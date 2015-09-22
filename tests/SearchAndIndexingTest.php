<?php

/**
 * Teste the functionality of the Searchable extension
 * @package elastica
 */
class SearchAndIndexingTest extends SapphireTest {
	//public static $fixture_file = 'elastica/tests/lotsOfPhotos.yml';
	public static $fixture_file = 'elastica/tests/lotsOfPhotos.yml';

	protected $extraDataObjects = array(
		'SearchableTestPage','FlickrPhoto','FlickrAuthor','FlickrSet','FlickrTag'
	);

	public function setUp() {
		// this needs to be called in order to create the list of searchable
		// classes and fields that are available.  Simulates part of a build
		$classes = array('SearchableTestPage','SiteTree','Page','FlickrPhoto','FlickrSet',
			'FlickrTag', 'FlickrAuthor', 'FlickrSet');
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

	/**
	 * Test searching
	 */
	public function testSearching() {
		$elasticService = Injector::inst()->create('SilverStripe\Elastica\ElasticaService');
		//$elasticService->setLocale($this->locale);

		$task = new SilverStripe\Elastica\ReindexTask($elasticService);
		$task->run(null);
		$flickrPhoto = $this->objFromFixture('FlickrPhoto', 'photo0001');

		$query = '<b>';
		$es = new \ElasticSearcher();
		$es->setStart(0);
		$es->setPageLength(20);
		$es->addFilter('IsInSiteTree', false);
		$results = $es->search($query);
		foreach ($results as $result) {
			echo($result->Title);
			if ($result->SearchHighlightsByField->Content) {
				foreach ($result->SearchHighlightsByField->Content as $highlight) {
					echo("- ".$highlight->Snippet);
				}
			}

			echo "\n\n";
		}
	}


}
