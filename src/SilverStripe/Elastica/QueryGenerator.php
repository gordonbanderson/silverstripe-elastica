<?php
namespace SilverStripe\Elastica;

use \SilverStripe\Elastica\ResultList;
use Elastica\Query;

use Elastica\Query\QueryString;
use Elastica\Aggregation\Filter;
use Elastica\Filter\Term;
use Elastica\Filter\BoolAnd;
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
	 * Set the manipulator, mainly used for aggregation
	 * @param ElasticaSearchHelper $newManipulator manipulator used for aggregation
	 */
	public function setQueryResultManipulator($newManipulator) {
		$this->manipulator = $newManipulator;
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

		echo "Query text exists: $queryTextExists\n";
		echo "is multi match?: $isMultiMatch\n";
		echo "Has selected filters?: $hasSelectedFilters\n";


		$this->manipulatorInstance = null;
		if ($this->manipulator) {
			$this->manipulatorInstance = \Injector::inst()->create($this->manipulator);
			$this->manipulatorInstance->queryGenerator = $this;
			$this->manipulatorInstance->originalQueryString = $this->queryText;
		}

		//This is a query_string object
		$textQuery = null;

		//if (!$queryTextExists && !$isMultiMatch && !$hasSelectedFilters) {
		if (!$queryTextExists && !$isMultiMatch) {
			$textQuery = $this->queryNoMultiMatchNoFiltersSelected($this->queryText);
		}

		//Query text only provided
		//elseif ($queryTextExists && !$isMultiMatch && !$hasSelectedFilters) {
		elseif ($queryTextExists && !$isMultiMatch) {
			$textQuery = $this->queryNoMultiMatchNoFiltersSelected();
		}


		echo "PRE AGGS QUERY:\n";
		echo get_class($textQuery);
		print_r($textQuery);

		$query = $this->addFilters($textQuery);

		//This needs to be query object of some form
		$this->addAggregation($query);


		// pagination
		$query->setLimit($this->pageLength);
		$query->setFrom($this->start);

		//TODO - sort

		return $query;

	}


	/**
	 * Using a query string object, return a suitable filtered or unfiltered query object
	 * @param Elastica\Query\QueryString $textQuery A query_string representing the current query
	 */
	private function addFilters($textQuery) {
		if ($this->manipulator) {
			echo "SEARCH: UPDATING FILTERS";
			$this->manipulatorInstance->updateFilters($this->filters);
		}


		$elFilters = array();
		$rangeFilterKeys = RangedAggregation::getTitles();

		foreach ($this->selectedFilters as $key => $value) {
			echo "Checking filter $key => $value\n";
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



		// the Elastica query object
		if ($queryFilter == null) {
			$query = new Query($textQuery);
		} else {
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
			echo "AUGMENTING QUERY FOR AGGS\n";
			print_r($query);
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
	private function queryNoMultiMatchNoFiltersSelected() {
		// this will search all fields
		$textQuery = new QueryString($this->queryText);

		//Setting the lenient flag means that numeric fields can be searched for text values
		$textQuery->setParam('lenient', true);

		if ($this->showResultsForEmptyQuery && $this->queryText == '') {
			$params = $textQuery->getParams();

			print_r($params);
			$params['query'] = '*';
			$textQuery->setParams($params);
		}

		return $textQuery;
	}
}
