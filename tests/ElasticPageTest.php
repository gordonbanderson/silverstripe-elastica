<?php

/**
 * @package comments
 */
class ElasticPageTest extends FunctionalTest {

	public static $fixture_file = 'elastica/tests/ElasticaTest.yml';

	protected $extraDataObjects = array(
		'SearchableTestPage','FlickrPhoto','FlickrAuthor','FlickrSet','FlickrTag'
	);

	public function setUp() {
		// this needs to be called in order to create the list of searchable
		// classes and fields that are available.  Simulates part of a build
		$classes = array('SearchableTestPage','SiteTree','Page','FlickrPhoto','FlickrSet',
			'FlickrTag', 'FlickrAuthor', 'FlickrSet');
		$this->requireDefaultRecordsFrom = $classes;

		// add Searchable extension where appropriate
		FlickrSet::add_extension('SilverStripe\Elastica\Searchable');
		FlickrPhoto::add_extension('SilverStripe\Elastica\Searchable');
		FlickrTag::add_extension('SilverStripe\Elastica\Searchable');
		FlickrAuthor::add_extension('SilverStripe\Elastica\Searchable');
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
			'FlickrTag' => array('RawValue'),
			'FlickrAuthor' => array('PathAlias','DisplayName'),
			'FlickrPhoto' => array('Title','FlickrID','Description','TakenAt', 'Aperture',
				'ShutterSpeed','FocalLength35mm','ISO'),
			'FlickrSet' => array('Title','FlickrID','Description')
		);

		// check the expected classes
		$expectedClasses = array_keys($expected);
		$nSearchableClasses = SearchableClass::get()->count();
		$this->assertEquals(sizeof($expectedClasses), $nSearchableClasses);


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

		// check the names expected to appear

		$fieldCtr = 0;
		foreach ($expectedClasses as $expectedClass) {
			$sc = SearchableClass::get()->filter('Name', $expectedClass)->first();
			$this->assertEquals($expectedClass,$sc->Name);

			$expectedNames = $expected[$expectedClass];
			foreach ($expectedNames as $expectedName) {
				$filter = array('Name' => $expectedName, 'SearchableClassID' => $sc->ID );
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
 * @package elastica
 * @subpackage tests
 */
class FlickrPhoto extends DataObject implements TestOnly {
	private static $searchable_fields = array('Title','FlickrID','Description','TakenAt',
		'Aperture','ShutterSpeed','FocalLength35mm','ISO');

	private static $db = array(
		'Title' => 'Varchar(255)',
		'FlickrID' => 'Varchar',
		'Description' => 'HTMLText',
		'TakenAt' => 'SS_Datetime',
		'Aperture' => 'Float',
		'ShutterSpeed' => 'Varchar',
		'FocalLength35mm' => 'Int',
		'ISO' => 'Int',
		'MediumURL' => 'Varchar(255)',
		'MediumHeight' => 'Int',
		'MediumWidth' => 'Int'
	);

	static $belongs_many_many = array(
		'FlickrSets' => 'FlickrSet'
	);

	static $has_one = array(
		'Photographer' => 'FlickrAuthor'
	);

	static $many_many = array(
		'FlickrTags' => 'FlickrTag'
	);

}



/**
 * @package elastica
 * @subpackage tests
 */
class FlickrTag extends DataObject implements TestOnly {
	private static $db = array(
		'Value' => 'Varchar',
		'FlickrID' => 'Varchar',
		'RawValue' => 'HTMLText'
	);

	private static $belongs_many_many = array(
		'FlickrPhotos' => 'FlickrPhoto'
	);

	private static $searchable_fields = array('RawValue');
}


/**
 * @package elastica
 * @subpackage tests
 */
class FlickrSet extends DataObject implements TestOnly {
	private static $searchable_fields = array('Title','FlickrID','Description');

	private static $db = array(
		'Title' => 'Varchar(255)',
		'FlickrID' => 'Varchar',
		'Description' => 'HTMLText'
	);

	private static $many_many = array(
		'FlickrPhotos' => 'FlickrPhoto'
	);
}



/**
 * @package elastica
 * @subpackage tests
 */
class FlickrAuthor extends DataObject implements TestOnly {
		private static $db = array(
			'PathAlias' => 'Varchar',
			'DisplayName' => 'Varchar'
		);

		private static $has_many = array('FlickrPhotos' => 'FlickrPhoto');

		private static $searchable_fields = array('PathAlias', 'DisplayName');
}



/**
 * @package elastica
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
 * @package elastica
 * @subpackage tests
 */
class SearchableTestPage_Controller extends Controller implements TestOnly {
}
