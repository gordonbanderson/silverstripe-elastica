<?php

/**
 * Test the functionality of the Searchable extension
 * @package elastica
 */
class SearchableTest extends ElasticsearchBaseTest {
	public static $fixture_file = 'elastica/tests/ElasticaTest.yml';

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


	/**
	 * Test a valid identifier
	 */
	public function testMapping() {
		$flickrPhoto = $this->objFromFixture('FlickrPhoto', 'photo0001');
		$mapping = $flickrPhoto->getElasticaMapping();

		//array of mapping properties
		$properties = $mapping->getProperties();

		//test FlickrPhoto relationships mapping
		$expectedRelStringArray = array(
			'type' => 'string',
			'fields' => array(
				'standard' => array(
					'type' => 'string',
					'analyzer' => 'unstemmed'
				)
			),
			'analyzer' => 'stemmed'
		);

		/*
		'standard' => array(
					array('type' => 'string', 'analyzer' => 'standard')
				)
		 */

		$this->assertEquals($expectedRelStringArray,
			$properties['FlickrAuthor']['properties']['DisplayName']
		);
		$this->assertEquals($expectedRelStringArray,
			$properties['FlickrAuthor']['properties']['PathAlias']
		);
		$this->assertEquals($expectedRelStringArray,
			$properties['FlickrTag']['properties']['RawValue']
		);
		$this->assertEquals($expectedRelStringArray,
			$properties['FlickrSet']['properties']['Title']
		);
		$this->assertEquals($expectedRelStringArray,
			$properties['FlickrSet']['properties']['Description']
		);

		// check constructed field, location

		$locationProperties = $properties['location'];
		$this->assertEquals('geo_point', $locationProperties['type']);
		$this->assertEquals('compressed', $locationProperties['fielddata']['format']);
		$this->assertEquals('1cm', $locationProperties['fielddata']['precision']);


		//test the FlickrPhoto core model



		// check strings
		$shouldBeString = array('Title','Description');
		$shouldBeInt = array('ISO','FlickrID','FocalLength35mm');
		$shouldBeBoolean = array('IsSiteTree');
		$shouldBeDouble = array('Aperture');
		$shouldBeDateTime = array('TakenAt');
		$shouldBeDate = array('FirstViewed');

		// tokens are strings that have analyzer 'not_analyzed', namely the string is indexed as is
		$shouldBeTokens = array('ShutterSpeed','Link');


		// check strings
		$expectedStandardArray = array('type' => 'string', 'analyzer' => 'unstemmed');
		foreach ($shouldBeString as $fieldName) {
			$fieldProperties = $properties[$fieldName];

			$type = $fieldProperties['type'];
			$analyzer = $fieldProperties['analyzer'];
			$this->assertEquals('string', $type);

			// check for stemmed analysis
			$this->assertEquals('stemmed', $analyzer);

			// check for unstemmed analaysis

			$this->assertEquals($expectedStandardArray,$fieldProperties['fields']['standard']);

			// check for only 3 entries
			$this->assertEquals(3, sizeof(array_keys($fieldProperties)));
		}

		// check ints
		foreach ($shouldBeInt as $fieldName) {
			$fieldProperties = $properties[$fieldName];
			$type = $fieldProperties['type'];
			$this->assertEquals(1, sizeof(array_keys($fieldProperties)));
			$this->assertEquals('integer',$type);
		}


		// check doubles
		foreach ($shouldBeDouble as $fieldName) {
			$fieldProperties = $properties[$fieldName];
			$type = $fieldProperties['type'];
			$this->assertEquals(1, sizeof(array_keys($fieldProperties)));
			$this->assertEquals('double',$type);
		}

		// check boolean
		foreach ($shouldBeBoolean as $fieldName) {
			$fieldProperties = $properties[$fieldName];
			$type = $fieldProperties['type'];
			$this->assertEquals(1, sizeof(array_keys($fieldProperties)));
			$this->assertEquals('boolean',$type);
		}


		foreach ($shouldBeDate as $fieldName) {
			$fieldProperties = $properties[$fieldName];
			$type = $fieldProperties['type'];
			$this->assertEquals(2, sizeof(array_keys($fieldProperties)));
			$this->assertEquals('date',$type);
			$this->assertEquals('y-M-d', $fieldProperties['format']);
		}



		// check date time, stored in Elasticsearch as a date with a different format than above
		foreach ($shouldBeDateTime as $fieldName) {
			$fieldProperties = $properties[$fieldName];
			$type = $fieldProperties['type'];
			$this->assertEquals(2, sizeof(array_keys($fieldProperties)));
			$this->assertEquals('date',$type);
			$this->assertEquals('y-M-d H:m:s', $fieldProperties['format']);
		}

		//check shutter speed is tokenized, ie not analyzed - for aggregation purposes
		//
		foreach ($shouldBeTokens as $fieldName) {
			$fieldProperties = $properties[$fieldName];
			$type = $fieldProperties['type'];
			$this->assertEquals('string', $type);

			// check for no analysis
			$analyzer = $fieldProperties['index'];
			$this->assertEquals('not_analyzed', $analyzer);

			// check for only 2 entries
			$this->assertEquals(2, sizeof(array_keys($fieldProperties)));
		}
	}


	public function testGetType() {
		//A type in Elasticsearch is used to represent each SilverStripe content type,
		//the name used being the Silverstripe $fieldName

		$flickrPhoto = $this->objFromFixture('FlickrPhoto', 'photo0001');
		$type = $flickrPhoto->getElasticaType();
		$this->assertEquals('FlickrPhoto', $type);
	}


	/*
	Get a record as an Elastic document and check values
	 */
	public function testElasticaDocument() {
		$flickrPhoto = $this->objFromFixture('FlickrPhoto', 'photo0001');
		$doc = $flickrPhoto->getElasticaDocument()->getData();

		$expected = array();
		$expected['Title'] = 'Bangkok' ;
		$expected['FlickrID'] = '1234567';
		$expected['Description'] = 'Test photograph';
		$expected['TakenAt'] = '2012-04-24 18:12:00';
		$expected['FirstViewed'] = '2012-04-28';
		$expected['Aperture'] = 8.0;

		//Shutter speed is altered for aggregations
		$expected['ShutterSpeed'] = '0.01|1/100';
		$expected['FocalLength35mm'] = 140;
		$expected['ISO'] = 400;
		$expected['Photographer'] = array();
		$expected['FlickrTags'] = array();
		$expected['FlickrSets'] = array();
		$expected['IsInSiteTree'] = false;
		$this->assertEquals($expected, $doc);
	}


	public function testElasticaResult() {
		$flickrPhoto = $this->objFromFixture('FlickrPhoto', 'photo0001');
		//$doc = $flickrPhoto->getElasticaResult()->getData();
		//TODO
	}


	/*
	getFieldValuesAsArray - needs html
	onBeforePublish
	onAfterPublish
	doDeleteDocumentIfInSearch - search flag
	doDeleteDocument - delete a non existent doc
	getAllSearchableFields - this one may be tricky, needs searchable fields not set
	fieldsToElasticaConfig - array case
	requireDefaultRecords - recursive method issue
	 */

	public function testUnpublishPublish() {
		$nDocsAtStart = $this->getNumberOfIndexedDocuments();
		$this->checkNumberOfIndexedDocuments($nDocsAtStart);

		$page = $this->objFromFixture('Page', 'page0001');
		$page->doUnpublish();

		$this->checkNumberOfIndexedDocuments($nDocsAtStart-1);

		$page->doPublish();
		$this->checkNumberOfIndexedDocuments($nDocsAtStart);
	}

}
