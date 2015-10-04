<?php

/**
 * @package elastica
 * @subpackage tests
 */
class SearchableTestFatherPage extends SearchableTestPage {
	private static $searchable_fields = array('Father');

	private static $db = array(
		'Father' => 'Varchar(255)'
	);
}

/**
 * @package elastica
 * @subpackage tests
 */
class SearchableTestFatherPage_Controller extends SearchableTestPage_Controller {
}

