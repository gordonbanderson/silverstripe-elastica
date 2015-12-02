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

	/**
	 * Array of highlighted fields, e.g. Title, Title.standard.  If this is empty then the
	 * ShowHighlight field of SearchableField is used to determine which fields to highlight
	 * @var array
	 */
	private $highlightedFields = array();


	/*
	Allow an empty search to return either no results (default) or all results, useful for
	showing some results during aggregation
	 */
	private $showResultsForEmptySearch = false;


	// ---- variables for more like this searching, defaults as per Elasticsearch ----
	private $minTermFreq = 2;

	private $maxTermFreq = 25;

	private $minDocFreq = 2;

	private $maxDocFreq = 0;

	private $minWordLength = 0;

	private $maxWordLength = 0;

	private $minShouldMatch = '30%';

	private $similarityStopWords = '';


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
	 * @return boolean true to show results for empty search, otherwise false
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
	 * @param string|boolean|integer $value the value of the indexed field to filter on
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
	 * Set the minimum term frequency for term to be considered in input query
	 */
	public function setMinTermFreq($newMinTermFreq) {
		$this->minTermFreq = $newMinTermFreq;
	}

	/**
	 * Set the maximum term frequency for term to be considered in input query
	 */
	public function setMaxTermFreq($newMaxTermFreq) {
		$this->maxTermFreq = $newMaxTermFreq;
	}

	/**
	 * Set the minimum number of documents a term can reside in for consideration as
	 * part of the input query
	 */
	public function setMinDocFreq($newMinDocFreq) {
		$this->minDocFreq = $newMinDocFreq;
	}

	/**
	 * Set the maximum number of documents a term can reside in for consideration as
	 * part of the input query
	 */
	public function setMaxDocFreq($newMaxDocFreq) {
		$this->maxDocFreq = $newMaxDocFreq;
	}

	/**
	 * Set the minimum word length for a term to be considered part of the query
	 */
	public function setMinWordLength($newMinWordLength) {
		$this->minWordLength = $newMinWordLength;
	}

	/**
	 * Set the maximum word length for a term to be considered part of the query
	 */
	public function setMaxWordLength($newMaxWordLength) {
		$this->maxWordLength = $newMaxWordLength;
	}

	/*
	Number or percentage of chosen terms that match
	 */
	public function setMinShouldMatch($newMinShouldMatch) {
		$this->minShouldMatch = $newMinShouldMatch;
	}

	public function setSimilarityStopWords($newSimilarityStopWords) {
		$this->similarityStopWords = $newSimilarityStopWords;
	}


	/*
	Set the highlight fields for subsequent searches
	 */

	/**
	 * @param string[] $newHighlightedFields
	 */
	public function setHighlightedFields($newHighlightedFields) {
		$this->highlightedFields = $newHighlightedFields;
	}



	/**
	 * Search against elastica using the criteria already provided, such as page length, start,
	 * and of course the filters
	 * @param  string $queryText query string, e.g. 'New Zealand'
	 * @param array $fieldsToSearch Mapping of name to an array of mapping Weight and Elastic mapping,
	 *                              e.g. array('Title' => array('Weight' => 2, 'Type' => 'string'))
	 * @return \PaginatedList    SilverStripe DataObjects returned from the search against ElasticSearch
	 */
	public function search($queryText, $fieldsToSearch = null,  $testMode = false) {
		if ($this->locale == null) {
			if (class_exists('Translatable') && \SiteTree::has_extension('Translatable')) {
				$this->locale = \Translatable::get_current_locale();
			} else {
				// if no translatable we only have the default locale
				$this->locale = \i18n::default_locale();
			}
		}

		$qg = new QueryGenerator();
		$qg->setQueryText($queryText);

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
		$elasticService->setHighlightedFields($this->highlightedFields);
		if ($testMode) {
			$elasticService->setTestMode(true);
		}
		$resultList = new ResultList($elasticService, $query, $queryText, $this->filters);

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

	/**
	 * @param string $queryText
	 */
	public function autocomplete_search($queryText, $field) {
		if ($this->locale == null) {
			if (class_exists('Translatable') && \SiteTree::has_extension('Translatable')) {
				$this->locale = \Translatable::get_current_locale();
			} else {
				// if no translatable we only have the default locale
				$this->locale = \i18n::default_locale();
			}
		}

		$qg = new QueryGenerator();
		$qg->setQueryText($queryText);

		//only one field but must be array
		echo "FIELD\n";
		print_r($field);
		$qg->setFields(array($field => 1));
		if ($this->classes) {
			$qg->setClasses($this->classes);
		}

		if (!empty($this->filters)) {
			$qg->setSelectedFilters($this->filters);
		}

		$qg->setPageLength($this->pageLength);
		$qg->setStart(0);

		$qg->setShowResultsForEmptyQuery(false);
		$query = $qg->generateElasticaAutocompleteQuery();

		print_r($query);

		$elasticService = \Injector::inst()->create('SilverStripe\Elastica\ElasticaService');
		$elasticService->setLocale($this->locale);
		echo "FILTERS\n";
		print_r($this->filters);
		$resultList = new ResultList($elasticService, $query, $queryText, $this->filters);

		// restrict SilverStripe ClassNames returned
		// elasticsearch uses the notion of a 'type', and here this maps to a SilverStripe class
		$types = $this->classes;
		$resultList->setTypes($types);
		// This works in that is breaks things $resultList->setTypes(array('SiteTree'));

		return $resultList;
	}


	/**
	 * Perform a 'More Like This' search, aka relevance feedback, using the provided indexed DataObject
	 * @param  \DataObject $indexedItem A DataObject that has been indexed in Elasticsearch
	 * @param  array $fieldsToSearch  array of fieldnames to search, mapped to weighting
	 * @param  $$testMode Use all shards, not just one, for consistent results during unit testing. See
	 *         https://www.elastic.co/guide/en/elasticsearch/guide/current/relevance-is-broken.html#relevance-is-broken
	 * @return \PaginatedList  List of results
	 */
	public function moreLikeThis($indexedItem, $fieldsToSearch, $testMode = false) {
		echo "INDEXED ITEM:".$indexedItem;

		if ($indexedItem == null) {
			throw new \InvalidArgumentException('A searchable item cannot be null');
		}

		if (!$indexedItem->hasExtension('SilverStripe\Elastica\Searchable')) {
			throw new \InvalidArgumentException('Objects of class '.$indexedItem->ClassName.' are not searchable');
		}

		if ($fieldsToSearch == null) {
			throw new \InvalidArgumentException('Fields cannot be null');
		}

		if ($this->locale == null) {
			if (class_exists('Translatable') && \SiteTree::has_extension('Translatable')) {
				$this->locale = \Translatable::get_current_locale();
			} else {
				// if no translatable we only have the default locale
				$this->locale = \i18n::default_locale();
			}
		}

		$weightedFieldsArray = array();
		foreach ($fieldsToSearch as $field => $weighting) {
			if (!is_string($field)) {
				throw new \InvalidArgumentException('Fields must be of the form fieldname => weight');
			}
			if (!is_numeric($weighting)) {
				throw new \InvalidArgumentException('Fields must be of the form fieldname => weight');
			}
			$weightedField = $field.'^'.$weighting;
			$weightedField = str_replace('^1', '', $weightedField);
			array_push($weightedFieldsArray, $weightedField);
		}

		$mlt = array(
			'fields' => $weightedFieldsArray,
			'docs' => array(
				array(
				'_type' => $indexedItem->ClassName,
				'_id' => $indexedItem->ID
				)
			),
			// defaults - FIXME, make configurable
			// see https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-mlt-query.html
			// ---- term selection params ----
			'min_term_freq' => $this->minTermFreq,
			'max_query_terms' => $this->maxTermFreq,
			'min_doc_freq' => $this->minDocFreq,
			//'max_doc_freq' =>  0, // this causes no results to be returned
			'min_word_length' => $this->minWordLength,
			'max_word_length' => $this->maxWordLength,
			'max_word_length' => $this->minWordLength,

			// ---- query formation params ----
			// see https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-minimum-should-match.html
			'minimum_should_match' => $this->minShouldMatch,

			#FIXME configuration
			'stop_words' => explode(',', $this->similarityStopWords)
		);

		if ($this->maxDocFreq > 0) {
			$mlt['max_doc_freq'] = $this->maxDocFreq;
		}



        $query = new Query();
        $query->setParams(array('query' => array('more_like_this' => $mlt)));


        $elasticService = \Injector::inst()->create('SilverStripe\Elastica\ElasticaService');
		$elasticService->setLocale($this->locale);
		if ($testMode) {
			$elasticService->setTestMode(true);
		}


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
	}


	public function hasSuggestedQuery() {
		$result = isset($this->SuggestedQuery) && $this->SuggestedQuery != null;
		return $result;
	}

	/**
	 * @return string
	 */
	public function getSuggestedQuery() {
		return $this->SuggestedQuery;
	}

	public function getSuggestedQueryHighlighted() {
		return $this->SuggestedQueryHighlighted;
	}

}
