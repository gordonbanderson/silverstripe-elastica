<?php

use Elastica\Document;
use Elastica\Query;
use \SilverStripe\Elastica\ResultList;
use Elastica\Query\QueryString;
use Elastica\Aggregation\Filter;
use Elastica\Filter\Term;
use Elastica\Filter\BoolAnd;
use Elastica\Aggregation\Terms;
use Elastica\Query\Filtered;
use Elastica\Query\Range;
use \SilverStripe\Elastica\ElasticSearcher;
use \SilverStripe\Elastica\Searchable;
use \SilverStripe\Elastica\QueryGenerator;
use \SilverStripe\Elastica\ElasticaUtil;

class ElasticSearchPage_Controller extends Page_Controller {

	private static $allowed_actions = array('SearchForm', 'submit', 'index', 'similar');

	public function init() {
		parent::init();

		Requirements::javascript(THIRDPARTY_DIR . '/jquery/jquery.js');
		Requirements::javascript("elastica/javascript/jquery.autocomplete.js");
		Requirements::javascript("elastica/javascript/elastica.js");
		Requirements::css("elastica/css/elastica.css");
	}



	/*
	Find DataObjects in Elasticsearch similar to the one selected.  Note that aggregations are not
	taken into account, merely the text of the selected document.
	 */
	public function similar() {
		//FIXME double check security, ie if escaping needed
		$class = $this->request->param('ID');
		$instanceID = $this->request->param('OtherID');

		$data = array(
			'Content' => $this->Content,
			'Title' => $this->Title,
			'SearchPerformed' => false
		);

		// record the time
		$startTime = microtime(true);

		//instance of ElasticPage associated with this controller
		$ep = Controller::curr()->dataRecord;

		// use an Elastic Searcher, which needs primed from URL params
		$es = new ElasticSearcher();

		// start, and page length, i.e. pagination
		$startParam = $this->request->getVar('start');
		$start = isset($startParam) ? $startParam : 0;
		$es->setStart($start);
		$es->setPageLength($ep->ResultsPerPage);


		$es->setMinTermFreq($this->MinTermFreq);
		$es->setMaxTermFreq($this->MaxTermFreq);
		$es->setMinDocFreq($this->MinDocFreq);
		$es->setMaxDocFreq($this->MaxDocFreq);
		$es->setMinWordLength($this->MinWordLength);
		$es->setMaxWordLength($this->MaxWordLength);
		$es->setMinShouldMatch($this->MinShouldMatch);
		$es->setSimilarityStopWords($this->SimilarityStopWords);


		// filter by class or site tree
		if($ep->SiteTreeOnly) {
			T7; //FIXME test missing
			$es->addFilter('IsInSiteTree', true);
		} else {
			$es->setClasses($ep->ClassesToSearch);
		}


		// get the edited fields to search from the database for this search page
		// Convert this into a name => weighting array
		$fieldsToSearch = array();
		$editedSearchFields = $this->ElasticaSearchableFields()->filter(array(
			'Active' => true,
			'SimilarSearchable' => true
		));

		foreach($editedSearchFields->getIterator() as $searchField) {
			$fieldsToSearch[$searchField->Name] = $searchField->Weight;
		}

		// Use the standard field for more like this, ie not stemmed
		foreach($fieldsToSearch as $field => $value) {
			$fieldsToSearch[$field . '.standard'] = $value;
			unset($fieldsToSearch[$field]);
		}

		try {
			// Simulate server being down for testing purposes
			if($this->request->getVar('ServerDown')) {
				throw new Elastica\Exception\Connection\HttpException('Unable to reach search server');
			}
			if(class_exists($class)) {
				$instance = \DataObject::get_by_id($class, $instanceID);

				$paginated = $es->moreLikeThis($instance, $fieldsToSearch);

				$this->Aggregations = $es->getAggregations();
				$data['SearchResults'] = $paginated;
				$data['SearchPerformed'] = true;
				$data['SearchPageLink'] = $ep->Link();
				$data['SimilarTo'] = $instance;
				$data['NumberOfResults'] = $paginated->getTotalItems();


				$moreLikeThisTerms = $paginated->getList()->MoreLikeThisTerms;
				$fieldToTerms = new ArrayList();
				foreach(array_keys($moreLikeThisTerms) as $fieldName) {
					$readableFieldName = str_replace('.standard', '', $fieldName);
					$fieldTerms = new ArrayList();
					foreach($moreLikeThisTerms[$fieldName] as $value) {
						$do = new DataObject();
						$do->Term = $value;
						$fieldTerms->push($do);
					}

					$do = new DataObject();
					$do->FieldName = $readableFieldName;
					$do->Terms = $fieldTerms;
					$fieldToTerms->push($do);
				}

				$data['SimilarSearchTerms'] = $fieldToTerms;
			} else {
				// class does not exist
				$data['ErrorMessage'] = "Class $class is either not found or not searchable\n";
			}
		} catch (\InvalidArgumentException $e) {
			$data['ErrorMessage'] = "Class $class is either not found or not searchable\n";
		} catch (Elastica\Exception\Connection\HttpException $e) {
			$data['ErrorMessage'] = 'Unable to connect to search server';
			$data['SearchPerformed'] = false;
		}


		// calculate time
		$endTime = microtime(true);
		$elapsed = round(100 * ($endTime - $startTime)) / 100;

		// store variables for the template to use
		$data['ElapsedTime'] = $elapsed;
		$data['Elapsed'] = $elapsed;

		// allow the optional use of overriding the search result page, e.g. for photos, maps or facets
		if($this->hasExtension('PageControllerTemplateOverrideExtension')) {
			return $this->useTemplateOverride($data);
		} else {
			return $data;
		}
	}


	/*
	Display the search form. If the query parameter exists, search against Elastica
	and render results accordingly.
	 */
	public function index() {
		$data = array(
			'Content' => $this->Content,
			'Title' => $this->Title,
			'SearchPerformed' => false
		);

		// record the time
		$startTime = microtime(true);

		//instance of ElasticPage associated with this controller
		$ep = Controller::curr()->dataRecord;

		// use an Elastic Searcher, which needs primed from URL params
		$es = new ElasticSearcher();

		// start, and page length, i.e. pagination
		$startParam = $this->request->getVar('start');
		$start = isset($startParam) ? $startParam : 0;
		$es->setStart($start);
		$es->setPageLength($ep->ResultsPerPage);


		// Do not show suggestions if this flag is set
		$ignoreSuggestions = null !== $this->request->getVar('is');


		// query string
		$queryTextParam = $this->request->getVar('q');
		$queryText = !empty($queryTextParam) ? $queryTextParam : '';

		$testMode = !empty($this->request->getVar('TestMode'));

		// filters for aggregations
		$ignore = \Config::inst()->get('Elastica', 'BlackList');
		foreach($this->request->getVars() as $key => $value) {
			if(!in_array($key, $ignore)) {
				$es->addFilter($key, $value);
			}
		}

		// filter by class or site tree
		if($ep->SiteTreeOnly) {
			$es->addFilter('IsInSiteTree', true);
		} else {
			$es->setClasses($ep->ClassesToSearch);
		}

		// set the optional aggregation manipulator
		// In the event of a manipulator being present, show all the results for search
		// Otherwise aggregations are all zero
		if($this->SearchHelper) {
			$es->setQueryResultManipulator($this->SearchHelper);
			$es->showResultsForEmptySearch();
		} else {
			$es->hideResultsForEmptySearch();
		}

		// get the edited fields to search from the database for this search page
		// Convert this into a name => weighting array
		$fieldsToSearch = array();
		$editedSearchFields = $this->ElasticaSearchableFields()->filter(array(
			'Active' => true,
			'Searchable' => true
		));

		foreach($editedSearchFields->getIterator() as $searchField) {
			$fieldsToSearch[$searchField->Name] = $searchField->Weight;
		}

		$paginated = null;
		try {
			// Simulate server being down for testing purposes
			if(!empty($this->request->getVar('ServerDown'))) {
				throw new Elastica\Exception\Connection\HttpException('Unable to reach search server');
			}

			// now actually perform the search using the original query
			$paginated = $es->search($queryText, $fieldsToSearch, $testMode);

			// This is the case of the original query having a better one suggested.  Do a
			// second search for the suggested query, throwing away the original
			if($es->hasSuggestedQuery() && !$ignoreSuggestions) {
				$data['SuggestedQuery'] = $es->getSuggestedQuery();
				$data['SuggestedQueryHighlighted'] = $es->getSuggestedQueryHighlighted();
				//Link for if the user really wants to try their original query
				$sifLink = rtrim($this->Link(), '/') . '?q=' . $queryText . '&is=1';
				$data['SearchInsteadForLink'] = $sifLink;
				$paginated = $es->search($es->getSuggestedQuery(), $fieldsToSearch);

			}

			// calculate time
			$endTime = microtime(true);
			$elapsed = round(100 * ($endTime - $startTime)) / 100;

			// store variables for the template to use
			$data['ElapsedTime'] = $elapsed;
			$this->Aggregations = $es->getAggregations();
			$data['SearchResults'] = $paginated;
			$data['SearchPerformed'] = true;
			$data['NumberOfResults'] = $paginated->getTotalItems();

		} catch (Elastica\Exception\Connection\HttpException $e) {
			$data['ErrorMessage'] = 'Unable to connect to search server';
			$data['SearchPerformed'] = false;
		}

		$data['OriginalQuery'] = $queryText;
		$data['IgnoreSuggestions'] = $ignoreSuggestions;

		if($this->has_extension('PageControllerTemplateOverrideExtension')) {
			return $this->useTemplateOverride($data);
		} else {
			return $data;
		}
	}



	/*
	Return true if the query is not empty
	 */
	public function QueryIsEmpty() {
		return empty($this->request->getVar('q'));
	}


	/**
	 * Process submission of the search form, redirecting to a URL that will render search results
	 * @param  array $data form data
	 * @param  Form $form form
	 */
	public function submit($data, $form) {
		$queryText = $data['q'];
		$url = $this->Link();
		$url = rtrim($url, '/');
		$link = rtrim($url, '/') . '?q=' . $queryText . '&sfid=' . $data['identifier'];
		$this->redirect($link);
	}

	/*
	Obtain an instance of the form
	*/

	public function SearchForm() {
		$form = new ElasticSearchForm($this, 'SearchForm');
		$fields = $form->Fields();
		$ep = Controller::curr()->dataRecord;
		$identifierField = new HiddenField('identifier');
		$identifierField->setValue($ep->Identifier);
		$fields->push($identifierField);
		$queryField = $fields->fieldByName('q');

		 if($this->isParamSet('q') && $this->isParamSet('sfid')) {
		 	$sfid = $this->request->getVar('sfid');
			if($sfid == $ep->Identifier) {
				$queryText = $this->request->getVar('q');
				$queryField->setValue($queryText);
			}

		}

		if($this->action == 'similar') {
			$queryField->setDisabled(true);
			$actions = $form->Actions();

			if(!empty($actions)) {
				foreach($actions as $field) {
					$field->setDisabled(true);
				}
			}

		}

		ElasticaUtil::addAutocompleteToQueryField(
			$queryField,
			$this->ClassesToSearch,
			$this->SiteTreeOnly,
			$this->Link(),
			$this->AutocompleteFunction()->Slug
		);
		return $form;
	}


	/**
	 * @param string $paramName
	 */
	private function isParamSet($paramName) {
		return !empty($this->request->getVar($paramName));
	}

}
