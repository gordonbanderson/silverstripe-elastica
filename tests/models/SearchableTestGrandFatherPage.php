<?php

/**
 * @package elastica
 * @subpackage tests
 */
class SearchableTestGrandFatherPage extends SearchableTestFatherPage implements TestOnly {
	private static $searchable_fields = array('GrandFather');

	private static $db = array(
		'GrandFather' => 'Varchar(255)'
	);
}

/**
 * @package elastica
 * @subpackage tests
 */
class SearchableTestGrandFatherPage_Controller extends SearchableTestFatherPage_Controller implements TestOnly {
}

