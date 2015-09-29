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

		// load fixtures
		parent::setUp();
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
}
