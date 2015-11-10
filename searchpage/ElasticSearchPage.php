<?php
/**
* Only show a page with login when not logged in
*/
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

//FIXME namespace


class ElasticSearchPage extends Page {
	private static $defaults = array(
		'ShowInMenus' => 0,
		'ShowInSearch' => 0,
		'ClassesToSearch' => '',
		'ResultsPerPage' => 10,
		'SiteTreeOnly' => true,
		'MinTermFreq' => 2,
		'MaxTermFreq' => 25,
		'MinWordLength' => 3,
		'MinDocFreq' => 2,
		'MaxDocFreq' => 0,
		'MinWordLength' => 0,
		'MaxWordLength' => 0,
		'MinShouldMatch' => '30%'
	);

	private static $db = array(
		'ClassesToSearch' => 'Text',
		// unique identifier used to find correct search page for results
		// e.g. a separate search page for blog, pictures etc
		'Identifier' => 'Varchar',
		'ResultsPerPage' => 'Int',
		'SearchHelper' => 'Varchar',
		'SiteTreeOnly' => 'Boolean',
		'ContentForEmptySearch' => 'HTMLText',
		'MinTermFreq' => 'Int',
		'MaxTermFreq' => 'Int',
		'MinWordLength' => 'Int',
		'MinDocFreq' => 'Int',
		'MaxDocFreq' => 'Int',
		'MinWordLength' => 'Int',
		'MaxWordLength' => 'Int',
		'MinShouldMatch' => 'Varchar'
	);

	private static $many_many = array(
		'ElasticaSearchableFields' => 'SearchableField'
	);

	private static $many_many_extraFields = array(
    	'ElasticaSearchableFields' => array(
		'Searchable' => 'Boolean', // allows the option of turning off a single field for searching
		'SimilarSearchable' => 'Boolean', // allows field to be used in more like this queries.
		'Active' => 'Boolean', // preserve previous edits of weighting when classes changed
		'EnableAutocomplete' => 'Boolean', // whether or not to show autocomplete search for this field
		'Weight' => 'Int' // Weight to apply to this field in a search
		)
  	);


  	private static $has_one = array(
  		'AutoCompleteFunction' => 'AutoCompleteOption',
  		'AutoCompleteField' => 'SearchableField'
  	);


	/*
	Add a tab with details of what to search
	 */
	function getCMSFields() {
		Requirements::javascript('elastica/javascript/elasticaedit.js');
		$fields = parent::getCMSFields();


		$fields->addFieldToTab("Root", new TabSet('Search',
			new Tab('SearchFor'),
			new Tab('Identifier'),
			new Tab('Fields'),
			new Tab('AutoComplete'),
			new Tab('Aggregations'),
			new Tab('Similarity')
		));

		// ---- similarity tab ----
		$html = '<button class="ui-button-text-alternate ui-button-text"
		id="MoreLikeThisDefaultsButton"
		style="display: block;float: right;">Restore Defaults</button>';
		$defaultsButton = new LiteralField('DefaultsButton', $html);
				$fields->addFieldToTab("Root.Search.Similarity", $defaultsButton);

		$lf = new LiteralField('SimilarityNotes', _t('Elastica.SIMILARITY_NOTES',
			'Default values are those used by Elastica'));
		$fields->addFieldToTab("Root.Search.Similarity", $lf);
		$fields->addFieldToTab("Root.Search.Similarity", new TextField('MinTermFreq',
			'The minimum term frequency below which the terms will be ignored from the input '.
			'document. Defaults to 2.'));
		$fields->addFieldToTab("Root.Search.Similarity", new TextField('MaxTermFreq',
			'The maximum number of query terms that will be selected. Increasing this value gives '.
			'greater accuracy at the expense of query execution speed. Defaults to 25.'));
		$fields->addFieldToTab("Root.Search.Similarity", new TextField('MinWordLength',
			'The minimum word length below which the terms will be ignored.  Defaults to 0.'));
		$fields->addFieldToTab("Root.Search.Similarity", new TextField('MinDocFreq',
			'The minimum document frequency below which the terms will be ignored from the input '.
			'document. Defaults to 5.'));
		$fields->addFieldToTab("Root.Search.Similarity", new TextField('MaxDocFreq',
			'The maximum document frequency above which the terms will be ignored from the input '.
			'document. This could be useful in order to ignore highly frequent words such as stop '.
			'words. Defaults to unbounded (0).'));
		$fields->addFieldToTab("Root.Search.Similarity", new TextField('MinWordLength',
			'The minimum word length below which the terms will be ignored. The old name min_'.
			'word_len is deprecated. Defaults to 0.'));
		$fields->addFieldToTab("Root.Search.Similarity", new TextField('MaxWordLength',
			'The maximum word length above which the terms will be ignored. The old name max_word_'.
			'len is deprecated. Defaults to unbounded (0).'));
		$fields->addFieldToTab("Root.Search.Similarity", new TextField('MinShouldMatch',
			'This parameter controls the number of terms that must match. This can be either a '.
			'number or a percentage.  See '.
			'https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-minimum-should-match.html'));

		// ---- search details tab ----
		$identifierField = new TextField('Identifier',
			'Identifier to allow this page to be found in form templates');
		$fields->addFieldToTab('Root.Search.Identifier', $identifierField);


		$fields->addFieldToTab('Root.Search.SearchFor', new CheckboxField('SiteTreeOnly', 'Show search results for all SiteTree objects only'));
		$fields->addFieldToTab('Root.Search.SearchFor', new TextField('ClassesToSearch'));
		$sql = "SELECT DISTINCT ClassName from SiteTree_Live UNION "
			 . "SELECT DISTINCT ClassName from SiteTree "
			 . "WHERE ClassName != 'ErrorPage'"
			 . "ORDER BY ClassName"
		;

		$classes = array();
		$records = DB::query($sql);
		foreach ($records as $record) {
			array_push($classes, $record['ClassName']);
		}
		$list = implode(',', $classes);
		$html = '<div class="field text" id="SiteTreeOnlyInfo">';
		$html .= "<p>Copy the following into the above field to ensure that all SiteTree classes are searched</p>";
		$html .= '<p class="message">'.$list;
		$html .= "</p></div>";
		$infoField = new LiteralField('InfoField',$html);
		$fields->addFieldToTab('Root.Search.SearchFor', $infoField);

		$fields->addFieldToTab('Root.Main', new HTMLEditorField('ContentForEmptySearch'));


			$fields->addFieldToTab('Root.Search.SearchFor', new NumericField('ResultsPerPage',
											'The number of results to return on a page'));
		$fields->addFieldToTab('Root.Search.Aggregations', new TextField('SearchHelper',
			'ClassName of object to manipulate search details and results.  Leave blank for standard search'));







        $ottos = AutoCompleteOption::get()->Filter('Locale', $this->Locale)->map('ID', 'Name')->
        									toArray();
        $df = DropdownField::create('AutoCompleteFunctionID', 'Autocomplete Function')->
        							setSource($ottos);
        $df->setEmptyString('-- Please select what do do after find as you type has occurred --');

        $ottos = $this->ElasticaSearchableFields()->filter('EnableAutocomplete',1)->Map('ID', 'Name')->toArray();
        $autoCompleteFieldDF = DropDownField::create('AutoCompleteFieldID', 'Field to use for autocomplete')->setSource($ottos);
        $autoCompleteFieldDF->setEmptyString('-- Please select which field to use for autocomplete --');

        //$fieldSet = new \FieldSet($df);
        //$fields->addFieldToTab('Root.SearchDetails', $fieldSet);

		$fields->addFieldToTab("Root.Search.AutoComplete",
		  		FieldGroup::create(
		  			$autoCompleteFieldDF,
		 			$df
		 		)->setTitle('Autocomplete')
		 );
		        // ---- grid of searchable fields ----
        		//$searchTabName = 'Root.'._t('SiteConfig.ELASTICA', 'Search');
		$html = '<p id="SearchFieldIntro">'._t('SiteConfig.ELASTICA_SEARCH_INFO',
				"Select a field to edit it's properties").'</p>';
		$fields->addFieldToTab('Root.Search.Fields', $h1=new LiteralField('SearchInfo', $html));
		$searchPicker = new PickerField('ElasticaSearchableFields', 'Searchable Fields',
			$this->ElasticaSearchableFields()->filter('Active', 1)->sort('Name')); //, 'Select Owner(s)', 'SortOrder');

		$fields->addFieldToTab('Root.Search.Fields', $searchPicker);

		$pickerConfig = $searchPicker->getConfig();

		$pickerConfig->removeComponentsByType(new GridFieldAddNewButton());
		$pickerConfig->removeComponentsByType(new GridFieldDeleteAction());
		$pickerConfig->removeComponentsByType(new PickerFieldAddExistingSearchButton());

		$pickerConfig->getComponentByType('GridFieldPaginator')->setItemsPerPage(100);


        $searchPicker->enableEdit();
		$edittest = $pickerConfig->getComponentByType('GridFieldDetailForm');
		$edittest->setFields(FieldList::create(
			TextField::create('Name', 'Field Name'),
			TextField::create('ClazzName', 'Class'),
			HiddenField::create('Autocomplete', 'This can be autocompleted'),
			CheckboxField::create('ManyMany[Searchable]', 'Use for normal searching'),
			CheckboxField::create('ManyMany[SimilarSearchable]', 'Use for similar search'),
			NumericField::create('ManyMany[Weight]', 'Weighting'),
			CheckboxField::create('ShowHighlights', 'Show highlights from search in results for this field'),
			CheckboxField::create('ManyMany[EnableAutocomplete]', 'Enable Autocomplete')
		));


		$edittest->setItemEditFormCallback(function($form) {
			error_log($form->ID);
			// Get the image field from the form fields
			$fields = $form->Fields();

			$fieldAutocomplete = $fields->dataFieldByName('Autocomplete');
			$fieldEnableAutcomplete = $fields->dataFieldByName('ManyMany[EnableAutocomplete]');

			$fields->dataFieldByName('ClazzName')->setReadOnly(true);
			$fields->dataFieldByName('ClazzName')->setDisabled(true);
			$fields->dataFieldByName('Name')->setReadOnly(true);
			$fields->dataFieldByName('Name')->setDisabled(true);

			if (!$fieldAutocomplete->Value() == '1') {
				// Set the folder name, easy!
				$fieldEnableAutcomplete->setDisabled(true);
				$fieldEnableAutcomplete->setTitle("Autcomplete is not available for this field");
			}

		});


		// What do display on the grid of searchable fields
		$dataColumns = $pickerConfig->getComponentByType('GridFieldDataColumns');
        $dataColumns->setDisplayFields(array(
			'Name' => 'Name',
        	'ClazzName' => 'Class',
			'Type' => 'Type',
			'Searchable' => 'Use for Search?',
			'SimilarSearchable' => 'Use for Similar Search?',
			'ShowHighlights' => 'Show Search Highlights',
			'Weight' => 'Weighting'
        ));



		return $fields;
	}


	public function getCMSValidator() {
        return new ElasticSearchPage_Validator();
    }


	/**
	 * Avoid duplicate identifiers, and check that ClassesToSearch actually exist and are Searchable
	 * @return DataObject result with or without error
	 */
	public function validate() {
		$result = parent::validate();
		$mode = Versioned::get_reading_mode();
		$suffix =  '';
		if ($mode == 'Stage.Live') {
			$suffix = '_Live';
		}

		$where = 'ElasticSearchPage'.$suffix.'.ID != '.$this->ID." AND `Identifier` = '{$this->Identifier}'";
		$existing = ElasticSearchPage::get()->where($where)->count();
		if ($existing > 0) {
			$result->error('The identifier '.$this->Identifier.' already exists');
		}


		error_log('CTS:'.$this->ClassesToSearch);

		// now check classes to search actually exist, assuming in site tree not set
		error_log('STO:'.$this->SiteTreeOnly);
		if (!$this->SiteTreeOnly) {
			if ($this->ClassesToSearch == '') {
				$result->error('At least one searchable class must be available, or SiteTreeOnly flag set');
			} else {
				$toSearch = explode(',', $this->ClassesToSearch);
				foreach ($toSearch as $clazz) {
					try {
						$instance = Injector::inst()->create($clazz);
						if (!$instance->hasExtension('SilverStripe\Elastica\Searchable')) {
							$result->error('The class '.$clazz.' must have the Searchable extension');
						}
					} catch (ReflectionException $e) {
						$result->error('The class '.$clazz.' does not exist');
					}
				}
			}
		}

		return $result;
	}


	public function onAfterWrite() {
		// FIXME - move to a separate testable method and call at build time also
		$nameToMapping = QueryGenerator::getSearchFieldsMappingForClasses($this->ClassesToSearch);
		$names = array_keys($nameToMapping);

		#FIXME -  SiteTree only
		$relevantClasses = $this->ClassesToSearch; // due to validation this will be valid
		if ($this->SiteTreeOnly) {
			$relevantClasses = SearchableClass::get()->filter('InSiteTree', true)->Map('Name')->toArray();

		}
		$quotedClasses = QueryGenerator::convertToQuotedCSV($relevantClasses);
		$quotedNames = QueryGenerator::convertToQuotedCSV($names);

		$where = "Name in ($quotedNames) AND ClazzName IN ($quotedClasses)";

		// Get the searchfields for the ClassNames searched
		$sfs = SearchableField::get()->where($where);


		// Get the searchable fields associated with this search page
		$esfs = $this->ElasticaSearchableFields();

		// Remove existing searchable fields for this page from the list of all available
    	$delta = array_keys($esfs->map()->toArray());
		$newSearchableFields = $sfs->exclude('ID', $delta);

		foreach ($newSearchableFields->getIterator() as $newSearchableField) {
			error_log('NEW FIELD:'.$newSearchableField->Name);
			$newSearchableField->Active = true;
			$newSearchableField->Weight = 1;

			$esfs->add($newSearchableField);

			// Note 1 used instead of true for SQLite3 testing compatibility
			$sql = "UPDATE ElasticSearchPage_ElasticaSearchableFields SET ";
			$sql .= 'Active=1, Weight=1 WHERE ElasticSearchPageID = '.$this->ID;
			DB::query($sql);
		}

		// Mark all the fields for this page as inactive initially
		$sql = "UPDATE ElasticSearchPage_ElasticaSearchableFields SET ACTIVE=0 WHERE ";
		$sql .= "ElasticSearchPageID={$this->ID}";
		DB::query($sql);

		$activeIDs = array_keys($sfs->map()->toArray());
		$activeIDs = implode(',', $activeIDs);

		//Mark as active the relevant ones
		$sql = "UPDATE ElasticSearchPage_ElasticaSearchableFields SET ACTIVE=1 WHERE ";
		$sql .= "ElasticSearchPageID={$this->ID} AND SearchableFieldID IN (";
		$sql .= "$activeIDs)";
		DB::query($sql);
	}


	/*
	Obtain an instance of the form - this is need for rendering the search box
	*/
	public function SearchForm($buttonTextOverride = null) {
		$result = new ElasticSearchForm($this, 'SearchForm');
		if ($buttonTextOverride) {
			$result->setButtonText($buttonTextOverride);
		}
		return $result;
	}


	/*
	If a manipulator object is set, assume aggregations are present.  Used to add the column
	for aggregates
	 */
	public function HasAggregations() {
		return $this->SearchHelper != null;
	}

}


class ElasticSearchPage_Controller extends Page_Controller {

	private static $allowed_actions = array('SearchForm', 'submit','index','similar');

	public function init() {
		parent::init();

		Requirements::javascript(THIRDPARTY_DIR . '/jquery/jquery.js');
		Requirements::javascript("elastica/javascript/jquery.autocomplete.js");
		Requirements::javascript("elastica/javascript/elastica.js");
        Requirements::css("elastica/css/elastica.css");
	}



	public function similar() {
		//FIXME double check security, ie if escaping needed
		$class = $this->request->param('ID');
		$instanceID = $this->request->param('OtherID');

		$searchable = Injector::inst()->create($class);

		//FIXME better ways to do this #sleepyOClock
		if (!$searchable->hasMethod('getElasticaFields')) {
			throw new Exception($class.' is not searchable');
		}

		$instance = DataObject::get_by_id($class,$instanceID);

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


		//May not work
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


		//FIXME may not work
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
			'SimilarSearchable' => true
		));

		foreach ($editedSearchFields->getIterator() as $searchField) {
			$fieldsToSearch[$searchField->Name] = $searchField->Weight;
		}

		// Use the standard field for more like this, ie not stemmed
		$standardFields = array();
		foreach ($fieldsToSearch as $field => $value) {
			$fieldsToSearch[$field.'.standard'] = $value;

			//Experiment here with other fields to ad to similarity searching
			//$fieldsToSearch[$field.'.shingles'] = $value;
			//$fieldsToSearch[$field.'.autocomplete'] = $value;
			unset($fieldsToSearch[$field]);
		}

		//$paginated = $es->moreLikeThis($instance, array($fieldsToSearch));
		$paginated = $es->moreLikeThis($instance, $fieldsToSearch);

		// calculate time
		$endTime = microtime(true);
		$elapsed = round(100*($endTime-$startTime))/100;

		// store variables for the template to use
		$data['ElapsedTime'] = $elapsed;
		$this->Aggregations = $es->getAggregations();
		$data['SearchResults'] = $paginated;
		$data['Elapsed'] = $elapsed;
		$data['SearchPerformed'] = true;
		$data['SearchPageLink'] = $ep->Link();
		$data['SimilarTo'] = $instance;
		$data['NumberOfResults'] = $paginated->getTotalItems();


		$moreLikeThisTerms = $paginated->getList()->MoreLikeThisTerms;

		//print_r($moreLikeThisTerms);
		//die;

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

/*
		$terms = new ArrayList();
		foreach ($moreLikeThisTerms as $key => $term) {
			$fieldTerms = $moreLikeThisTerms[$key];
			foreach ($fieldTerms as $value) {
				$do = new DataObject();
				$do->Value = $value;
				$terms->push($do);
			}
		}
*/


		$data['SimilarSearchTerms'] = $fieldToTerms;

		//Add a 'similar' link to each of the results
		$link = $this->Link();

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
		$q = '';
		if (isset($_GET['q'])) {
			$q = $_GET['q'];
		}

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
			// now actually perform the search using the original query
			$paginated = $es->search($q, $fieldsToSearch);

			// This is the case of the original query having a better one suggested.  Do a
			// second search for the suggested query, throwing away the original
			if ($es->hasSuggestedQuery() && !$ignoreSuggestions) {
				$data['SuggestedQuery'] = $es->getSuggestedQuery();
				$data['SuggestedQueryHighlighted'] = $es->getSuggestedQueryHighlighted();
				//Link for if the user really wants to try their original query
				$sifLink = rtrim($this->Link(),'/').'?q='.$q.'&is=1';
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

		$data['OriginalQuery'] = $q;
		$data['IgnoreSuggestions'] = $ignoreSuggestions;






		// allow the optional use of overriding the search result page, e.g. for photos, maps or facets
		if ($this->hasExtension('PageControllerTemplateOverrideExtension')) {
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
			$q = $_GET['q'];
			if ($q == '') {
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
		$query = $data['q'];

		$url = $this->Link();
		$url = rtrim($url, '/');
		$link = rtrim($url, '/').'?q='.$query;
		$this->redirect($link);
	}

	/*
	Obtain an instance of the form
	*/
	public function SearchForm() {
		$form = new ElasticSearchForm($this, 'SearchForm');
		$fields = $form->Fields();
		$q = $fields->fieldByName('q');
		if($this->action == 'similar') {
			$q->setDisabled(true);
			$actions = $form->Actions();
			foreach ($actions as $field) {
				$field->setDisabled(true);
			}
		}

		/*
		A field needs to be chosen for autocompletion, if not no autocomplete
		 */
		if ($this->AutoCompleteFieldID > 0) {
			$q->setAttribute('data-autocomplete', 'true');
			$q->setAttribute('data-autocomplete-field', 'Title');
			$q->setAttribute('data-autocomplete-classes', $this->ClassesToSearch);
			$q->setAttribute('data-autocomplete-source',$this->Link());
			$q->setAttribute('data-autocomplete-function',
				$this->AutocompleteFunction()->Slug);
		}

		return $form;
	}

}
