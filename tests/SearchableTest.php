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
		$expected['TestMethod'] = 'this is a test method';
		$expected['TestMethodHTML'] = 'this is a test method that returns *HTML*';


		print_r($doc);

		$this->assertEquals($expected, $doc);
	}


	public function testElasticaResult() {
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
			$this->fail('Exception should have been thrown when deleting non existent item');
		} catch (Exception $e) {
			//This error comes out of Elastica itself
			$this->assertEquals('Deleted document FlickrPhotoTO (2) not found in search index.',
				$e->getMessage());
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


	/**
	 * For a page that is already published, set the ShowInSearch flag to false,
	 * write to stage, and then rePublish
	 */
	public function testUnpublishAlreadyPublisedhHideFromSearch() {
		$page = $this->objFromFixture('SiteTree', 'sitetree001');

		// By default the page is not indexed (for speed reasons)
		// Change the title, turn on indexing and save it
		// This will invoke a database write
		$page->Title = "I will be indexed";
		$page->IndexingOff = true;
		$page->write();

		$nDocsAtStart = $this->getNumberOfIndexedDocuments();
		$this->checkNumberOfIndexedDocuments($nDocsAtStart);

		// assert keys of term vectors, this will indicate page
		// is stored in the index or not
		$termVectors = $page->getTermVectors();
		$expected = array(
		'0' => 'Content',
		'1' => 'Content.shingles',
		'2' => 'Content.standard',
		'3' => 'Link',
				'4' => 'Title',
		'5' => 'Title.autocomplete',
		'6' => 'Title.shingles',
		'7' => 'Title.standard',
		);

		$keys = array_keys($termVectors);
		sort($keys);

		$this->assertEquals($expected, $keys);


//CURRENT
		$page->ShowInSearch = false;
		$page->write();

		$this->checkNumberOfIndexedDocuments($nDocsAtStart);

		$page->doPublish();
		$this->checkNumberOfIndexedDocuments($nDocsAtStart);
	}



	/**
	 * For a page that is not published, set the ShowInSearch flag to false,
	 * write to stage, and then rePublish.  Same as previous test except
	 * no need to delete from the index as it already does not exist
	 */
	public function testUnpublishPublishHideFromSearch() {
		$page = $this->objFromFixture('SiteTree', 'sitetree001');
		$page->doUnpublish();

		// By default the page is not indexed (for speed reasons)
		// Change the title, turn on indexing and save it
		// This will invoke a database write
		$page->Title = "I will be indexed";
		$page->IndexingOff = true;
		$page->write();

		$nDocsAtStart = $this->getNumberOfIndexedDocuments();
		$this->checkNumberOfIndexedDocuments($nDocsAtStart);
		$page->ShowInSearch = false;
		$page->write();

		$this->checkNumberOfIndexedDocuments($nDocsAtStart);

		$page->doPublish();
		$this->checkNumberOfIndexedDocuments($nDocsAtStart);
	}




	public function testGetCMSFields() {
		$flickrPhoto = $this->objFromFixture('FlickrPhotoTO', 'photo0001');
		$fields = $flickrPhoto->getCMSFields();

		$this->checkTabExists($fields,'ElasticaTermsset');
	}


	public function testNoSearchableFieldsConfigured() {
		$config = Config::inst();
		$sf = $config->get('FlickrPhotoTO', 'searchable_fields');
		$config->remove('FlickrPhotoTO', 'searchable_fields');
		$fp = Injector::inst()->create('FlickrPhotoTO');
		try {
			$fp->getAllSearchableFields();
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
			$fp->getAllSearchableFields();
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
			$fp->getAllSearchableFields();
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
			$fp->getAllSearchableFields();
			$this->fail("getAllSearchableFields should have failed searchable relationship does not exist");
		} catch (Exception $e) {
			$this->assertEquals('The method thisMethodDoesNotExist not found in class FlickrPhotoTO, please check configuration',
				 $e->getMessage());
		}

    	// MUST REMOVE FIRST.  Otherwise append and the erroroneus value above still exists
    	$config->remove('FlickrPhotoTO', 'searchable_relationships');
		$config->update('FlickrPhotoTO' ,'searchable_relationships', $sr);
	}


	public function testFieldsToElasticaConfig() {
		$flickrPhoto = $this->objFromFixture('FlickrPhotoTO', 'photo0001');
		$fields = $flickrPhoto->getAllSearchableFields();

		$expected = array(
			'Title' => array(
				'title' => 'Title',
				'filter' => 'PartialMatchFilter'
			),
			'FlickrID' => array(
				'title' => 'Flickr ID',
				'filter' => 'PartialMatchFilter'
			),
			'Description' => array(
				'title' => 'Description',
				'filter' => 'PartialMatchFilter'
			),
			'TakenAt' => array(
				'title' => 'Taken At',
				'filter' => 'PartialMatchFilter'
			),
			'TakenAtDT' => array(
				'title' => 'Taken At DT',
				'filter' => 'PartialMatchFilter'
			),
			'FirstViewed' => array(
				'title' => 'First Viewed',
				'filter' => 'PartialMatchFilter'
			),
			'Aperture' => array(
				'title' => 'Aperture',
				'filter' => 'PartialMatchFilter'
			),
			'ShutterSpeed' => array(
				'title' => 'Shutter Speed',
				'filter' => 'PartialMatchFilter'
			),
			'FocalLength35mm' => array(
				'title' => 'Focal Length35mm',
				'filter' => 'PartialMatchFilter'
			),
			'ISO' => array(
				'title' => 'ISO',
				'filter' => 'PartialMatchFilter'
			),
			'AspectRatio' => array(
				'title' => 'Aspect Ratio',
				'filter' => 'PartialMatchFilter'
			),
			'TestMethod' => array(
				'title' => 'Test Method',
				'filter' => 'PartialMatchFilter'
			),
			'TestMethodHTML' => array(
				'title' => 'Test Method HTML',
				'filter' => 'PartialMatchFilter'
			),
			'Photographer()' => array(
				'PathAlias' => array(
					'title' => 'Path Alias',
					'filter' => 'PartialMatchFilter'
				),
				'DisplayName' => array(
					'title' => 'Display Name',
					'filter' => 'PartialMatchFilter'
				)
			),
			'FlickrTagTOs()' => array(
				'RawValue' => array(
					'title' => 'Raw Value',
					'filter' => 'PartialMatchFilter'
				)
			),
			'FlickrSetTOs()' => array(
				'Title' => array(
					'title' => 'Title',
					'filter' => 'PartialMatchFilter'
				),
				'FlickrID' => array(
					'title' => 'Flickr ID',
					'filter' => 'PartialMatchFilter'
				),
				'Description' => array(
					'title' => 'Description',
					'filter' => 'PartialMatchFilter'
				)
			)
		);

		$this->assertEquals($expected, $fields);
	}


	public function testHasOneExistsSearchableToArray() {
		$flickrPhoto = $this->objFromFixture('FlickrPhotoTO', 'photo0001');
		$flickrPhoto->IndexingOff = false;
		$flickrPhoto->Title = 'Test title edited';
		$photographer = new FlickrAuthorTO();
		$photographer->DisplayName = 'Fred Bloggs';
		$photographer->PathAlias = '/fredbloggs';

		$photographer->write();

		$flickrPhoto->PhotographerID = $photographer->ID;;
		$flickrPhoto->write();
		echo 'ID='.$flickrPhoto->PhotographerID;
		$fieldValuesArray = $flickrPhoto->getFieldValuesAsArray();

		$actual = $fieldValuesArray['Photographer'];
		$this->generateAssertionsFromArray($actual);
		$expected = array(
			'PathAlias' => '/fredbloggs',
			'DisplayName' => 'Fred Bloggs',
			'FlickrPhotoTO' => '',
		);

		$this->assertEquals($expected, $actual);
	}



	public function testHasManyExistsSearchableToArray() {
		$flickrPhoto = $this->objFromFixture('FlickrPhotoTO', 'photo0001');
		$flickrPhoto->IndexingOff = false;
		$flickrPhoto->Title = 'Test title edited';
		$tag1 = new FlickrTagTO();
		$tag1->FlickrID = '1000001';
		$tag1->Value = 'auckland';
		$tag1->RawValue = 'Auckland';
		$tag1->write();


		$tag2 = new FlickrTagTO();
		$tag2->FlickrID = '1000002';
		$tag2->Value = 'wellington';
		$tag2->RawValue = 'Wellington';
		$tag2->write();

		$flickrPhoto->FlickrTagTOs()->add($tag1);
		$flickrPhoto->FlickrTagTOs()->add($tag2);




		$flickrPhoto->write();
		echo 'ID='.$flickrPhoto->PhotographerID;
		$fieldValuesArray = $flickrPhoto->getFieldValuesAsArray();
		$actual = $fieldValuesArray['Photographer'];
		$this->assertEquals(array(), $actual);


		$actual = $fieldValuesArray['FlickrTagTOs'];
		$this->generateAssertionsFromArrayRecurse($actual);

		$expected = array(
			'0' => array(
				'RawValue' => 'Auckland'
			),
			'1' => array(
				'RawValue' => 'Wellington'
			)
		);


		$this->assertEquals($expected, $actual);
	}


	public function testUpdateCMSFieldsDatabject() {
		$flickrPhoto = $this->objFromFixture('FlickrPhotoTO', 'photo0001');
		$flickrPhoto->IndexingOff = false;
		$flickrPhoto->Title = 'Test title edited';
		$flickrPhoto->write();
		$fields = new FieldList();
		$fields = $flickrPhoto->getCMSFields();

		$tabset = $fields->findOrMakeTab('Root.ElasticaTerms');
		$tabNames = array();
		foreach ($tabset->Tabs() as $tab) {
			$tabFields = array();
			foreach ($tab->FieldList() as $field) {
				array_push($tabFields, $field->getName());
			}
			$expectedName = 'TermsFor'.$tab->getName();;
			$expected = array($expectedName);
			$this->assertEquals($expected, $tabFields);
			array_push($tabNames, $tab->getName());
		}
		$expected = array('Description','Description_shingles','Description_standard',
			'ShutterSpeed',  'TestMethod', 'TestMethod_shingles', 'TestMethod_standard',
			'TestMethodHTML', 'TestMethodHTML_shingles', 'TestMethodHTML_standard',
			'Title','Title_autocomplete','Title_shingles','Title_standard');

		$this->assertEquals($expected, $tabNames);
	}


	public function testUpdateCMSFieldsSiteTreeLive() {
		echo "+++++++++++++++++ testUpdateCMSFieldsSiteTreeLive +++++++++++++\n";
		$page = $this->objFromFixture('SearchableTestPage', 'first');
		$page->IndexingOff = false;
		$page->Title = 'Test title edited';
		$page->write();
		$page->doPublish();
		$fields = new FieldList();

		$fields = $page->getCMSFields();

		$tabset = $fields->findOrMakeTab('Root.ElasticaTerms');
		$tabNames = array();
		foreach ($tabset->Tabs() as $tab) {
			$tabFields = array();
			foreach ($tab->FieldList() as $field) {
				array_push($tabFields, $field->getName());
			}
			$expectedName = 'TermsFor'.$tab->getName();;
			$expected = array($expectedName);
			$this->assertEquals($expected, $tabFields);
			array_push($tabNames, $tab->getName());
		}
		$expected = array(
			'Content','Content_standard','Link','Title','Title_autocomplete','Title_shingles',
			'Title_standard');
		$this->generateAssertionsFromArray1D($tabNames);
		$this->assertEquals($expected, $tabNames);

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
