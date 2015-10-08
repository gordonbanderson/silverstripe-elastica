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




}
