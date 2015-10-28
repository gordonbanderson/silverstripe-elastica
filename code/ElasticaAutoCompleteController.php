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

		error_log('QUERY:'.$query);

		// start, and page length, i.e. pagination
		$es->setPageLength(10);
		$es->setClasses(array('FlickrPhoto'));
		$resultList = $es->autocomplete_search($query, array('Title' => 1));
		$result = array();
		$result['Query'] = $query;
		$suggestions = array();
		foreach ($resultList->getResults() as $singleResult) {
			$suggestion = array('value' => $singleResult->Title);
			$suggestion['data'] = $singleResult->ID;
			array_push($suggestions, $suggestion);
		}

		$result['suggestions'] = $suggestions;
		echo json_encode($result);
		die;
	}
}