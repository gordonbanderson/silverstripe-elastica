<?php

use SilverStripe\Elastica\ReindexTask;
use SilverStripe\Elastica\ElasticaService;


class ElasticsearchBaseTest extends SapphireTest {

	public static $ignoreFixtureFileFor = array();

	protected $extraDataObjects = array(
		'SearchableTestPage','FlickrPhotoTO','FlickrAuthorTO','FlickrSetTO','FlickrTagTO',
		'SearchableTestFatherPage','SearchableTestGrandFatherPage'
	);

	public function setUpOnce() {

		// add Searchable extension where appropriate
		FlickrSetTO::add_extension('SilverStripe\Elastica\Searchable');
		FlickrPhotoTO::add_extension('SilverStripe\Elastica\Searchable');
		FlickrTagTO::add_extension('SilverStripe\Elastica\Searchable');
		FlickrAuthorTO::add_extension('SilverStripe\Elastica\Searchable');
		SearchableTestPage::add_extension('SilverStripe\Elastica\Searchable');

		$config = Config::inst();
		$config->remove('Injector', 'SilverStripe\Elastica\ElasticaService');
		$constructor = array('constructor' => array('%$Elastica\Client', 'elastica_ss_module_test'));
		$config->update('Injector', 'SilverStripe\Elastica\ElasticaService', $constructor);
		ElasticaService::setIsInTestMode();
		parent::setUpOnce();
	}


	public function setUp() {
		// no need to index here as it's done when fixtures are loaded during setup method
		$cache = SS_Cache::factory('elasticsearch');
		$cache->clean(Zend_Cache::CLEANING_MODE_ALL);
		SS_Cache::set_cache_lifetime('elasticsearch', 3600, 1000);

		// this needs to be called in order to create the list of searchable
		// classes and fields that are available.  Simulates part of a build
		$classes = array('SearchableTestPage','SiteTree','Page','FlickrPhotoTO','FlickrSetTO',
			'FlickrTagTO', 'FlickrAuthorTO');
		$this->requireDefaultRecordsFrom = $classes;


		// clear the index
		$this->service = Injector::inst()->create('SilverStripe\Elastica\ElasticaService');

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

		echo "\n\n\n\nEXECUTING TEST {$this->getName()}, FIXTURES=".static::$fixture_file."\n";

		parent::setUp();
		static::$fixture_file = $orig_fixture_file;

		$this->publishSiteTree();

		$this->service->reset();

		// index loaded fixtures
		$task = new ReindexTask($this->service);
		// null request is fine as no parameters used

		$task->run(null);

	}


	protected function devBuild() {
		$task = new \BuildTask();
		// null request is fine as no parameters used
		$task->run(null);
	}


	private function publishSiteTree() {
		foreach (SiteTree::get()->getIterator() as $page) {
			// temporarily disable Elasticsearch indexing, it will be done in a batch
			$page->IndexingOff = true;

			echo "Publishing ".$page->Title."\n";
			$page->publish('Stage','Live');
		}
	}


	/*
	Helper methods for testing CMS fields
	 */
	public function checkTabExists($fields, $tabName) {
		$tab = $fields->findOrMakeTab("Root.{$tabName}");
		$this->assertEquals($tabName, $tab->getName());
		$this->assertEquals("Root_${tabName}", $tab->id());
		return $tab;
	}


	public function checkFieldExists($tab,$fieldName) {
		$field = $tab->fieldByName($fieldName);
		$this->assertTrue($field != null);
		return $field;
	}


	/**
	 * From https://jtreminio.com/2013/03/unit-testing-tutorial-part-3-testing-protected-private-methods-coverage-reports-and-crap/
	 * Call protected/private method of a class.
	 *
	 * @param object &$object    Instantiated object that we will run method on.
	 * @param string $methodName Method name to call
	 * @param array  $parameters Array of parameters to pass into method.
	 *
	 * @return mixed Method return.
	 */
	public function invokeMethod(&$object, $methodName, array $parameters = array())
	{
	    $reflection = new \ReflectionClass(get_class($object));
	    $method = $reflection->getMethod($methodName);
	    $method->setAccessible(true);

	    return $method->invokeArgs($object, $parameters);
	}


	public function checkNumberOfIndexedDocuments($expectedAmount) {
		$index = $this->service->getIndex();
		$status = $index->getStatus()->getData();

		$numberDocsInIndex = -1; // flag value for not yet indexed
		if (isset($status['indices']['elastica_ss_module_test_en_us']['docs'])) {
			$numberDocsInIndex = $status['indices']['elastica_ss_module_test_en_us']['docs']['num_docs'];
		}

		$this->assertEquals($expectedAmount,$numberDocsInIndex);
	}

	/*
	Get the number of documents in an index.  It is assumed the index exists, if not the test will fail
	 */
	public function getNumberOfIndexedDocuments() {
		$index = $this->service->getIndex();
		$status = $index->getStatus()->getData();

		$numberDocsInIndex = -1; // flag value for not yet indexed
		if (isset($status['indices']['elastica_ss_module_test_en_us']['docs'])) {
			$numberDocsInIndex = $status['indices']['elastica_ss_module_test_en_us']['docs']['num_docs'];
		}

		$this->assertGreaterThan(-1, $numberDocsInIndex);
		return $numberDocsInIndex;
	}
}
