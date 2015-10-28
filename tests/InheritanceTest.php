<?php

use SilverStripe\Elastica\Searchable;

/**
 * Test that inheritance works correctly with configuration properties
 * @package elastica
 */
class InheritanceTest extends ElasticsearchBaseTest {
	public static $fixture_file = 'elastica/tests/ElasticaTest.yml';


	public function testSearchableInherited() {

		$interfaces = class_implements('SiteTree');
		print_r($interfaces);

		$page = $this->objFromFixture('SearchableTestPage', 'first');
		$this->assertTrue($page->hasExtension('SilverStripe\Elastica\Searchable'),
			'Page extending SiteTree has Searchable extension');

		$page = $this->objFromFixture('SearchableTestFatherPage', 'father0001');
		$this->assertTrue($page->hasExtension('SilverStripe\Elastica\Searchable'),
			'Page extending SiteTree has Searchable extension');

		$page = $this->objFromFixture('SearchableTestGrandFatherPage', 'grandfather0001');
		$this->assertTrue($page->hasExtension('SilverStripe\Elastica\Searchable'),
			'Page extending SiteTree has Searchable extension');
	}


}
