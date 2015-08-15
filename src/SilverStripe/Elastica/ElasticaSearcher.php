<?php
use \SilverStripe\Elastica\ResultList;
use Elastica\Query;

use Elastica\Query\QueryString;
use Elastica\Aggregation\Filter;
use Elastica\Filter\Term;
use Elastica\Filter\BoolAnd;
use Elastica\Query\Filtered;


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
	 * Add a filter to the current query in the form of a key/value pair
	 * @param string $field the name of the indexed field to filter on
	 * @param string $value the value of the indexed field to filter on
	 */
	public function addFilter($field, $value) {
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
	 * @return ArrayList    SilverStripe DataObjects returned from the search against ElasticSearch
	 */
	public function search($q) {
		if ($q == '') {
			$q = '*';
		}
		$queryString = new QueryString($q);

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
			echo "Checking for key $key \n";
			if (!in_array($key, $rangeFilterKeys)) {
				echo " - Adding key \n";
				$filter = new Term();
				$filter->setTerm($key,$value);
				$elFilters[] = $filter;
			} else {
				// get the selected range filter
				$range = \RangedAggregation::getByTitle($key);
				$filter = $range->getFilter('Panoramic');
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
				print_r($elFilters);
				$queryFilter = $elFilters[0];
				break;

			default:
			//http://dev.jakayanrides.com/gallery/search/?Tags=bike&ISO=400 now failing
				$queryFilter = new BoolAnd();
				foreach ($elFilters as $filter) {
					$queryFilter->addFilter($filter);
				}
				break;
		}

		// the Elastica query object
		$query = null;



		$filtered = new Filtered(
			  $queryString,
			  $queryFilter
			);
			$query = new Query( $filtered);

		// pagination
		$query->setLimit($this->pageLength);
		$query->setFrom($this->start);

		// aggregation (optional)
		if ($this->manipulator) {
			$manipulatorInstance->augmentQuery($query);
		}

		print_r($query);


		$index = Injector::inst()->create('SilverStripe\Elastica\ElasticaService');
		$resultList = new ResultList($index, $query);

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
}
