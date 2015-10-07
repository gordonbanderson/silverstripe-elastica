<?php
class ElasticSearcherUnitTest extends ElasticsearchBaseTest {

	use \SilverStripe\Elastica\ElasticSearcher;


	public static $fixture_file = 'elastica/tests/ElasticaTest.yml';


	public function setUp() {
		parent::setUp();
	}

	public function tearDown() {
		parent::tearDown();
	}


	public function testSearchFieldMapping() {
		$es = new ElasticSearcher();
		$es->setClasses('SiteTree');
		$expected = array('Title' => 'string', 'Content' => 'string');
		$this->assertEquals($expected, $es->getSearchFieldsMappingForClasses('SiteTree'));
	}


	// ---- tests for field array to elasticsearch syntax
	public function testConvertWeightedFieldsForElasticaUnaryStrings() {
		$es = new ElasticSearcher();
		$es->setClasses('FlickrPhoto');
		$fields = array('Title' => 1, 'Description' => 1);
		$expected = array('Title', 'Title.*','Description', 'Description.*');
		$this->assertEquals($expected, $es->convertWeightedFieldsForElastica($fields));
	}


	public function testConvertWeightedFieldsForElasticaMultipleStrings() {
		$es = new ElasticSearcher();
		$es->setClasses('FlickrPhoto');
		$fields = array('Title' => 2, 'Description' => 1);
		$expected = array('Title^2', 'Title.*^2','Description', 'Description.*');
		$this->assertEquals($expected, $es->convertWeightedFieldsForElastica($fields));
	}


	public function testConvertWeightedFieldsForElasticaTestNonString() {
		$es = new ElasticSearcher();
		$es->setClasses('FlickrPhoto');
		$fields = array('Aperture' => 2, 'FocalLength35mm' => 1);
		$expected = array('Aperture^2', 'FocalLength35mm');
		$this->assertEquals($expected, $es->convertWeightedFieldsForElastica($fields));
	}


	// ---- tests for the toQuotedCSV function ----
	public function testToQuotedCSVFromString() {
		$expected = "'Bangkok','Nonthaburi','Saraburi','Chiang Mai'";
		$items = 'Bangkok,Nonthaburi,Saraburi,Chiang Mai';
		$quoted = ElasticSearcher::convertToQuotedCSV($items);
		$this->assertEquals($expected, $quoted);
	}

	public function testToQuotedCSVFromArray() {
		$expected = "'Bangkok','Nonthaburi','Saraburi','Chiang Mai'";
		$items = array('Bangkok','Nonthaburi','Saraburi','Chiang Mai');
		$quoted = ElasticSearcher::convertToQuotedCSV($items);
		$this->assertEquals($expected, $quoted);
	}

	public function testToQuotedCSVEmptyString() {
		$quoted = ElasticSearcher::convertToQuotedCSV('');
		$this->assertEquals('', $quoted);
	}

	public function testToQuotedCSVEmptyArray() {
		$quoted = ElasticSearcher::convertToQuotedCSV(array());
		$this->assertEquals('', $quoted);
	}

	public function testToQuotedCSVNull() {
		$quoted = ElasticSearcher::convertToQuotedCSV(null);
		$this->assertEquals('', $quoted);
	}
}
