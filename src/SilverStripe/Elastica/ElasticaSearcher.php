<?php
namespace SilverStripe\Elastica;

//use \SilverStripe\Elastica\ResultList;
use Elastica\Query;

use Elastica\Query\QueryString;
use Elastica\Aggregation\Filter;
use Elastica\Filter\Term;
use Elastica\Filter\BoolAnd;
use Elastica\Query\Filtered;
use Elastica\Query\MultiMatch;
use Elastica\Query\MoreLikeThis;



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


	/*
	Allow an empty search to return either no results (default) or all results, useful for
	showing some results during aggregation
	 */
	private $showResultsForEmptySearch = false;


	/*
	Show results for an empty search string
	 */
	public function showResultsForEmptySearch() {
		$this->showResultsForEmptySearch = true;
	}


	/*
	Hide results for an empty search
	 */
	public function hideResultsForEmptySearch() {
		$this->showResultsForEmptySearch = false;
	}


	/**
	 * Accessor the variable to determine whether or not to show results for an empty search
	 * @return [type] [description]
	 */
	public function getShowResultsForEmptySearch() {
		return $this->showResultsForEmptySearch;
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
				$this->locale = \Translatable::get_current_locale();
			}
		}

		// pagination


		$qg = new QueryGenerator();
		$qg->setQueryText($q);

		$qg->setFields($fieldsToSearch);
		$qg->setSelectedFilters($this->filters);
		$qg->setClasses($this->classes);

		$qg->setPageLength($this->pageLength);
		$qg->setStart($this->start);

		$qg->setQueryResultManipulator($this->manipulator);

		$qg->setShowResultsForEmptyQuery($this->showResultsForEmptySearch);

		$query = $qg->generateElasticaQuery();

		$elasticService = \Injector::inst()->create('SilverStripe\Elastica\ElasticaService');
		$elasticService->setLocale($this->locale);

		$resultList = new ResultList($elasticService, $query, $q);

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

		if ($resultList->SuggestedQuery) {
			$this->SuggestedQuery = $resultList->SuggestedQuery;
			$this->SuggestedQueryHighlighted = $resultList->SuggestedQueryHighlighted;
		}
		return $paginated;
	}


	/* Perform an autocomplete search */
	public function autocomplete_search($q, $fieldsToSearch = null) {
		if ($this->locale == null) {
			if (!class_exists('Translatable')) {
				// if no translatable we only have the default locale
				$this->locale = \i18n::default_locale();
			} else {
				$this->locale = \Translatable::get_current_locale();
			}
		}

		$qg = new QueryGenerator();
		$qg->setQueryText($q);

		$qg->setFields($fieldsToSearch);
		$qg->setClasses($this->classes);

		$qg->setPageLength($this->pageLength);
		$qg->setStart(0);

		$qg->setShowResultsForEmptyQuery(false);

		$query = $qg->generateElasticaAutocompleteQuery();

		$elasticService = \Injector::inst()->create('SilverStripe\Elastica\ElasticaService');
		$elasticService->setLocale($this->locale);

		$resultList = new ResultList($elasticService, $query, $q);

		// restrict SilverStripe ClassNames returned
		// elasticsearch uses the notion of a 'type', and here this maps to a SilverStripe class
		$types = $this->classes;
		$resultList->setTypes($types);

		return $resultList;
	}


	/**
	 * Perform a 'More Like This' search, aka relevance feedback, using the provided indexed DataObject
	 * @param  DataObject $indexedItem A DataObject that has been indexed in Elasticsearch
	 * @param  array $fieldsToSearch  array of fieldnames to search
	 * @return resultList  List of results
	 */
	public function moreLikeThis($indexedItem, $fieldsToSearch) {
		if ($this->locale == null) {
			if (!class_exists('Translatable')) {
				// if no translatable we only have the default locale
				$this->locale = \i18n::default_locale();
			} else {
				$this->locale = \Translatable::get_current_locale();
			}
		}

		$mlt = array(
			'fields' => $fieldsToSearch,
			'docs' => array(
				array(
				'_type' => $indexedItem->ClassName,
				'_id' => $indexedItem->ID
				)
			),
			'min_term_freq' => 1,
			'max_query_terms' => 12,
			'min_word_length' => 3,
			#FIXME configuration
			'stop_words' => array('ca','about', 'le','du','ou','bc','archives', 'website', 'click',
				'web','file', 'descriptive', 'taken', 'copyright', 'collection', 'from', 'image',
				'page', 'which', 'etc', 'news', 'service', 'publisher','did','were', 'his', 'url')
		);


        $query = new Query();
        $query->setParams(array('query' => array('more_like_this' => $mlt)));

        $elasticService = \Injector::inst()->create('SilverStripe\Elastica\ElasticaService');
		$elasticService->setLocale($this->locale);


// pagination
		$query->setLimit($this->pageLength);
		$query->setFrom($this->start);

		$resultList = new ResultList($elasticService, $query, null);
        // at this point ResultList object, not yet executed search query
		$paginated = new \PaginatedList(
			$resultList
		);

		$paginated->setPageStart($this->start);
		$paginated->setPageLength($this->pageLength);
		$paginated->setTotalItems($resultList->getTotalItems());

		$this->aggregations = $resultList->getAggregations();

		return $paginated;

        //$mltQuery->setMaxQueryTerms(1);
        //$mltQuery->setMinDocFrequency(1);
        //$mltQuery->setMinTermFrequency(1);
	}


	public function hasSuggestedQuery() {
		$result = isset($this->SuggestedQuery) && $this->SuggestedQuery != null;
		return $result;
	}

	public function getSuggestedQuery() {
		return $this->SuggestedQuery;
	}

	public function getSuggestedQueryHighlighted() {
		return $this->SuggestedQueryHighlighted;
	}

}
