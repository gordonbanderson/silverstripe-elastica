<?php
namespace SilverStripe\Elastica;

use \SilverStripe\Elastica\ResultList;
use Elastica\Query;

use Elastica\Query\QueryString;
use Elastica\Aggregation\Filter;
use Elastica\Filter\Term;
use Elastica\Filter\BoolAnd;
use Elastica\Filter\MatchAll;
use Elastica\Query\Filtered;
use Elastica\Query\MultiMatch;
use SilverStripe\Elastica\RangedAggregation;

class QueryGenerator {

	/* The term to search for */
	private $queryText = '';

	/* Fields to search for as an array of Name to weighting, otherwise null for all, ie not
	a multi match query */
	private $fields = null;

	/* Aggregations already selected in format array(key => value), e.g. array('ISO' => 400) */
	private $selectedFilters = null;

	/* For an empty query, show results or not */
	private $showResultsForEmptyQuery = false;

	/* Manipulator to be used for aggregations */
	private $manipulator = null;

	/* The length of a page of results */
	private $pageLength = 10;

	/* Where to start, normally a multiple of pageLength */
	private $start = 0;

	/* Cache hit counter for test purposes */
	private static $cacheHitCtr = 0;

	/**
	 * Comma separated list of SilverStripe ClassNames to search. Leave blank for all
	 * @var string
	 */
	private $classes = '';


	public function setQueryText($newQueryText) {
		$this->queryText = $newQueryText;
	}


	public function setFields($newFields) {
		$this->fields = $newFields;
	}


	public function setSelectedFilters($newSelectedFilters) {
		$this->selectedFilters = $newSelectedFilters;
	}


	public function setShowResultsForEmptyQuery($newShowResultsForEmptyQuery) {
		$this->showResultsForEmptyQuery = $newShowResultsForEmptyQuery;
	}


	public function getShowResultsForEmptyQuery() {
		return $this->showResultsForEmptyQuery;
	}


	public function setPageLength($newPageLength) {
		$this->pageLength = $newPageLength;
	}


	public function setStart($newStart) {
		$this->start = $newStart;
	}


	/**
	 * Update the list of Classes to search, use SilverStripe ClassName comma separated
	 * @param string $newClasses comma separated list of SilverStripe ClassNames
	 */
	public function setClasses($newClasses) {
		$this->classes = $newClasses;
	}



	/**
	 * Set the manipulator, mainly used for aggregation
	 * @param string $newManipulator manipulator used for aggregation, must implement ElasticaSearchHelper
	 */
	public function setQueryResultManipulator($newManipulator) {
		$this->manipulator = $newManipulator;
	}


	/*
	Accessor to cache hit counter, for testing purposes
	 */
	public static function getCacheHitCounter() {
		return self::$cacheHitCtr;
	}


	public static function resetCacheHitCounter() {
		self::$cacheHitCtr = 0;
	}


	/**
	 * From the input variables create a suitable query using Elastica.  This is somewhat complex
	 * due to different formats with and without query text, with and without filters, with and
	 * without selected filters.  Extracting this logic into a separate class makes testing much
	 * faster and can be used for testing new cases
	 *
	 * @return [type]            [description]
	 */
	public function generateElasticaQuery() {
		$queryTextExists = ($this->queryText != '');
		$isMultiMatch = ($this->fields != null);

		if ($this->selectedFilters == null) {
			$this->selectedFilters = array();
		}
		$hasSelectedFilters = sizeof($this->selectedFilters) > 0;

		$this->manipulatorInstance = null;
		if ($this->manipulator) {
			$this->manipulatorInstance = \Injector::inst()->create($this->manipulator);
			$this->manipulatorInstance->queryGenerator = $this;
			$this->manipulatorInstance->originalQueryString = $this->queryText;
		}

		//This is a query_string object
		$textQuery = null;

		if (!$isMultiMatch) {
			$textQuery = $this->simpleTextQuery();
		} else {
			$textQuery = $this->multiMatchQuery();
		}

		$query = $this->addFilters($textQuery);
		$query->OriginalQueryText = $this->queryText;

		//This needs to be query object of some form
		$this->addAggregation($query);


		// pagination
		$query->setLimit($this->pageLength);
		$query->setFrom($this->start);

		if ($this->manipulatorInstance && !$queryTextExists) {
			$sort = $this->manipulatorInstance->getDefaultSort();
			$query->setSort($sort);
		}

		return $query;

	}


	/**
	 * Using a query string object, return a suitable filtered or unfiltered query object
	 * @param Elastica\Query\QueryString $textQuery A query_string representing the current query
	 */
	private function addFilters($textQuery) {
		if ($this->manipulator) {
			$this->manipulatorInstance->updateFilters($this->selectedFilters);
		}

		$elFilters = array();
		$rangeFilterKeys = RangedAggregation::getTitles();

		foreach ($this->selectedFilters as $key => $value) {
			if (!in_array($key, $rangeFilterKeys)) {
				$filter = new Term();
				$filter->setTerm($key,$value);
				$elFilters[] = $filter;
			} else {
				// get the selected range filter
				$range = RangedAggregation::getByTitle($key);
				$filter = $range->getFilter($value);
				$elFilters[] = $filter;
			}
		}


		// if not facets selected, pass through null
		$queryFilter = null;
		switch (count($this->selectedFilters)) {
			case 0:
				// filter already null
				break;
			case 1:
				$queryFilter = $elFilters[0];
				break;
			default:
				$queryFilter = new BoolAnd();

				foreach ($elFilters as $filter) {
					$queryFilter->addFilter($filter);
				}
				break;
		}

		// the Elastica query object
		if ($queryFilter == null) {
			$query = new Query($textQuery);
		} else {
			//MatchAll appears not be allowed inside a filtered query which is a bit of a pain.
			if ($textQuery instanceof MatchAll) {
				$textQuery = null;
			}

			$filtered = new Filtered(
			  $textQuery,
			  $queryFilter
			);
			$query = new Query($filtered);

		}

		return $query;
	}


	private function addAggregation(&$query) {
		// aggregation (optional)
		if ($this->manipulatorInstance) {
			$this->manipulatorInstance->augmentQuery($query);
		}
	}


	/*
	Simplest form of search, namely search for text string against all fields.  In Curl terms:

	curl -XGET 'http://localhost:9200/elastica_ss_module_test_en_us/_search?pretty' -d '
	{
	   "query": {
	        "query_string": {
	            "query":        "Image"
	        }
	    }
	}
	'

	In the case of an empty string change the query to a wildcard of '*'.

	FIXME: Ideally remove the query entirely, cannot immediately see how to do it in Elastica
	This works at curl level:

	curl -XGET 'http://localhost:9200/elastica_ss_module_test_en_us/_search?pretty'

	 */
	private function simpleTextQuery() {
		// this will search all fields
		$textQuery = new QueryString($this->queryText);

		//Setting the lenient flag means that numeric fields can be searched for text values
		$textQuery->setParam('lenient', true);

		if ($this->showResultsForEmptyQuery && $this->queryText == '') {
			$textQuery = new MatchAll();
		}

		return $textQuery;
	}


	// USE MATCH_ALL, see https://www.elastic.co/guide/en/elasticsearch/reference/1.4/query-dsl-match-all-query.html
	private function multiMatchQuery() {
		$textQuery = new MultiMatch();

		// Differing cases for showing and not showing empty string
		if ($this->queryText == '') {
			if (!$this->showResultsForEmptyQuery) {
				$textQuery->setQuery('');
			} else {
				//WIP
				$textQuery = new MatchAll();
			}
			// else {otherwise leave blank, which will then make use of the sort order}
		}

		// If there is text, search for it regardless
		else {
			$textQuery->setQuery($this->queryText);
		}

		if ($textQuery instanceof MultiMatch) {
			$elasticaFields = $this->convertWeightedFieldsForElastica($this->fields);
	        //$textQuery->setFields(array('Title^4','Content','Content.*'));
	        //$fieldsCSV = implode(',', $fieldsToSearch);
	        $textQuery->setFields($elasticaFields);
	        $textQuery->setType('most_fields');

	        //Setting the lenient flag means that numeric fields can be searched for text values
	        $textQuery->setParam('lenient', true);
		}

        return $textQuery;
	}



	/**
	 * Use the configuration from the Search settings held in the database to
	 * form the array of fields suitable for a multimatch query.  Call this
	 * after having called setClasses
	 *
	 * @return array Array of fieldsname to weight
	 */
	public function convertWeightedFieldsForElastica($fields) {
		$result = array();
		$nameToType = self::getSearchFieldsMappingForClasses($this->classes,$fields);

		if (sizeof($fields) != 0) {
			foreach ($fields as $fieldName => $weight) {
				$fieldCfg = "$fieldName";
				if ($weight != 1) {
					$fieldCfg .= '^'.$weight;
				}
				array_push($result, $fieldCfg);
				if (isset($nameToType[$fieldName])) {
					if ($nameToType[$fieldName] == 'string') {
						$fieldCfg = "{$fieldName}.*";
						if ($weight != 1) {
							$fieldCfg .= '^'.$weight;
						}
						array_push($result, $fieldCfg);
					}
				} else {
					throw new \Exception("Field $fieldName does not exist");
				}
			}
		}
		return $result;
	}



	/**
	 * Get a hash of name to Elasticserver mapping, e.g. 'Title' => 'string'
	 * Use SS_Cache to save on database hits, as this data only changes at build time
	 * @param  string $classes CSV or array of ClassNames to search, or empty for
	 *         all of SiteTree
	 * @return array Array hash of fieldname to Elasticsearch mapping
	 */
	public static function getSearchFieldsMappingForClasses($classes = null, $fieldsAllowed = null) {

		// Get a array of relevant classes to search
		$cache = QueryGenerator::getCache();
		$csvClasses = $classes;
		if (is_array($classes)) {
			$csvClasses = implode(',',$classes);
		}

		$key = 'SEARCHABLE_FIELDS_'.str_replace(',', '_', $csvClasses);

		if ($fieldsAllowed) {
			$fieldsAllowedCSV = self::convertToQuotedCSV(array_keys($fieldsAllowed));
			$key .= '_' . str_replace(',', '_', str_replace("'", '_',$fieldsAllowedCSV));
		}

		$result = $cache->load($key);
		if (!$result) {
			$relevantClasses = array();
			if (!$csvClasses) {
				$sql = "SELECT DISTINCT Name from SearchableClass where InSiteTree = 1 order by Name";
				$records = \DB::query($sql);
				foreach ($records as $record) {
					array_push($relevantClasses, $record['Name']);
				}
			} else {
				$relevantClasses = explode(',', $csvClasses);
			}

			$result = array();
			if (sizeof($relevantClasses) > 0) {
				$relevantClassesCSV = self::convertToQuotedCSV($relevantClasses);

				//Perform a database query to get get a list of searchable fieldnames to Elasticsearch mapping
				$sql = "SELECT  sf.Name,sf.Type FROM SearchableClass sc  INNER JOIN SearchableField sf ON "
					 . "sc.id = sf.SearchableClassID WHERE sc.name IN ($relevantClassesCSV)";
				if ($fieldsAllowed) {
					$fieldsAllowedCSV = self::convertToQuotedCSV(array_keys($fieldsAllowed));
					if (strlen($fieldsAllowedCSV) > 0) {
						$sql .= " AND sf.Name IN ($fieldsAllowedCSV)";
					}
				}

				$records = \DB::query($sql);
				foreach ($records as $record) {
					$name = $record['Name'];
					$type = $record['Type'];

					/**
					 * FIXME:
					 * This will overwrite duplicate keys such as Content or Title from other Classes.
					 * Ideally need to check if the mapping being overwritten changes, e.g. if
					 * a field such as BirthDate is date in one class and string in another
					 * and throw an exception accordingly
					 */
					$result[$name] = $type;
				}
			}
			$cache->save(json_encode($result),$key);
		}  else {
			// true is necessary here to decode the array hash back to an array and not a struct
			self::$cacheHitCtr++;
			$result = json_decode($result,true);
		}

		return $result;
	}


	public static function getCache() {
		$cache = \SS_Cache::factory('elasticsearch');
		return $cache;
	}


	/**
	 * Convert either a CSV string or an array to a CSV single quoted string, suitable for use in
	 * an SQL IN clause
	 * @param  [type] $csvOrArray [description]
	 * @return [type]             [description]
	 */
	public static function convertToQuotedCSV($csvOrArray) {
		$asArray = $csvOrArray;
		if (!is_array($csvOrArray)) {
			if ($csvOrArray == null) {
				$asArray = array();
			} else {
				$asArray = explode(',', $csvOrArray);
			}

		}
		$quoted = array();
		foreach ($asArray as $value) {
			if (strlen($value) > 0) {
				$item = "'".$value."'";
				array_push($quoted, $item);
			}

		}
		return implode(',', $quoted);;
	}
}
