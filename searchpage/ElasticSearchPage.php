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
		'SiteTreeOnly' => 'Boolean'
	);

	private static $has_many = array('SearchableFields' => 'ElasticSearchPageSearchField');

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

		$config = GridFieldConfig_RecordEditor::create();
		$config->getComponentByType('GridFieldDataColumns')->setDisplayFields(array(
            'Name' => 'Name','Weight' => 'Weighting', 'HumanReadableSearchable' => 'Searchable'
        ));

        $config->removeComponent($config->getComponentByType('GridFieldAddNewButton'));
		$config->removeComponent($config->getComponentByType('GridFieldDeleteAction'));

		$filter = array('ElasticSearchPageID' => $this->ID, 'Active' => true);
		$searchFields = ElasticSearchPageSearchField::get()->filter($filter)->sort('Name');

        $gridField = new GridField(
            'ElasticSearchPageSearchField', // Field name
            'Field to Search', // Field title
            $searchFields,
            $config
        );

        $fields->addFieldToTab('Root.SearchDetails', $gridField);

        $html = '<p id="SearchFieldsMessage" class="warning message" style="display:none;">'.
        _t('Elastica.SEARCH_FIELDS_WILL_APPEAR_AFTER_SAVE',
        		'Search fields available will reappear after this page has been saved').'</p>';
        $messageField = new LiteralField('SearchFieldsMessage',$html);
        $messageField->addExtraClass('message warning');
        $fields->addFieldToTab('Root.SearchDetails', $messageField);


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

		$nameToMapping = QueryGenerator::getSearchFieldsMappingForClasses($this->ClassesToSearch);

		$names = array();
		foreach (array_keys($nameToMapping) as $name) {
			$type = $nameToMapping[$name];
			array_push($names, "'".$name."'");
			$filter = array('Name' => $name, 'ElasticSearchPageID' => $this->ID);
			$esf = ElasticSearchPageSearchField::get()->filter($filter)->first();
			if (!$esf) {
				$esf = new ElasticSearchPageSearchField();
				$esf->Name = $name;
				$esf->Type = $type;
				$esf->ElasticSearchPageID = $this->ID;
				$esf->write();
			}
		}

		$relevantNames = implode(',', $names);

		if (sizeof($names) > 0) {
			$sql = "UPDATE ElasticSearchPageSearchField SET Active = 'false' WHERE ";
			$sql .= "Name NOT IN ($relevantNames) AND ElasticSearchPageID={$this->ID};";
			DB::query($sql);

			$sql = "UPDATE ElasticSearchPageSearchField SET Active = 'true' WHERE ";
			$sql .= "Name IN ($relevantNames) AND ElasticSearchPageID={$this->ID};";
			DB::query($sql);
		}

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
}


class ElasticSearchPage_Controller extends Page_Controller {

	private static $allowed_actions = array('SearchForm', 'submit');

	public function init() {
		parent::init();
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

		echo "PAGE LENGTH:".$ep->ResultsPerPage."\n";

		// query string
		$q = '';
		if (isset($_GET['q'])) {
			$q = $_GET['q'];
		}

		// filters for aggregations
		$ignore = array('url', 'start','q');
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
		$es->setQueryResultManipulator($this->SearchHelper);

		// get the edited fields to search from the database for this search page
		// Convert this into a name => weighting array
		$fieldsToSearch = array();
		$editedSearchFields = ElasticSearchPageSearchField::get()->filter(array(
			'ElasticSearchPageID' => $this->ID, 'Active' => true, 'Searchable' => true));

		foreach ($editedSearchFields->getIterator() as $searchField) {
			$fieldsToSearch[$searchField->Name] = $searchField->Weight;
		}

		// now actually perform the search using the original query
		$paginated = $es->search($q, $fieldsToSearch);

		// calculate time
		$endTime = microtime(true);
		$elapsed = round(100*($endTime-$startTime))/100;

		// store variables for the template to use
		$data['ElapsedTime'] = $elapsed;
		$this->Aggregations = $es->getAggregations();
		$data['SearchResults'] = $paginated;
		$data['Elapsed'] = $elapsed;
		$data['SearchPerformed'] = true;

		// allow the optional use of overriding the search result page, e.g. for photos, maps or facets
		if ($this->hasExtension('PageControllerTemplateOverrideExtension')) {
			return $this->useTemplateOverride($data);
		} else {
			return $data;
		}
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
		return new ElasticSearchForm($this, 'SearchForm');
	}

}
