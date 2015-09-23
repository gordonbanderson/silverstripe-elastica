<?php

/**
 * Teste the functionality of the Searchable extension
 * @package elastica
 */
class IndexSettingsTest extends SapphireTest {
	//public static $fixture_file = 'elastica/tests/ElasticaTest.yml';

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

	/*
	Compare with structure as per
	https://www.elastic.co/guide/en/elasticsearch/reference/current/analysis-lang-analyzer.html#english-analyzer

	PUT /my_index
{
	"settings": {
		"analysis": {
			"char_filter":
			"tokenizer":
			"filter":
			"analyzer":
		}
	}
}
	 */
	public function testEnglishIndexSettings() {
		$indexSettings = new EnglishIndexSettings();
		$config = $indexSettings->generateConfig();
		$config = $config['index'];

		// check stemmed settings first
		print_r($config);

		// check for existence and then actual values of analysis
		$filters = $config['analysis']['filter'];

		$stopwordFilter = $filters['stopword_filter'];
		$this->assertEquals('stop', $stopwordFilter['type']);
		$this->assertEquals(
			$indexSettings->getStopWords(),
			$stopwordFilter['stopwords']
		);

		// check for existence and then actual values of analyzer
		$analyzers = $config['analysis']['analyzer'];
		$stemmedAnalyzer = $analyzers['stemmed'];

		$actual = $stemmedAnalyzer['tokenizer'];
		$filterNames = $stemmedAnalyzer['filter'];

		$this->assertEquals('stopword_filter', $filterNames[0]);

		// check the unstemmed analyzer
		$unstemmedAnalyzer = $analyzers['unstemmed'];
		$this->assertEquals('custom', $unstemmedAnalyzer['type']);
		$this->assertEquals('standard', $unstemmedAnalyzer['tokenizer']);
		$this->assertEquals('html_strip', $unstemmedAnalyzer['char_filter'][0]);
		$this->assertEquals('stopword_filter', $unstemmedAnalyzer['filter'][0]);
	}



}
