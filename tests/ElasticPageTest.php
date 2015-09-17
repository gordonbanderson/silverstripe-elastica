<?php

/**
 * @package comments
 */
class ElasticPageTest extends FunctionalTest {

	public static $fixture_file = 'elastica/tests/ElasticaTest.yml';

	protected $extraDataObjects = array(
		'SearchableTestPage'
	);

	public function setUp() {
		// this needs to be called in order to create the list of searchable
		// classes and fields that are available.  Simulates part of a build
		$this->requireDefaultRecordsFrom = array('SearchableTestPage','SiteTree','Page');

		// add Searchable extension where appropriate
		SearchableTestPage::add_extension('SilverStripe\Elastica\Searchable');

		// load fixtures
		parent::setUp();
	}


	/*
	Test that during the build process, requireDefaultRecords creates records for
	each unique field name declared in searchable_fields
	 */
	public function testSearchableFieldsCreatedAtBuildTime() {
		$searchableTestPage = $this->objFromFixture('SearchableTestPage', 'first');
		$searchPage = $this->objFromFixture('ElasticSearchPage', 'search');

		// expected mapping of searchable classes to searchable fields that will be
		// stored in the database as SearchableClass and SearchableField
		$expected = array(
			'Page' => array('Title','Content'),
			'SiteTree' => array('Title','Content'),
			'SearchableTestPage' => array('Title','Content','Country','PageDate'),
		);

		// check the expected classes
		$expectedClasses = array_keys($expected);
		$nSearchableClasses = SearchableClass::get()->count();
		$this->assertEquals(sizeof($expectedClasses), $nSearchableClasses);


/*

 		$searchPage->SiteTreeOnly = true;
		$searchPage->Content = 'some random string';
		$searchPage->write();
		$scs = SearchableClass::get();
		foreach ($scs as $sc) {
			echo "SEARCHABLE CLASS:".$sc->Name."\n";
		}

		$sfs = SearchableField::get();
		foreach ($sfs as $sf) {
			echo "SEARCHABLE FIELD:".$sf->Name."\n";
		}

		$sfs = ElasticSearchPageSearchField::get();
		foreach ($sfs as $sf) {
			echo "ESP SEARCH FIELD:".$sf->Name."\n";
		}
*/
		// check the names expected to appear

		$fieldCtr = 0;
		foreach ($expectedClasses as $expectedClass) {
			$sc = SearchableClass::get()->filter('Name', $expectedClass)->first();
			$this->assertEquals($expectedClass,$sc->Name);

			$expectedNames = $expected[$expectedClass];
			foreach ($expectedNames as $expectedName) {
				$filter = array('Name' => $expectedName, 'SearchableClassID' => $sc->ID );
				print_r($filter);
				$sf = SearchableField::get()->filter($filter)->first();
				$this->assertEquals($expectedName, $sf->Name);
				$fieldCtr++;
			}
		}
		$nSearchableFields = SearchableField::get()->count();
		$this->assertEquals($fieldCtr, $nSearchableFields);
	}
}



/**
 * @package comments
 * @subpackage tests
 */
class SearchableTestPage extends Page implements TestOnly {

	private static $searchable_fields = array('Country','PageDate');

	private static $db = array(
		'Country' => 'Varchar',
		'PageDate' => 'Date'
	);

}

/**
 * @package comments
 * @subpackage tests
 */
class SearchableTestPage_Controller extends Controller implements TestOnly {
}
