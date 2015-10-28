<?php

/**
 * @package elastica
 * @subpackage tests
 */
class SearchableTestFatherPage extends SearchableTestPage {
	private static $searchable_fields = array('FatherText');

	private static $db = array(
		'FatherText' => 'Varchar(255)'
	);
}

/**
 * @package elastica
 * @subpackage tests
 */
class SearchableTestFatherPage_Controller extends SearchableTestPage_Controller {
}

