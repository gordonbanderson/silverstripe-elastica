<?php

use SilverStripe\Elastica\ElasticSearcher;


/**
 * Test the functionality of the Searchable extension
 * @package elastica
 */
class SearchableTest extends ElasticsearchBaseTest {
	public static $fixture_file = 'elastica/tests/ElasticaTest.yml';

	public function setUp() {
		// this needs to be called in order to create the list of searchable
		// classes and fields that are available.  Simulates part of a build
		$classes = array('SearchableTestPage','SiteTree','Page','FlickrPhotoTO','FlickrSetTO',
			'FlickrTagTO', 'FlickrAuthorTO', 'FlickrSetTO');
		$this->requireDefaultRecordsFrom = $classes;

		// add Searchable extension where appropriate
		FlickrSetTO::add_extension('SilverStripe\Elastica\Searchable');
		FlickrPhotoTO::add_extension('SilverStripe\Elastica\Searchable');
		FlickrTagTO::add_extension('SilverStripe\Elastica\Searchable');
		FlickrAuthorTO::add_extension('SilverStripe\Elastica\Searchable');
		SearchableTestPage::add_extension('SilverStripe\Elastica\Searchable');

		// load fixtures
		parent::setUp();
	}



	public function testgetFieldValuesAsArrayFromFixtures() {
		$manyTypes = $this->objFromFixture('ManyTypesPage', 'manytypes0001');
		$result = $manyTypes->getFieldValuesAsArray();
		$this->generateAssertionsFromArray($result);
		$expected = array(
			'BooleanField' => '1',
			'CurrencyField' => '100.25',
			'DateField' => '2014-04-15',
			'DecimalField' => '0',
			'EnumField' => '',
			'HTMLTextField' => '',
			'HTMLVarcharField' => 'This is some *HTML*varchar field',
			'IntField' => '677',
			'PercentageField' => '27',
			'SS_DatetimeField' => '2014-10-18 08:24:00',
			'TextField' => 'This is a text field',
			'TimeField' => '17:48:18',
			'Title' => 'Many Types Page',
			'Content' => 'Many types of fields',
		);
		$this->assertEquals($expected, $result);

	}



	public function testBadFormatFields() {
		$manyTypes = $this->objFromFixture('ManyTypesPage', 'manytypes0001');
		$fields = $manyTypes->getElasticaFields();

		$expected = array('type' => 'boolean');
		$this->assertEquals($expected, $fields['BooleanField']);

		$expected = array('type' => 'double');
		$this->assertEquals($expected, $fields['CurrencyField']);

		$expected = array('type' => 'date', 'format' => 'y-M-d');
		$this->assertEquals($expected, $fields['DateField']);

		$expected = array('type' => 'double');
		$this->assertEquals($expected, $fields['DecimalField']);

		$stringFormat = array(
			'type' => 'string',
			'analyzer' => 'stemmed',
			'term_vector' => 'yes',
			'fields' => array(
				'standard' => array(
					'type' => 'string',
					'analyzer' => 'unstemmed',
					'term_vector' => 'yes'
				),
				'shingles' => array(
					'type' => 'string',
					'analyzer' => 'shingles',
					'term_vector' => 'yes'
				)
			)
		);
		$expected = $stringFormat;
		$this->assertEquals($expected, $fields['EnumField']);

		$expected = $stringFormat;
		$this->assertEquals($expected, $fields['HTMLTextField']);

		$expected = $stringFormat;
		$this->assertEquals($expected, $fields['HTMLVarcharField']);

		$expected = array('type' => 'integer');
		$this->assertEquals($expected, $fields['IntField']);

		$expected = array('type' => 'double');
		$this->assertEquals($expected, $fields['PercentageField']);

		$expected = array('type' => 'date', 'format' => 'y-M-d H:m:s');
		$this->assertEquals($expected, $fields['SS_DatetimeField']);

		$expected = $stringFormat;
		$this->assertEquals($expected, $fields['TextField']);

		$expected = array('type' => 'date', 'format' => 'H:m:s');
		$this->assertEquals($expected, $fields['TimeField']);
	}



	public function testGetDateFields() {
		$flickrPhoto = $this->objFromFixture('FlickrPhotoTO', 'photo0001');
		$fields = $flickrPhoto->getElasticaFields();

		print_r($fields);
		$expected = array('type' => 'date', 'format' => 'y-M-d H:m:s');
		$this->assertEquals($expected, $fields['TakenAt']);

		$expected = array('type' => 'date', 'format' => 'y-M-d H:m:s');
		$this->assertEquals($expected, $fields['TakenAtDT']);

		$expected = array('type' => 'date', 'format' => 'y-M-d');
		$this->assertEquals($expected, $fields['FirstViewed']);
	}


	/**
	 * Test a valid identifier
	 */
	public function testMapping() {
		$flickrPhoto = $this->objFromFixture('FlickrPhotoTO', 'photo0001');
		$mapping = $flickrPhoto->getElasticaMapping();

		//array of mapping properties
		$properties = $mapping->getProperties();

		//test FlickrPhotoTO relationships mapping
		$expectedRelStringArray = array(
			'type' => 'string',
			'fields' => array(
				'standard' => array(
					'type' => 'string',
					'analyzer' => 'unstemmed',
					'term_vector' => 'yes'
				),
				'shingles' => array(
					'type' => 'string',
					'analyzer' => 'shingles',
					'term_vector' => 'yes'
				)
			),
			'analyzer' => 'stemmed',
			'term_vector' => 'yes'
		);

		/*
		'standard' => array(
					array('type' => 'string', 'analyzer' => 'standard')
				)
		 */

		$this->assertEquals($expectedRelStringArray,
			$properties['FlickrAuthorTO']['properties']['DisplayName']
		);
		$this->assertEquals($expectedRelStringArray,
			$properties['FlickrAuthorTO']['properties']['PathAlias']
		);
		$this->assertEquals($expectedRelStringArray,
			$properties['FlickrTagTO']['properties']['RawValue']
		);
		$this->assertEquals($expectedRelStringArray,
			$properties['FlickrSetTO']['properties']['Title']
		);
		$this->assertEquals($expectedRelStringArray,
			$properties['FlickrSetTO']['properties']['Description']
		);

		// check constructed field, location

		$locationProperties = $properties['location'];
		$this->assertEquals('geo_point', $locationProperties['type']);
		$this->assertEquals('compressed', $locationProperties['fielddata']['format']);
		$this->assertEquals('1cm', $locationProperties['fielddata']['precision']);


		//test the FlickrPhotoTO core model



		// check strings
		$shouldBeString = array('Title','Description');
		$shouldBeInt = array('ISO','FlickrID','FocalLength35mm');
		$shouldBeBoolean = array('IsInSiteTree');
		$shouldBeDouble = array('Aperture');
		$shouldBeDateTime = array('TakenAt');
		$shouldBeDate = array('FirstViewed');

		// tokens are strings that have analyzer 'not_analyzed', namely the string is indexed as is
		$shouldBeTokens = array('ShutterSpeed','Link');


		// check strings
		$expectedStandardArray = array('type' => 'string', 'analyzer' => 'unstemmed', 'term_vector' => 'yes');
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
			$this->assertEquals(4, sizeof(array_keys($fieldProperties)));
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

		$flickrPhoto = $this->objFromFixture('FlickrPhotoTO', 'photo0001');
		$type = $flickrPhoto->getElasticaType();
		$this->assertEquals('FlickrPhotoTO', $type);
	}


	/*
	Get a record as an Elastic document and check values
	 */
	public function testGetElasticaDocument() {
		$flickrPhoto = $this->objFromFixture('FlickrPhotoTO', 'photo0001');
		$doc = $flickrPhoto->getElasticaDocument()->getData();

		$expected = array();
		$expected['Title'] = 'Bangkok' ;
		$expected['FlickrID'] = '1234567';
		$expected['Description'] = 'Test photograph';
		$expected['TakenAt'] = '2011-07-04 20:36:00';
		$expected['TakenAtDT'] = null;
		$expected['FirstViewed'] = '2012-04-28';
		$expected['Aperture'] = 8.0;

		//Shutter speed is altered for aggregations
		$expected['ShutterSpeed'] = '0.01|1/100';
		$expected['FocalLength35mm'] = 140;
		$expected['ISO'] = 400;
		$expected['AspectRatio'] = 1.013;
		$expected['Photographer'] = array();
		$expected['FlickrTagTOs'] = array();
		$expected['FlickrSetTOs'] = array();
		$expected['IsInSiteTree'] = false;
		$expected['location'] = array('lat' => 13.42, 'lon' => 100);

		print_r($doc);

		$this->assertEquals($expected, $doc);
	}


	public function testElasticaResult() {
		$flickrPhoto = $this->objFromFixture('FlickrPhotoTO', 'photo0001');

		$resultList = $this->getResultsFor('Bangkok');

		// there is only one result.  Note lack of a 'first' method
		foreach ($resultList->getIterator() as $fp) {
			//This is an Elastica\Result object
			$elasticaResult = $fp->getElasticaResult();

			$fields = $elasticaResult->getSource();

			$this->assertEquals($fp->Title, $fields['Title']);
			$this->assertEquals($fp->FlickrID, $fields['FlickrID']);
			$this->assertEquals($fp->Description, $fields['Description']);
			$this->assertEquals($fp->TakenAt, $fields['TakenAt']);
			$this->assertEquals($fp->FirstViewed, $fields['FirstViewed']);
			$this->assertEquals($fp->Aperture, $fields['Aperture']);

			//ShutterSpeed is a special case, mangled field
			$this->assertEquals('0.01|1/100', $fields['ShutterSpeed']);
			$this->assertEquals($fp->FocalLength35mm, $fields['FocalLength35mm']);
			$this->assertEquals($fp->ISO, $fields['ISO']);
			$this->assertEquals($fp->AspectRatio, $fields['AspectRatio']);

			//Empty arrays for null values
			$this->assertEquals(array(), $fields['Photographer']);
			$this->assertEquals(array(), $fields['FlickrTagTOs']);
			$this->assertEquals(array(), $fields['FlickrSetTOs']);
			$this->assertEquals(false, $fields['IsInSiteTree']);
		}
	}


	public function testDeleteNonExistentDoc() {
		$fp = new FlickrPhotoTO();
		$fp->Title = 'Test Deletion';
		$fp->IndexingOff = true; // do no index this
		$fp->write();
		$fp->IndexingOff = false;

		try {
			$fp->delete();
		} catch (Elastica\Exception\NotFoundException $e) {
			//This error comes out of Elastica itself
			$this->assertEquals('Doc id 2 not found and can not be deleted', $e->getMessage());
		}

	}


	public function testUnpublishPublish() {
		$nDocsAtStart = $this->getNumberOfIndexedDocuments();
		$this->checkNumberOfIndexedDocuments($nDocsAtStart);

		$page = $this->objFromFixture('SiteTree', 'sitetree001');
		$page->doUnpublish();

		$this->checkNumberOfIndexedDocuments($nDocsAtStart-1);

		$page->doPublish();
		$this->checkNumberOfIndexedDocuments($nDocsAtStart);
	}


	public function testUnpublishPublishHideFromSearch() {
		$nDocsAtStart = $this->getNumberOfIndexedDocuments();
		$this->checkNumberOfIndexedDocuments($nDocsAtStart);

		$page = $this->objFromFixture('SiteTree', 'sitetree001');
	//	$page->doUnpublish();
		$page->Title = "I will be indexed";
		$page->write();
//CURRENT
		$page->ShowInSearch = false;
		$page->write();

		$this->checkNumberOfIndexedDocuments($nDocsAtStart-1);

		$page->doPublish();
		$this->checkNumberOfIndexedDocuments($nDocsAtStart);
	}




	public function testGetCMSFields() {
		$flickrPhoto = $this->objFromFixture('FlickrPhotoTO', 'photo0001');
		$fields = $flickrPhoto->getCMSFields();

		$tab = $this->checkTabExists($fields,'ElasticaTerms');

		//Check fields

		$this->fail('Need to fix the REMOVE ME tab');
	}


	public function testNoSearchableFieldsConfigured() {
		$config = Config::inst();
		$sf = $config->get('FlickrPhotoTO', 'searchable_fields');
		$config->remove('FlickrPhotoTO', 'searchable_fields');
		$fp = Injector::inst()->create('FlickrPhotoTO');
		try {
			$fields = $fp->getAllSearchableFields();
			$this->fail("getAllSearchableFields should have failed as static var searchable_fields not configured");
		} catch (Exception $e) {
			$this->assertEquals('The field $searchable_fields must be set for the class FlickrPhotoTO', $e->getMessage());
		}

		$config->update('FlickrPhotoTO' ,'searchable_fields', $sf);
	}


	public function testNoSearchableFieldsConfiguredForHasManyRelation() {
		$config = Config::inst();
		$sf = $config->get('FlickrTagTO', 'searchable_fields');
		$config->remove('FlickrTagTO', 'searchable_fields');
		$fp = Injector::inst()->create('FlickrPhotoTO');
		try {
			$fields = $fp->getAllSearchableFields();
			$this->fail("getAllSearchableFields should have failed as static var searchable_fields not configured");
		} catch (Exception $e) {
			$this->assertEquals('The field $searchable_fields must be set for the class FlickrTagTO', $e->getMessage());
		}

		$config->update('FlickrTagTO' ,'searchable_fields', $sf);

	}


	public function testNoSearchableFieldsConfiguredForHasOneRelation() {
		$config = Config::inst();
		$sf = $config->get('FlickrAuthorTO', 'searchable_fields');
		$config->remove('FlickrAuthorTO', 'searchable_fields');
		$fp = Injector::inst()->create('FlickrPhotoTO');
		try {
			$fields = $fp->getAllSearchableFields();
			$this->fail("getAllSearchableFields should have failed as static var searchable_fields not configured");
		} catch (Exception $e) {
			$this->assertEquals('The field $searchable_fields must be set for the class FlickrAuthorTO', $e->getMessage());
		}

		$config->update('FlickrAuthorTO' ,'searchable_fields', $sf);

	}


	public function testSearchableMethodNotExist() {
		$config = Config::inst();
		$sr = $config->get('FlickrPhotoTO', 'searchable_relationships');
		$config->remove('FlickrPhotoTO', 'searchable_relationships');
		$config->update('FlickrPhotoTO', 'searchable_relationships',array('thisMethodDoesNotExist'));
		$fp = Injector::inst()->create('FlickrPhotoTO');
		try {
			$fields = $fp->getAllSearchableFields();
			$this->fail("getAllSearchableFields should have failed searchable relationship does not exist");
		} catch (Exception $e) {
			$this->assertEquals('The method thisMethodDoesNotExist not found in class FlickrPhotoTO, please check configuration',
				 $e->getMessage());
		}

		echo 'UPDATING TO ';
		print_r($sr);


    	//$sr2 = array('Photographer', 'FlickrTagTOs', 'FlickrSetTOs');

    	// MUST REMOVE FIRST.  Otherwise append and the erroroneus value above still exists
    	$config->remove('FlickrPhotoTO', 'searchable_relationships');
		$config->update('FlickrPhotoTO' ,'searchable_relationships', $sr);
	}


	public function testFieldsToElasticaConfig() {
		$config = Config::inst();
		$sr = $config->get('FlickrPhotoTO', 'searchable_relationships');
		$flickrPhoto = $this->objFromFixture('FlickrPhotoTO', 'photo0001');
		$fields = $flickrPhoto->getAllSearchableFields();
		print_r($fields);

	}


	private function getResultsFor($query, $pageLength = 10) {
		$es = new ElasticSearcher();
		$es->setStart(0);
		$es->setPageLength($pageLength);
		//$es->addFilter('IsInSiteTree', false);
		$es->setClasses('FlickrPhotoTO');
		$fields = array('Title' => 1, 'Description' => 1);
		$resultList = $es->search($query, $fields)->getList();
		$this->assertEquals('SilverStripe\Elastica\ResultList', get_class($resultList));
		return $resultList;
	}

}
