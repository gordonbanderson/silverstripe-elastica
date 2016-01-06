<?php

/**
 * Test the functionality of the Searchable extension.
 */
class IndexSettingsTest extends ElasticsearchBaseTest
{
    //public static $fixture_file = 'elastica/tests/ElasticaTest.yml';
    public function setUp()
    {
        // this needs to be called in order to create the list of searchable
        // classes and fields that are available.  Simulates part of a build
        $classes = array('SearchableTestPage', 'SiteTree', 'Page', 'FlickrPhotoTO', 'FlickrSetTO',
            'FlickrTagTO', 'FlickrAuthorTO', 'FlickrSetTO', );
        $this->requireDefaultRecordsFrom = $classes;

        // add Searchable extension where appropriate
        FlickrSetTO::add_extension('SilverStripe\Elastica\Searchable');
        FlickrPhotoTO::add_extension('SilverStripe\Elastica\Searchable');
        FlickrTagTO::add_extension('SilverStripe\Elastica\Searchable');
        FlickrAuthorTO::add_extension('SilverStripe\Elastica\Searchable');
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
    public function testEnglishIndexSettings()
    {
        $indexSettings = new EnglishIndexSettings();
        $config = $indexSettings->generateConfig();
        $config = $config['index'];

        // Check filters
        $filters = $config['analysis']['filter'];

        $stopwordFilter = $filters['english_stop'];
        $this->assertEquals('stop', $stopwordFilter['type']);
        $this->assertEquals(
            $indexSettings->getStopWords(),
            $stopwordFilter['stopwords']
        );
        $this->assertFalse(isset($filters['stopword_filter']));

        $english_stemmer = $filters['english_stemmer'];
        $expected = array('type' => 'stemmer', 'language' => 'english');
        $this->assertEquals($expected, $english_stemmer);

        $english_possessive_stemmer = $filters['english_possessive_stemmer'];
        $expected = array('type' => 'stemmer', 'language' => 'possessive_english');
        $this->assertEquals($expected, $english_possessive_stemmer);

        $english_snowball = $filters['english_snowball'];
        $expected = array('type' => 'snowball', 'language' => 'English');
        $this->assertEquals($expected, $english_snowball);

        $no_single_chars = $filters['no_single_chars'];
        $expected = array('type' => 'length', 'min' => '2');
        $this->assertEquals($expected, $no_single_chars);

        $english_stemmer = $filters['english_stemmer'];
        $expected = array('type' => 'stemmer', 'language' => 'english');
        $this->assertEquals($expected, $english_stemmer);

        $autocomplete = $filters['autocomplete'];
        $expected = array(
            'type' => 'nGram',
            'min_gram' => 2,
            'max_gram' => 20,
            'token_chars' => array('letter', 'digit', 'punctuation', 'symbol'),
        );
        $this->assertEquals($expected, $autocomplete);

        $filter_shingle = $filters['filter_shingle'];
        $expected = array(
            'type' => 'shingle',
            'min_shingle_size' => '2',
            'max_shingle_size' => '2',
            'output_unigrams' => false,
        );
        $this->assertEquals($expected, $filter_shingle);

        // check for existence and then actual values of analyzer
        $analyzers = $config['analysis']['analyzer'];
        $stemmedAnalyzer = $analyzers['stemmed'];

        $actual = $stemmedAnalyzer['tokenizer'];
        $filterNames = $stemmedAnalyzer['filter'];

        $expected = array('no_single_chars', 'english_snowball', 'lowercase', 'english_stop');

        // check the unstemmed analyzer
        $unstemmedAnalyzer = $analyzers['unstemmed'];
        $this->assertEquals('custom', $unstemmedAnalyzer['type']);
        $this->assertEquals('uax_url_email', $unstemmedAnalyzer['tokenizer']);

        //Difference here is deliberate lack of a stemmer
        $expected = array('no_single_chars', 'lowercase', 'english_stop');
        $this->assertEquals($expected, $unstemmedAnalyzer['filter']);

        // Check autocomplete index analyzer
        $autocompleteIndexAnalyzer = $analyzers['autocomplete_index_analyzer'];
        $expected = array(
            'type' => 'custom',
            'tokenizer' => 'whitespace',
            'filter' => array('lowercase', 'asciifolding', 'autocomplete'),
        );
        $this->assertEquals($expected, $autocompleteIndexAnalyzer);

        // Check autocomplete search analyzer
        $autocompleteSearchAnalyzer = $analyzers['autocomplete_search_analyzer'];
        $expected = array(
            'type' => 'custom',
            'tokenizer' => 'whitespace',
            'filter' => array('lowercase', 'asciifolding'),
        );
        $this->assertEquals($expected, $autocompleteSearchAnalyzer);

        // Check shingles analyzer
        $shinglesAnalyzer = $analyzers['shingles'];
        $expected = array(
            'type' => 'custom',
            'tokenizer' => 'uax_url_email',
            'filter' => array('lowercase', 'filter_shingle'),
        );

        $this->assertEquals($expected, $shinglesAnalyzer);
    }

    public function testGetSetAsciiFolding()
    {
        $indexSettings = new EnglishIndexSettings();
        $indexSettings->setAsciiFolding(false);
        $this->assertFalse($indexSettings->getAsciiFolding());
        $indexSettings->setAsciiFolding(true);
        $this->assertTrue($indexSettings->getAsciiFolding());
    }
}
