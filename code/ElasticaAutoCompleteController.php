<?php
use \SilverStripe\Elastica\ElasticSearcher;

class ElasticaAutoCompleteController extends Controller {
	private static $url_handlers = array(
		'search' => 'search'
	);


	private static $allowed_actions = array('search');


	public function search() {
		$es = new ElasticSearcher();
		$query = $this->request->getVar('query');
		$query = trim($query);
		$classes = $this->request->getVar('classes');
		$filter = $this->request->getVar('filter');

		// Makes most sense to only provide one field here, e.g. Title, Name
		$field = $this->request->getVar('field');

		error_log('QUERY:'.$query);

		// start, and page length, i.e. pagination
		$es->setPageLength(10);
		if ($classes) {
			$es->setClasses($classes);
		}

		if ($filter) {
			$es->addFilter('InSiteTree', true);
		}
		$resultList = $es->autocomplete_search($query, array($field => 1));
		$es->setClasses($classes);
		$resultList = $es->autocomplete_search($query,$field);
		$result = array();
		$result['Query'] = $query;
		$suggestions = array();

		foreach ($resultList->getResults() as $singleResult) {
			$suggestion = array('value' => $singleResult->Title);
			$suggestion['data'] = array(
				'ID' => $singleResult->getParam('_id'),
				'Class' => $singleResult->getParam('_type'),
				'Link' => $singleResult->Link
			);
			array_push($suggestions, $suggestion);
		}

		$result['suggestions'] = $suggestions;


		$json = json_encode($result);

		$this->response->addHeader('Content-Type', 'application/json');
		//$this->response->setBody($json);
		return $json;
	}
}
