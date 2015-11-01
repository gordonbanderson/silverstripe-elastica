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
use \SilverStripe\Elastica\QueryGenerator;
use \SilverStripe\Elastica\ElasticaUtil;

//FIXME namespace


class ElasticSearchPage extends Page {
	static $defaults = array(
		'ShowInMenus' => 0,
		'ShowInSearch' => 0,
		'ClassesToSearch' => '',
		'ResultsPerPage' => 10,
		'SiteTreeOnly' => true
	);

	private static $db = array(
		'ClassesToSearch' => 'Text',
		// unique identifier used to find correct search page for results
		// e.g. a separate search page for blog, pictures etc
		'Identifier' => 'Varchar',
		'ResultsPerPage' => 'Int',
		'SearchHelper' => 'Varchar',
		'SiteTreeOnly' => 'Boolean',
		'ContentForEmptySearch' => 'HTMLText'
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


	/*
	Add a tab with details of what to search
	 */
	function getCMSFields() {
		Requirements::javascript('elastica/javascript/elasticaedit.js');
		$fields = parent::getCMSFields();

		$fields->addFieldToTab('Root.SearchDetails', new CheckboxField('SiteTreeOnly', 'Show search results for all SiteTree objects only'));
		$fields->addFieldToTab('Root.SearchDetails', new TextField('ClassesToSearch'));
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
		$fields->addFieldToTab('Root.SearchDetails', $infoField);

		$fields->addFieldToTab('Root.Main', new HTMLEditorField('ContentForEmptySearch'));



		$identifierField = new TextField('Identifier',
			'Identifier to allow this page to be found in form templates');
		$fields->addFieldToTab('Root.SearchDetails', new NumericField('ResultsPerPage',
											'The number of results to return on a page'));
		$fields->addFieldToTab('Root.SearchDetails', new TextField('SearchHelper',
			'ClassName of object to manipulate search details and results.  Leave blank for standard search'));

		$fields->addFieldToTab('Root.Main', $identifierField, 'Content');

		//$searchTabName = 'Root.'._t('SiteConfig.ELASTICA', 'Search');
		$html = '<p id="SearchFieldIntro">'._t('SiteConfig.ELASTICA_SEARCH_INFO',
				"Select a field to edit it's properties").'</p>';
		$fields->addFieldToTab('Root.SearchDetails', $h1=new LiteralField('SearchInfo', $html));

		$searchPicker = new PickerField('ElasticaSearchableFields', 'Searchable Fields',
			$this->ElasticaSearchableFields()->sort('Name')); //, 'Select Owner(s)', 'SortOrder');

		$fields->addFieldToTab('Root.SearchDetails', $searchPicker);

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
			TextField::create('ManyMany[Weight]', 'Weighting'),
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
			'Weight' => 'Weighting',
			'EnableAutocomplete' => 'Enable Autocomplete?'
        ));




/*
        //Based on http://www.silverstripe.org/community/forums/data-model-questions/show/21178



		$config->addComponent($edittest);
		$config->addComponent($summaryfieldsconf, new GridFieldFilterHeader());

		$activeFields = $this->ElasticaSearchableFields()->filter('Active', true);
		$field = GridField::create('ElasticaSearchableFields', null, $activeFields, $config);
		$fields->addFieldToTab('Root.SearchDetails', $field);
*/
		return $fields;
	}


	/**
	 * Avoid duplicate identifiers
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
		return $result;
	}


	public function onAfterWrite() {
		// ClassesToSearch, SiteTreeOnly
		// FIXME - move to a separate testable method and call at build time also
		$nameToMapping = QueryGenerator::getSearchFieldsMappingForClasses($this->ClassesToSearch);

		$names = array_keys($nameToMapping);


		#FIXME - deal with empty case and also SiteTree only
		$relevantClasses = $this->ClassesToSearch;
		$quotedClasses = QueryGenerator::convertToQuotedCSV($relevantClasses);
		$quotedNames = QueryGenerator::convertToQuotedCSV($names);

		$where = "Name in ($quotedNames) AND ClazzName IN ($quotedClasses)";


		// Get the searchfields for the ClassNames searched
		$sfs = SearchableField::get()->where($where);
		$activeIDs = array_keys($sfs->map()->toArray());
		$activeIDs = implode(',', $activeIDs);

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
		}

		//FIXME deal with the no classes case #ZZZZ

		// Mark all the fields for this page as inactive/active as appropriate
		$sql = "UPDATE ElasticSearchPage_ElasticaSearchableFields SET ACTIVE=0 WHERE ";
		$sql .= "ElasticSearchPageID={$this->ID} AND SearchableFieldID NOT IN (";
		$sql .= "$activeIDs)";
		DB::query($sql);

		$sql = "UPDATE ElasticSearchPage_ElasticaSearchableFields SET ACTIVE=1 WHERE ";
		$sql .= "ElasticSearchPageID={$this->ID} AND SearchableFieldID IN (";
		$sql .= "$activeIDs)";
		DB::query($sql);







		/*
Array
(
    [SeriesTitle] =&gt; string
    [Episode Title] =&gt; string
    [Description] =&gt; string
    [Title] =&gt; string
    [Content] =&gt; string
)

		 foreach (array_keys($nameToMapping) as $name) {
			$type = $nameToMapping[$name];
			array_push($names, "'".$name."'");
			$filter = array('Name' => $name, 'ElasticSearchPageID' => $this->ID);
			//FIXME model changed
			$esf = ElasticSearchPageSearchField::get()->filter($filter)->first();
			if (!$esf) {
							//FIXME model changed

				$esf = new ElasticSearchPageSearchField();
				$esf->Name = $name;
				$esf->Type = $type;
				$esf->ElasticSearchPageID = $this->ID;
				$esf->write();
			}
		}

		$relevantNames = implode(',', $names);



		*/
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


	public function getHumanReadableEnableAutoComplete() {
		return ElasticaUtil::showBooleanHumanReadable($this->EnableAutocomplete);
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
		$paginated = $es->moreLikeThis($instance, array($fieldsToSearch));

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


		$terms = new ArrayList();
		foreach ($moreLikeThisTerms as $key => $term) {
			$fieldTerms = $moreLikeThisTerms[$key];
			foreach ($fieldTerms as $value) {
				$do = new DataObject();
				$do->Value = $value;
				$terms->push($do);
			}
		}


		$data['SimilarSearchTerms'] = $terms;

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

		// now actually perform the search using the original query
		$paginated = $es->search($q, $fieldsToSearch);

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
		$data['Elapsed'] = $elapsed;
		$data['SearchPerformed'] = true;
		$data['NumberOfResults'] = $paginated->getTotalItems();
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
				echo $field->getName();
				$field->setDisabled(true);
			}
		} else {
			$q->setAttribute('data-autocomplete', 'true');
			$q->setAttribute('data-autocomplete-field', 'Title');
			$q->setAttribute('data-autocomplete-classes', $this->ClassesToSearch);

		}



		return $form;
	}

}
