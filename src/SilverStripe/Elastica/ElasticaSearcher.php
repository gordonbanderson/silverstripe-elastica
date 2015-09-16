<?php
use \SilverStripe\Elastica\ResultList;
use Elastica\Query;

use Elastica\Query\QueryString;
use Elastica\Aggregation\Filter;
use Elastica\Filter\Term;
use Elastica\Filter\BoolAnd;
use Elastica\Query\Filtered;
use Elastica\Query\MultiMatch;


class ElasticSearcher {
	/**
	 * Comma separated list of SilverStripe ClassNames to search. Leave blank for all
	 * @var string
	 */
	private $classes = '';

	/**
	 * Array of aggregation selected mapped to the value selected, e.g. 'Aperture' => '11'
	 * @var array
	 */
	private $filters = array();


	/**
	 * The locale to search, is set to current locale or default locale by default
	 * but can be overriden.  This is the code in the form en_US, th_TH etc
	 */
	private $locale = null;

	/**
	 * Object just to manipulate the query and result, used for aggregations
	 * @var ElasticaSearchHelper
	 */
	private $manipulator;

	/**
	 * Offset from zero to return search results from
	 * @var integer
	 */
	private $start = 0;

	/**
	 * How many search results to return
	 * @var integer
	 */
	private $pageLength = 10;

	/**
	 * After a search is performed aggregrations are saved here
	 * @var array
	 */
	private $aggregations = null;

	/**
	 * Update the list of Classes to search, use SilverStripe ClassName comma separated
	 * @param string $newClasses comma separated list of SilverStripe ClassNames
	 */
	public function setClasses($newClasses) {
		$this->classes = $newClasses;
	}

	/**
	 * Set the manipulator, mainly used for aggregation
	 * @param ElasticaSearchHelper $newManipulator manipulator used for aggregation
	 */
	public function setQueryResultManipulator($newManipulator) {
		$this->manipulator = $newManipulator;
	}

	/**
	 * Update the start variable
	 * @param int $newStart Offset for search
	 */
	public function setStart($newStart) {
		$this->start = $newStart;
	}

	/**
	 * Update the page length variable
	 * @param int $newPageLength the number of results to be returned
	 */
	public function setPageLength($newPageLength) {
		$this->pageLength = $newPageLength;
	}

	/**
	 * Set a new locale
	 * @param string $newLocale locale in short form, e.g. th_TH
	 */
	public function setLocale($newLocale) {
		$this->locale = $newLocale;
	}

	/**
	 * Add a filter to the current query in the form of a key/value pair
	 * @param string $field the name of the indexed field to filter on
	 * @param string $value the value of the indexed field to filter on
	 */
	public function addFilter($field, $value) {
		echo "Added filter $field -> $value\n";
		$this->filters[$field] = $value;
	}

	/**
	 * Accessor to the aggregations, to be used after a search
	 * @return array Aggregations returned after a search
	 */
	public function getAggregations() {
		return $this->aggregations;
	}

	/**
	 * Search against elastica using the criteria already provided, such as page length, start,
	 * and of course the filters
	 * @param  string $q query string
	 * @param array $fieldsToSearch Mapping of name to an array of mapping Weight and Elastic mapping,
	 *                              e.g. array('Title' => array('Weight' => 2, 'Type' => 'string'))
	 * @return ArrayList    SilverStripe DataObjects returned from the search against ElasticSearch
	 */
	public function search($q, $fieldsToSearch = null) {
		if ($this->locale == null) {
			if (!class_exists('Translatable')) {
				// if no translatable we only have the default locale
				$this->locale = \i18n::default_locale();
			} else {
				$this->locale = Translatable::get_current_locale();
			}
		}

		//echo "SEARCH LOCALE:$this->locale\n";

		/*
		FIXME - this needs to go in the augmenter
		if ($q == '') {
			$q = '*';
		}
		*/

		$manipulatorInstance = null;
		if ($this->manipulator) {
			$manipulatorInstance = Injector::inst()->create($this->manipulator);
		}

		// update the filters, do any necessary remapping here
		if ($this->manipulator) {
			$manipulatorInstance->updateFilters($this->filters);
		}

		$elFilters = array();
		$rangeFilterKeys = RangedAggregation::getTitles();

		foreach ($this->filters as $key => $value) {
			if (!in_array($key, $rangeFilterKeys)) {
				$filter = new Term();
				$filter->setTerm($key,$value);
				$elFilters[] = $filter;
			} else {
				// get the selected range filter
				$range = \RangedAggregation::getByTitle($key);
				$filter = $range->getFilter($value);
				$elFilters[] = $filter;
			}
		}


		// if not facets selected, pass through null
		$queryFilter = null;
		switch (count($this->filters)) {
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

		$textQuery = null;

		if (is_array($fieldsToSearch) && sizeof($fieldsToSearch) > 0) {
			$textQuery = new MultiMatch();
	        $textQuery->setQuery($q);
	        $elasticaFields = $this->convertWeightedFieldsForElastica($fieldsToSearch);
	        //$textQuery->setFields(array('Title^4','Content','Content.*'));
	        //$fieldsCSV = implode(',', $fieldsToSearch);
	        $textQuery->setFields($elasticaFields);
	        $textQuery->setType('most_fields');
		} else {
			// this will search all fields
			$textQuery = new QueryString($q);
		}

		// the Elastica query object
		$filtered = new Filtered(
		  $textQuery, // FIXME this needs to be multimatch
		  $queryFilter
		);
		$query = new Query($filtered);



		// pagination
		$query->setLimit($this->pageLength);
		$query->setFrom($this->start);

		// aggregation (optional)
		if ($this->manipulator) {
			$manipulatorInstance->augmentQuery($query);
		}

		echo "**** QUERY ****\n";
		print_r($query);

		$elasticService = Injector::inst()->create('SilverStripe\Elastica\ElasticaService');
		$elasticService->setLocale($this->locale);


		$resultList = new ResultList($elasticService, $query);

		// restrict SilverStripe ClassNames returned
		// elasticsearch uses the notion of a 'type', and here this maps to a SilverStripe class
		$types = $this->classes;
		$resultList->setTypes($types);

		// set the optional aggregation manipulator
		$resultList->SearchHelper = $this->manipulator;

		// at this point ResultList object, not yet executed search query
		$paginated = new \PaginatedList(
			$resultList
		);

		$paginated->setPageStart($this->start);
		$paginated->setPageLength($this->pageLength);
		$paginated->setTotalItems($resultList->getTotalItems());

		$this->aggregations = $resultList->getAggregations();
		return $paginated;
	}


	/**
	 * Use the configuration from the Search settings held in the database to
	 * form the array of fields suitable for a multimatch query.  Call this
	 * after having called setClasses
	 *
	 * @return array Array of fieldsname to weight
	 */
	public function convertWeightedFieldsForElastica($fields) {
		echo "T1:CWFFE: \n";
		print_r($fields);

		$result = array();
		error_log(print_r($fields));
		$nameToType = self::getSearchFieldsMappingForClasses($this->classes,$fields); // FIXME
		echo "NTT";
		print_r($nameToType);

		if (sizeof($fields) == 0) {
			// FIXME - this seems to work but double check
		} else {
			foreach ($fields as $fieldName => $weight) {
				$fieldCfg = "$fieldName";
				if ($weight != 1) {
					$fieldCfg .= '^'.$weight;
				}
				array_push($result, $fieldCfg);

				if ($nameToType[$fieldName] == 'string') {
					$fieldCfg = "{$fieldName}.*";
					if ($weight != 1) {
						$fieldCfg .= '^'.$weight;
					}
					array_push($result, $fieldCfg);
				}
			}
		}

		error_log('FIELDS:'.print_r($result,1));
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
		echo "T1:Classes=";
		print_r($classes);
		// Get a array of relevant classes to search
		$cache = ElasticSearcher::getCache();
		$csvClasses = $classes;
		if (is_array($classes)) {
			$csvClasses = implode(',', array_keys($classes)); // FIXME, this line
		}
		$key = 'SEARCHABLE_FIELDS3_'.str_replace(',', '_', $csvClasses);
		$result = $cache->load($key);
		if (!$result) {
			echo "T2: CSV CLASSES:$csvClasses\n";
			$relevantClasses = array();
			if (!$csvClasses) {
				$sql = "SELECT DISTINCT Name from SearchableClass where InSiteTree = 1 order by Name";
				$records = DB::query($sql);
				foreach ($records as $record) {
					echo "RECORD:\n";
					print_r($record);
					array_push($relevantClasses, $record['Name']);
				}
			} else {
				$relevantClasses = explode(',', $csvClasses);
			}

			echo "T3: Relevant classes\n";
			print_r($relevantClasses);

			$relevantClassesCSV = self::convertToQuotedArray($relevantClasses);

			echo "T4: Relevant CSV=".$relevantClassesCSV."\n";

			//Perform a database query to get get a list of searchable fieldnames to Elasticsearch mapping
			$sql = "SELECT  sf.Name,sf.Type FROM SearchableClass sc  INNER JOIN SearchableField sf ON "
				 . "sc.id = sf.SearchableClassID WHERE sc.name IN ($relevantClassesCSV)";
			echo($sql."\n");
			$records = DB::query($sql);
			$result = array();
			foreach ($records as $record) {
				$name = $record['Name'];
				$type = $record['Type'];

				echo "T5: RECORD FOUND: $name => $type\n";


				/**
				 * FIXME:
				 * This will overwrite duplicate keys such as Content or Title from other Classes.
				 * Ideally need to check if the mapping being overwritten changes, e.g. if
				 * a field such as BirthDate is date in one class and string in another
				 * and throw an exception accordingly
				 */
				$result[$name] = $type;
			}
			echo "Saving to $key";
			print_r($result);
			$cache->save(json_encode($result),$key);
		}  else {
			// true is necessary here to decode the array hash back to an array and not a struct
			$result = json_decode($result,true);
		}

		return $result;
	}


	public static function getCache() {
		$cache = SS_Cache::factory('elasticsearch');
		return $cache;
	}

	/**
	 * Convert either a CSV string or an array to a CSV single quoted string, suitable for use in
	 * an SQL IN clause
	 * @param  [type] $csvOrArray [description]
	 * @return [type]             [description]
	 */
	private static function convertToQuotedArray($csvOrArray) {
		echo "CONVERT TO QUOTED ARRAY\n";
		print_r($csvOrArray);

		$asArray = $csvOrArray;
		if (!is_array($csvOrArray)) {
			$asArray = implode(',', $csvOrArray);
		}
		$quoted = array();
		foreach ($asArray as $value) {
			$item = "'".$value."'";
			array_push($quoted, $item);
		}
		return implode(',', $quoted);;
	}

}
