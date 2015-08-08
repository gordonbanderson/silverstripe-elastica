<?php
/**
* Only show a page with login when not logged in
*/
use Elastica\Document;
use Elastica\Query;
use \SilverStripe\Elastica\ResultList;
use Elastica\Query\QueryString;

class ElasticSearchPage extends Page {
		static $defaults = array(
			'ShowInMenus' => 0,
			'ShowInSearch' => 0,
			'ClassesToSearch' => '',
			'ResultsPerPage' => 10
		);

		private static $db = array(
			'ClassesToSearch' => 'Text',
			// unique identifier used to find correct search page for results
			// e.g. a separate search page for blog, pictures etc
			'Identifier' => 'Varchar',
			'ResultsPerPage' => 'Int',
			'QueryManipulator' => 'Varchar',
			'AggregationManipulator' => 'Varchar'
		);


		/*
		Add a tab with details of what to search
		 */
		function getCMSFields() {
			$fields = parent::getCMSFields();
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
			$html ="<p>Copy the following into the above field to ensure that all SiteTree classes are searched</p><pre>";
			$html .= $list;
			$html .= "</pre>";
			$infoField = new LiteralField('InfoField',$html);


			$fields->addFieldToTab('Root.SearchDetails', $infoField);
			$identifierField = new TextField('Identifier',
				'Identifier to allow this page to be found in form templates');
			$fields->addFieldToTab('Root.SearchDetails', new NumericField('ResultsPerPage',
												'The number of results to return on a page'));
			$fields->addFieldToTab('Root.SearchDetails', new TextField('QueryManipulator',
				'ClassName of query manipulator which can be used e.g. for aggregation. Leave blank for no effect.'));
			$fields->addFieldToTab('Root.SearchDetails', new TextField('AggregationManipulator',
				'ClassName of aggregation manipulator which can be used e.g. remapping keys. Leave blank for no effect.'));

			$fields->addFieldToTab('Root.Main', $identifierField, 'Content');
			return $fields;
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
		$searchResults = new ArrayList();

		$data = array(
			'Content' => $this->Content,
			'Title' => $this->Title,
			'SearchPerformed' => false
		);

		if (isset($_GET['q'])) {
			$startTime = microtime(true);
			$resultList = $this->searchResults();

			// set the optional aggregation manipulator
			$resultList->AggregationManipulator = $this->AggregationManipulator;

			// at this point ResultList object, not yet executed search query
			$searchResultsPaginated = new \PaginatedList(
				$resultList,
				\Controller::curr()->request
			);

			$searchResultsPaginated->setPageLength($this->ResultsPerPage);

			$endTime = microtime(true);

			$elapsed = round(100*($endTime-$startTime))/100;
			$data['ElapsedTime'] = $elapsed;

			$searchResultsPaginated->setTotalItems($resultList->getTotalItems());
			$this->Aggregations = $resultList->getAggregations();

			$data['SearchResults'] = $searchResultsPaginated;
			$data['Elapsed'] = $elapsed;
			$data['SearchPerformed'] = true;
		}

		// allow the optional use of overriding the search result page, e.g. for photos, maps or facets
		if ($this->hasExtension('PageControllerTemplateOverrideExtension')) {
			return $this->useTemplateOverride($data );
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
		$url = str_replace('/SearchForm', '?q=', $data['url']);
        $link = $url.$query;
        $this->redirect($link);
    }

    /*
    Obtain an instance of the form
     */
	public function SearchForm() {
       return new ElasticSearchForm($this, 'SearchForm');
    }

    /**
	 * Perform the search against Elastica return DataObjects, taking into account pagination
	 */
	private function searchResults(){
		//instance of ElasticPage associated with this controller
		$ep = Controller::curr()->dataRecord;

		$start = isset($_GET['start']) ? (int)$_GET['start'] : 0;
		$queryString = new QueryString($_GET['q']);
		$query = new Query($queryString);
		$query->setLimit($ep->ResultsPerPage);
		$query->setFrom($start);

		// this allows for the query to be altered, e.g. add aggregation
		if ($this->QueryManipulator) {
			$queryManipulator = Injector::inst()->create($this->QueryManipulator);
			$queryManipulator->augmentQuery($query);
		}

		$index = Injector::inst()->create('SilverStripe\Elastica\ElasticaService');
		$results = new ResultList($index, $query);
		$types = $ep->ClassesToSearch;
		$results->setTypes($types);
		return $results;
	}
}
