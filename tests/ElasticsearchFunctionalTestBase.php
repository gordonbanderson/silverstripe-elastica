<?php

use SilverStripe\Elastica\ReindexTask;
use SilverStripe\Elastica\ElasticaService;
use SilverStripe\Elastica\ElasticaUtil;


class ElasticsearchFunctionalTestBase extends FunctionalTest {
	public static $ignoreFixtureFileFor = array();

	protected $extraDataObjects = array(
		'SearchableTestPage','FlickrPhotoTO','FlickrAuthorTO','FlickrSetTO','FlickrTagTO',
		'SearchableTestFatherPage','SearchableTestGrandFatherPage'
	);


	public function setUpOnce() {
		ElasticaUtil::setPrinterOutput(false);

		$config = Config::inst();
		$config->remove('Injector', 'SilverStripe\Elastica\ElasticaService');
		$constructor = array('constructor' => array('%$Elastica\Client', 'elastica_ss_module_test'));
		$config->update('Injector', 'SilverStripe\Elastica\ElasticaService', $constructor);
		parent::setUpOnce();
	}


	public function setUp() {

		error_log("*************** TEST: ".$this->getName());

		$cache = SS_Cache::factory('elasticsearch');
		$cache->clean(Zend_Cache::CLEANING_MODE_ALL);
		SS_Cache::set_cache_lifetime('elasticsearch', 3600, 1000);

		// this needs to be called in order to create the list of searchable
		// classes and fields that are available.  Simulates part of a build
		$classes = array('SearchableTestPage','SiteTree','Page','FlickrPhotoTO','FlickrSetTO',
			'FlickrTagTO', 'FlickrAuthorTO');
		$this->requireDefaultRecordsFrom = $classes;

		// add Searchable extension where appropriate
		FlickrSetTO::add_extension('SilverStripe\Elastica\Searchable');
		FlickrPhotoTO::add_extension('SilverStripe\Elastica\Searchable');
		FlickrTagTO::add_extension('SilverStripe\Elastica\Searchable');
		FlickrAuthorTO::add_extension('SilverStripe\Elastica\Searchable');
		SearchableTestPage::add_extension('SilverStripe\Elastica\Searchable');

		$elasticaException = false;

		try {
			// clear the index
			$this->service = Injector::inst()->create('SilverStripe\Elastica\ElasticaService');
			$this->service->setTestMode(true);

			// A previous test may have deleted the index and then failed, so check for this
			if (!$this->service->getIndex()->exists()) {
				$this->service->getIndex()->create();
			}
			$this->service->reset();

			// FIXME - use request getVar instead?
			$_GET['progress'] = 20;

			// load fixtures
			$orig_fixture_file = static::$fixture_file;

			foreach (static::$ignoreFixtureFileFor as $testPattern) {
				$pattern = '/'.$testPattern.'/';
				if (preg_match($pattern, $this->getName())) {
					static::$fixture_file = null;
				}
			}
		} catch (Exception $e) {
			error_log('**** T1 EXCEPTION ' . $e->getMessage());
			$elasticaException = true;
		}

		// this has to be executed otherwise nesting exceptions occur
		parent::setUp();

		if ($elasticaException) {
			$this->fail('T1 Exception with Elasticsearch');
		}


		try {
			static::$fixture_file = $orig_fixture_file;

			$this->publishSiteTree();

			$this->service->reset();

			// index loaded fixtures
			$task = new ReindexTask($this->service);
			// null request is fine as no parameters used

			$task->run(null);
		} catch (Exception $e) {
			error_log('**** T2 EXCEPTION ' . $e->getMessage());
			$elasticaException = true;
		}

		if ($elasticaException) {
			$this->fail('T2 Exception with Elasticsearch');
		}
	}


	private function publishSiteTree() {
		foreach (SiteTree::get()->getIterator() as $page) {
			// temporarily disable Elasticsearch indexing, it will be done in a batch
			$page->IndexingOff = true;

			$page->publish('Stage','Live');
		}
	}


	//---- The HTML for search results is too long to check for, so instead check just the starting text ----

	/**
	 *Assert that the indexth matching css node has a prefix as expected
	 *
	 * Note: &nbsp; characters are stripped from the content; make sure that your assertions take this into account.
	 *
	 * @param string $selector A basic CSS selector, e.g. 'li.jobs h3'
	 * @param array|string $expectedMatches The content of at least one of the matched tags
	 * @throws PHPUnit_Framework_AssertionFailedError
	 * @return boolean
	 */
	public function assertSelectorStartsWithOrEquals($selector, $index, $expectedPrefix) {
		$items = $this->cssParser()->getBySelector($selector);

		$ctr = 0;
		foreach ($items as $item) {
			$text = strip_tags($item);
			$escaped = str_replace("'", "\'", $text);
			$ctr++;
		}

		$ctr = 0;
		$item = strip_tags($items[$index]);


		$errorMessage = "Failed to assert that '$item' started with '$expectedPrefix'";
		$this->assertStringStartsWith($expectedPrefix, $item, $errorMessage);

		return true;
	}


	/*
	Check all the nodes matching the selector for attribute name = expected value
	 */
	public function assertAttributeHasExactValue($selector, $attributeName, $expectedValue) {
		$items = $this->cssParser()->getBySelector($selector);
		foreach ($items as $item) {
			$this->assertEquals($expectedValue, $item['value']);
		}
	}


	public function assertAttributesHaveExactValues($selector, $expectedValues) {
		$attributeNames = array_keys($expectedValues);
		$items = $this->cssParser()->getBySelector($selector);
		foreach ($items as $item) {
			$actualValues = array();
			foreach ($attributeNames as $attributeName) {
				$actualValues[$attributeName] = (string)$item[$attributeName];
			}
			$this->assertEquals($expectedValues, $actualValues);
		}
	}


	public function assertNumberOfNodes($selector, $expectedAmount) {
		$items = $this->cssParser()->getBySelector($selector);
		foreach ($items as $item) {
			$text = strip_tags($item);
		}

		$ct = sizeof($items);
		$this->assertEquals($expectedAmount, $ct);
	}


	/* Collect an array of all the <ClassName>_<ID> search results, used for checking pagination */
	public function collateSearchResults() {
		$items = $this->cssParser()->getBySelector('div.searchResults .searchResult');
		$result = array();
		foreach ($items as $item) {
			$attr = $item->attributes()->id;
			array_push($result, $attr."");
		}

		return $result;
	}

}
