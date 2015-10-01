<?php

class ElasticsearchBaseTest extends SapphireTest {
	protected $extraDataObjects = array(
		'SearchableTestPage','FlickrPhoto','FlickrAuthor','FlickrSet','FlickrTag'
	);

	public function setUpOnce() {
		$config = Config::inst();
		$config->remove('Injector', 'SilverStripe\Elastica\ElasticaService');
		$constructor = array('constructor' => array('%$Elastica\Client', 'elastica_ss_module_test'));
		$config->update('Injector', 'SilverStripe\Elastica\ElasticaService', $constructor);

		// no need to index here as it's done when fixtures are loaded during setup method

		parent::setUpOnce();
	}


	public function setUp() {
		// this needs to be called in order to create the list of searchable
		// classes and fields that are available.  Simulates part of a build
		$classes = array('SearchableTestPage','SiteTree','Page','FlickrPhoto','FlickrSet',
			'FlickrTag', 'FlickrAuthor');
		$this->requireDefaultRecordsFrom = $classes;

		// add Searchable extension where appropriate
		FlickrSet::add_extension('SilverStripe\Elastica\Searchable');
		FlickrPhoto::add_extension('SilverStripe\Elastica\Searchable');
		FlickrTag::add_extension('SilverStripe\Elastica\Searchable');
		FlickrAuthor::add_extension('SilverStripe\Elastica\Searchable');
		SearchableTestPage::add_extension('SilverStripe\Elastica\Searchable');

		$this->service = Injector::inst()->create('SilverStripe\Elastica\ElasticaService');
		$this->service->reset();
		$this->service->startBulkIndex();

		// load fixtures
		parent::setUp();
		$this->service->endBulkIndex();

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