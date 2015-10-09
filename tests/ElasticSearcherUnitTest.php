<?php
use SilverStripe\Elastica\QueryGenerator;

class ElasticSearcherUnitTest extends ElasticsearchBaseTest {
	public static $fixture_file = 'elastica/tests/ElasticaTest.yml';

	public function setUp() {
		parent::setUp();
	}

	public function tearDown() {
		parent::tearDown();
	}

	public function testSearchFieldMapping() {
		$qg = new QueryGenerator();
		$qg->setClasses('SiteTree');
		$expected = array('Title' => 'string', 'Content' => 'string');
		//FIXME move to a utility class
		$this->assertEquals($expected, $qg->getSearchFieldsMappingForClasses('SiteTree'));
	}

}
