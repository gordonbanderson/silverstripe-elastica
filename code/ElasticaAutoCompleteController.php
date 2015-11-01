<?php
use \SilverStripe\Elastica\ElasticSearcher;

class ElasticaAutoCompleteController extends Controller {
	private static $url_handlers = array(
		'search' => 'search'
	);

	private static $allowed_actions = array('search');


/*
{
    // Query is not required as of version 1.2.5
    "query": "Unit",
    "suggestions": [
        { "value": "United Arab Emirates", "data": "AE" },
        { "value": "United Kingdom",       "data": "UK" },
        { "value": "United States",        "data": "US" }
    ]
}

 */
	public function search() {
		$es = new ElasticSearcher();
		$query = $this->request->getVar('query');
		$classes = $this->request->getVar('classes');

		// Makes most sense to only provide one field here, e.g. Title, Name
		$field = $this->request->getVar('field');

		error_log('QUERY:'.$query);

		// start, and page length, i.e. pagination
		$es->setPageLength(10);
		$es->setClasses($classes);
		$resultList = $es->autocomplete_search($query, array('Title' => 1));
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
		echo json_encode($result);
		die;
	}
}
