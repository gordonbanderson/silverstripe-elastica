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


		if (!$queryTextExists && !$isMultiMatch && !$hasSelectedFilters) {
			return $this->queryNoMultiMatchNoFiltersSelected($queryText);
		}

		//Query text only provided
		elseif ($queryTextExists && !$isMultiMatch && !$hasSelectedFilters) {
			return $this->queryNoMultiMatchNoFiltersSelected();
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
	 */
	private function queryNoMultiMatchNoFiltersSelected() {
		// this will search all fields
		$textQuery = new QueryString($this->queryText);

		//Setting the lenient flag means that numeric fields can be searched for text values
		$textQuery->setParam('lenient', true);

		return $textQuery;
	}
}
