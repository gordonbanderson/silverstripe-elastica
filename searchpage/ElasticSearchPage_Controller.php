<?php

class ElasticSearchPage_Controller extends Page_Controller {

	private static $allowed_actions = array('SearchForm', 'submit','index','similar');

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
		$start = isset($_GET['start']) ? (int)$_GET['start'] : 0;
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
		if ($ep->SiteTreeOnly) {
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

		foreach ($editedSearchFields->getIterator() as $searchField) {
			$fieldsToSearch[$searchField->Name] = $searchField->Weight;
		}

		// Use the standard field for more like this, ie not stemmed
		foreach ($fieldsToSearch as $field => $value) {
			$fieldsToSearch[$field.'.standard'] = $value;
			unset($fieldsToSearch[$field]);
		}

		try {
			// Simulate server being down for testing purposes
	        if (isset($_GET['ServerDown'])) {
	        	throw new Elastica\Exception\Connection\HttpException('Unable to reach search server');
	        }
			if (class_exists($class)) {
				$instance = \DataObject::get_by_id($class,$instanceID);

				$paginated = $es->moreLikeThis($instance, $fieldsToSearch);

				$this->Aggregations = $es->getAggregations();
				$data['SearchResults'] = $paginated;
				$data['SearchPerformed'] = true;
				$data['SearchPageLink'] = $ep->Link();
				$data['SimilarTo'] = $instance;
				$data['NumberOfResults'] = $paginated->getTotalItems();


				$moreLikeThisTerms = $paginated->getList()->MoreLikeThisTerms;
				$fieldToTerms = new ArrayList();
				foreach (array_keys($moreLikeThisTerms) as $fieldName) {
					$readableFieldName = str_replace('.standard', '', $fieldName);
					$fieldTerms = new ArrayList();
					foreach ($moreLikeThisTerms[$fieldName] as $value) {
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
		$elapsed = round(100*($endTime-$startTime))/100;

		// store variables for the template to use
		$data['ElapsedTime'] = $elapsed;
		$data['Elapsed'] = $elapsed;

		// allow the optional use of overriding the search result page, e.g. for photos, maps or facets
		if ($this->hasExtension('PageControllerTemplateOverrideExtension')) {
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
		$start = isset($_GET['start']) ? (int)$_GET['start'] : 0;
		$es->setStart($start);
		$es->setPageLength($ep->ResultsPerPage);


		// Do not show suggestions if this flag is set
		$ignoreSuggestions = isset($_GET['is']);


		// query string
		$queryText = '';
		if (isset($_GET['q'])) {
			$queryText = $_GET['q'];
		}

		$testMode = isset($_GET['TestMode']);

		// filters for aggregations
		$ignore = array('url', 'start','q','is');
		$ignore = \Config::inst()->get('Elastica', 'BlackList');
		foreach ($this->request->getVars() as $key => $value) {
			if (!in_array($key, $ignore)) {
				$es->addFilter($key,$value);
			}
		}

		// filter by class or site tree
		if ($ep->SiteTreeOnly) {
			$es->addFilter('IsInSiteTree', true);
		} else {
			$es->setClasses($ep->ClassesToSearch);
		}

		// set the optional aggregation manipulator
		// In the event of a manipulator being present, show all the results for search
		// Otherwise aggregations are all zero
		if ($this->SearchHelper) {
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

		foreach ($editedSearchFields->getIterator() as $searchField) {
			$fieldsToSearch[$searchField->Name] = $searchField->Weight;
		}

		$paginated = null;
		try {
			// Simulate server being down for testing purposes
	        if (isset($_GET['ServerDown'])) {
	        	throw new Elastica\Exception\Connection\HttpException('Unable to reach search server');
	        }

			// now actually perform the search using the original query
			$paginated = $es->search($queryText, $fieldsToSearch, $testMode);

			// This is the case of the original query having a better one suggested.  Do a
			// second search for the suggested query, throwing away the original
			if ($es->hasSuggestedQuery() && !$ignoreSuggestions) {
				$data['SuggestedQuery'] = $es->getSuggestedQuery();
				$data['SuggestedQueryHighlighted'] = $es->getSuggestedQueryHighlighted();
				//Link for if the user really wants to try their original query
				$sifLink = rtrim($this->Link(),'/').'?q='.$queryText.'&is=1';
				$data['SearchInsteadForLink'] = $sifLink;
				$paginated = $es->search($es->getSuggestedQuery(), $fieldsToSearch);

			}

			// calculate time
			$endTime = microtime(true);
			$elapsed = round(100*($endTime-$startTime))/100;

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

		if ($this->has_extension('PageControllerTemplateOverrideExtension')) {
			return $this->useTemplateOverride($data);
		} else {
			return $data;
		}
	}



	/*
	Return true if the query is not empty
	 */
	public function QueryIsEmpty() {
		$result = !isset($_GET['q']);
		if (isset($_GET['q']))	{
			$queryText = $_GET['q'];
			if ($queryText == '') {
				$result = true;
			}
		}
		return $result;
	}


	/**
	 * Process submission of the search form, redirecting to a URL that will render search results
	 * @param  [type] $data form data
	 * @param  [type] $form form
	 */
	public function submit($data, $form) {
		$queryText = $data['q'];
		$url = $this->Link();
		$url = rtrim($url, '/');
		$link = rtrim($url, '/').'?q='.$queryText.'&sfid='.$data['identifier'];
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

		 if (isset($_GET['q']) && isset($_GET['sfid'])) {
			if ($_GET['sfid'] == $ep->Identifier) {
				$queryField->setValue($_GET['q']);
			}

		}

		if($this->action == 'similar') {
			$queryField->setDisabled(true);
			$actions = $form->Actions();
			foreach ($actions as $field) {
				$field->setDisabled(true);
			}
		}

		/*
		A field needs to be chosen for autocompletion, if not no autocomplete
		 */
		if ($this->AutoCompleteFieldID > 0) {
			$queryField->setAttribute('data-autocomplete', 'true');
			$queryField->setAttribute('data-autocomplete-field', 'Title');
			$queryField->setAttribute('data-autocomplete-classes', $this->ClassesToSearch);
			$queryField->setAttribute('data-autocomplete-sitetree', $this->SiteTreeOnly);
			$queryField->setAttribute('data-autocomplete-source',$this->Link());
			$queryField->setAttribute('data-autocomplete-function',
			$this->AutocompleteFunction()->Slug);
		}

		return $form;
	}

}
