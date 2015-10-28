<?php

/**
 * @package elastica
 * @subpackage tests
 */
class SearchableTestGrandFatherPage extends SearchableTestFatherPage implements TestOnly {
	private static $searchable_fields = array('GrandFatherText');

	private static $db = array(
		'GrandFatherText' => 'Varchar(255)'
	);
}

/**
 * @package elastica
 * @subpackage tests
 */
class SearchableTestGrandFatherPage_Controller extends SearchableTestFatherPage_Controller implements TestOnly {
}

