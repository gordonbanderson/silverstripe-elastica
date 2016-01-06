<?php


/**
 */
class ElasticSearchPageControllerTest extends ElasticsearchFunctionalTestBase
{
    public static $fixture_file = 'elastica/tests/lotsOfPhotos.yml';

    public function setUp()
    {
        parent::setUp();
        $esp = new ElasticSearchPage();
        $esp->Title = 'Search with aggregation';
        $esp->Content = 'Example search page with aggregation';
        $esp->Identifier = 'testwithagg';
        $esp->IndexingOff = true;
        $esp->URLSegment = 'search';
        $esp->SiteTreeOnly = false;
        $esp->ClassesToSearch = 'FlickrPhotoTO';
        $esp->SearchHelper = 'FlickrPhotoTOElasticaSearchHelper';
        $esp->write();

        $esp2 = new ElasticSearchPage();
        $esp2->Title = 'Search without aggregation';
        $esp2->Content = 'Example search page';
        $esp2->Identifier = 'testwithoutagg';
        $esp2->IndexingOff = true;
        $esp2->URLSegment = 'search';
        $esp2->SiteTreeOnly = false;
        $esp2->ClassesToSearch = 'FlickrPhotoTO';
        $esp2->ContentForEmptySearch = 'Content for empty search';
        $esp2->write();

        #NOTE: how to edit extra extra fields programatically
        $extraFields = array('Searchable' => 1, 'SimilarSearchable' => 1, 'Active' => 1,
            'Weight' => 1, );
        $esfs2 = $esp2->ElasticaSearchableFields();
        foreach ($esfs2 as $sf) {
            if ($sf->Name == 'Title' || $sf->Name == 'Description') {
                $esfs2->remove($sf);
                $esfs2->add($sf, $extraFields);
            }
        }
        $esp2->write();

        $esfs = $esp->ElasticaSearchableFields();

        foreach ($esfs as $sf) {
            if ($sf->Name == 'Title' || $sf->Name == 'Description') {
                $esfs->remove($sf);
                $esfs->add($sf, $extraFields);
            }
        }
        $esp->write();
        $esp->publish('Stage', 'Live');
        $esp2->publish('Stage', 'Live');
        $this->ElasticSearchPage = $esp;
        $this->ElasticSearchPage2 = $esp2;
    }

    public function testAggregationNoneSelected()
    {
        //SearchHelper

        $this->autoFollowRedirection = false;

        //Note pages need to be published, by default fixtures only reside in Stage
        $searchPageObj = $this->ElasticSearchPage;

        $url = $searchPageObj->Link();
        $searchPage = $this->get($searchPageObj->URLSegment);
        $this->assertEquals(200, $searchPage->getStatusCode());
        $url = rtrim($url, '/');

        $response = $this->get($url);

        $this->assertEquals(200, $response->getStatusCode());

        $this->assertSelectorStartsWithOrEquals('ul.iso span.count', 0, '(5)');
        $this->assertSelectorStartsWithOrEquals('ul.iso span.count', 1, '(11)');
        $this->assertSelectorStartsWithOrEquals('ul.iso span.count', 2, '(12)');
        $this->assertSelectorStartsWithOrEquals('ul.iso span.count', 3, '(13)');
        $this->assertSelectorStartsWithOrEquals('ul.iso span.count', 4, '(16)');
        $this->assertSelectorStartsWithOrEquals('ul.iso span.count', 5, '(13)');
        $this->assertSelectorStartsWithOrEquals('ul.iso span.count', 6, '(11)');
        $this->assertSelectorStartsWithOrEquals('ul.iso span.count', 7, '(19)');
        $this->assertSelectorStartsWithOrEquals('ul.focal_length span.count', 0, '(12)');
        $this->assertSelectorStartsWithOrEquals('ul.focal_length span.count', 1, '(11)');
        $this->assertSelectorStartsWithOrEquals('ul.focal_length span.count', 2, '(11)');
        $this->assertSelectorStartsWithOrEquals('ul.focal_length span.count', 3, '(20)');
        $this->assertSelectorStartsWithOrEquals('ul.focal_length span.count', 4, '(12)');
        $this->assertSelectorStartsWithOrEquals('ul.focal_length span.count', 5, '(17)');
        $this->assertSelectorStartsWithOrEquals('ul.focal_length span.count', 6, '(17)');
        $this->assertSelectorStartsWithOrEquals('ul.shutter_speed span.count', 0, '(17)');
        $this->assertSelectorStartsWithOrEquals('ul.shutter_speed span.count', 1, '(15)');
        $this->assertSelectorStartsWithOrEquals('ul.shutter_speed span.count', 2, '(17)');
        $this->assertSelectorStartsWithOrEquals('ul.shutter_speed span.count', 3, '(10)');
        $this->assertSelectorStartsWithOrEquals('ul.shutter_speed span.count', 4, '(18)');
        $this->assertSelectorStartsWithOrEquals('ul.shutter_speed span.count', 5, '(1)');
        $this->assertSelectorStartsWithOrEquals('ul.shutter_speed span.count', 6, '(10)');
        $this->assertSelectorStartsWithOrEquals('ul.shutter_speed span.count', 7, '(12)');
        $this->assertSelectorStartsWithOrEquals('ul.aperture span.count', 0, '(21)');
        $this->assertSelectorStartsWithOrEquals('ul.aperture span.count', 1, '(23)');
        $this->assertSelectorStartsWithOrEquals('ul.aperture span.count', 2, '(17)');
        $this->assertSelectorStartsWithOrEquals('ul.aperture span.count', 3, '(16)');
        $this->assertSelectorStartsWithOrEquals('ul.aperture span.count', 4, '(23)');
        $this->assertSelectorStartsWithOrEquals('ul.aspect span.count', 0, '(9)');
        $this->assertSelectorStartsWithOrEquals('ul.aspect span.count', 1, '(31)');
        $this->assertSelectorStartsWithOrEquals('ul.aspect span.count', 2, '(16)');
        $this->assertSelectorStartsWithOrEquals('ul.aspect span.count', 3, '(39)');
        $this->assertSelectorStartsWithOrEquals('ul.aspect span.count', 4, '(5)');
    }

    public function testAggregationOneSelected()
    {
        //SearchHelper

        $this->autoFollowRedirection = false;

        //Note pages need to be published, by default fixtures only reside in Stage
        $searchPageObj = $this->ElasticSearchPage;

        $url = $searchPageObj->Link();
        $searchPage = $this->get($searchPageObj->URLSegment);
        $this->assertEquals(200, $searchPage->getStatusCode());
        $url = rtrim($url, '/');
        $url .= '?ISO=400';

        $response = $this->get($url);

        $this->assertEquals(200, $response->getStatusCode());

        // These are less than in the no facets selected case, as expected
        $this->assertSelectorStartsWithOrEquals('ul.focal_length span.count', 0, '(2)');
        $this->assertSelectorStartsWithOrEquals('ul.focal_length span.count', 1, '(1)');
        $this->assertSelectorStartsWithOrEquals('ul.focal_length span.count', 2, '(2)');
        $this->assertSelectorStartsWithOrEquals('ul.focal_length span.count', 3, '(1)');
        $this->assertSelectorStartsWithOrEquals('ul.focal_length span.count', 4, '(3)');
        $this->assertSelectorStartsWithOrEquals('ul.focal_length span.count', 5, '(1)');
        $this->assertSelectorStartsWithOrEquals('ul.focal_length span.count', 6, '(3)');
        $this->assertSelectorStartsWithOrEquals('ul.shutter_speed span.count', 0, '(3)');
        $this->assertSelectorStartsWithOrEquals('ul.shutter_speed span.count', 1, '(2)');
        $this->assertSelectorStartsWithOrEquals('ul.shutter_speed span.count', 2, '(2)');
        $this->assertSelectorStartsWithOrEquals('ul.shutter_speed span.count', 3, '(1)');
        $this->assertSelectorStartsWithOrEquals('ul.shutter_speed  span.count', 4, '(2)');
        $this->assertSelectorStartsWithOrEquals('ul.shutter_speed span.count', 5, '(3)');
        $this->assertSelectorStartsWithOrEquals('ul.aperture span.count', 0, '(3)');
        $this->assertSelectorStartsWithOrEquals('ul.aperture span.count', 1, '(4)');
        $this->assertSelectorStartsWithOrEquals('ul.aperture span.count', 2, '(3)');
        $this->assertSelectorStartsWithOrEquals('ul.aperture span.count', 3, '(1)');
        $this->assertSelectorStartsWithOrEquals('ul.aperture span.count', 4, '(2)');
        $this->assertSelectorStartsWithOrEquals('ul.aspect span.count', 0, '(0)');
        $this->assertSelectorStartsWithOrEquals('ul.aspect span.count', 1, '(4)');
        $this->assertSelectorStartsWithOrEquals('ul.aspect span.count', 2, '(1)');
        $this->assertSelectorStartsWithOrEquals('ul.aspect span.count', 3, '(7)');
        $this->assertSelectorStartsWithOrEquals('ul.aspect span.count', 4, '(1)');
    }

    public function testAggregationTwoSelected()
    {
        //SearchHelper

        $this->autoFollowRedirection = false;

        //Note pages need to be published, by default fixtures only reside in Stage
        $searchPageObj = $this->ElasticSearchPage;

        $url = $searchPageObj->Link();
        $searchPage = $this->get($searchPageObj->URLSegment);
        $this->assertEquals(200, $searchPage->getStatusCode());
        $url = rtrim($url, '/');
        $url .= '?ISO=400&ShutterSpeed=2%2F250';

        $response = $this->get($url);
        $this->assertEquals(200, $response->getStatusCode());

        // These are less than in the one facet selected case, as expected
        $this->assertSelectorStartsWithOrEquals('ul.focal_length span.count', 0, '(1)');
        $this->assertSelectorStartsWithOrEquals('ul.focal_length span.count', 1, '(2)');
        $this->assertSelectorStartsWithOrEquals('ul.aperture span.count', 0, '(1)');
        $this->assertSelectorStartsWithOrEquals('ul.aperture span.count', 1, '(1)');
        $this->assertSelectorStartsWithOrEquals('ul.aperture span.count', 2, '(1)');
        $this->assertSelectorStartsWithOrEquals('ul.aspect span.count', 0, '(0)');
        $this->assertSelectorStartsWithOrEquals('ul.aspect span.count', 1, '(1)');
        $this->assertSelectorStartsWithOrEquals('ul.aspect span.count', 2, '(0)');
        $this->assertSelectorStartsWithOrEquals('ul.aspect span.count', 3, '(2)');
        $this->assertSelectorStartsWithOrEquals('ul.aspect span.count', 4, '(0)');
    }

    public function testAggregationThreeSelected()
    {
        //SearchHelper

        $this->autoFollowRedirection = false;

        //Note pages need to be published, by default fixtures only reside in Stage
        $searchPageObj = $this->ElasticSearchPage;

        $url = $searchPageObj->Link();
        $searchPage = $this->get($searchPageObj->URLSegment);
        $this->assertEquals(200, $searchPage->getStatusCode());
        $url = rtrim($url, '/');
        $url .= '?ISO=400&ShutterSpeed=2%2F250&Aspect=Vertical';

        $response = $this->get($url);
        $this->assertEquals(200, $response->getStatusCode());

        // These are less than in the one facet selected case, as expected
        $this->assertSelectorStartsWithOrEquals('span.count', 0, '(2)');
        $this->assertSelectorStartsWithOrEquals('span.count', 1, '(1)');
        $this->assertSelectorStartsWithOrEquals('span.count', 2, '(1)');
    }

    public function testQueryIsEmpty()
    {
        //Note pages need to be published, by default fixtures only reside in Stage
        $searchPageObj = $this->ElasticSearchPage2;
        $url = rtrim($searchPageObj->Link(), '/');
        $response = $this->get($url);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertSelectorStartsWithOrEquals('div.contentForEmptySearch', 0,
            $searchPageObj->ContentForEmptySearch);

        $url .= '?q=';
        $response = $this->get($url);
        $this->assertEquals(200,
            $response->getStatusCode());
        $this->assertSelectorStartsWithOrEquals('div.contentForEmptySearch', 0,
            $searchPageObj->ContentForEmptySearch);

        $url .= 'farming';
        $response = $this->get($url);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertExactHTMLMatchBySelector('div.contentForEmptySearch', array());
    }

    public function testQueryInSearchBoxForOneFormOnly()
    {
        $searchPageObj = $this->ElasticSearchPage2;
        $url = rtrim($searchPageObj->Link(), '/');
        $url .= '?q=Auckland&sfid='.$searchPageObj->Identifier;
        $response = $this->get($url);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertAttributeHasExactValue('#ElasticSearchForm_SearchForm_q', 'q',
            'Auckland');
    }

    /*
    Check the search form attributes for autocomplete info
     */
    public function testSearchFormAutocompleteEnabled()
    {
        // prepeare autocomplete field
        $this->autoFollowRedirection = false;

        //Note pages need to be published, by default fixtures only reside in Stage
        $searchPageObj = $this->ElasticSearchPage2;
        $searchPageObj->ClassesToSearch = 'FlickrPhotoTO';
        $filter = array('ClazzName' => 'FlickrPhotoTO', 'Name' => 'Title');
        $sfid = SearchableField::get()->filter($filter)->first()->ID;
        $searchPageObj->AutoCompleteFieldID = $sfid;
        $pageLength = 10; // the default
        $searchPageObj->ResultsPerPage = $pageLength;
        $searchPageObj->write();
        $searchPageObj->publish('Stage', 'Live');

        $url = rtrim($searchPageObj->Link(), '/');
        $response = $this->get($url);
        $this->assertEquals(200, $response->getStatusCode());

        // note, need to test with strings
        $expected = array(
            'data-autocomplete' => 'true',
            'data-autocomplete-field' => 'Title',
            'data-autocomplete-field' => 'Title',
            'data-autocomplete-classes' => 'FlickrPhotoTO',
            'data-autocomplete-sitetree' => '0',
            'data-autocomplete-source' => '/search-2/',
        );
        $this->assertAttributesHaveExactValues('#ElasticSearchForm_SearchForm_q', $expected);
    }

    public function testTemplateOverrideExtension()
    {
        if (!class_exists('PageControllerTemplateOverrideExtension')) {
            $this->markTestSkipped('PageControllerTemplateOverrideExtension not installed');
        }

        Page_Controller::remove_extension('PageControllerTemplateOverrideExtension');
        $searchPageObj = $this->ElasticSearchPage2;

        $url = rtrim($searchPageObj->Link(), '/');
        $response = $this->get($url);
        $this->assertEquals(0, PageControllerTemplateOverrideExtension::getTemplateOverrideCounter());
        Page_Controller::add_extension('PageControllerTemplateOverrideExtension');
        $searchPageObj = $this->ElasticSearchPage2;
        $url = rtrim($searchPageObj->Link(), '/');
        $response = $this->get($url);
        $this->assertEquals(1, PageControllerTemplateOverrideExtension::getTemplateOverrideCounter());

        $url .= '/similar/FlickrPhotoTO/77';
        $response = $this->get($url);

        //check the template override method was called
        $this->assertEquals(2, PageControllerTemplateOverrideExtension::getTemplateOverrideCounter());

        Page_Controller::remove_extension('PageControllerTemplateOverrideExtension');
    }

    public function testSimilarNotSearchable()
    {
        $searchPageObj = $this->ElasticSearchPage2;
        $url = rtrim($searchPageObj->Link(), '/');
        $url .= '/similar/Member/1';
        $response = $this->get($url);
        $this->assertEquals(200, $response->getStatusCode());

        $this->assertSelectorStartsWithOrEquals('div.error', 0,
            'Class Member is either not found or not searchable');
    }

    public function testSimilarNull()
    {
        $searchPageObj = $this->ElasticSearchPage2;
        $url = rtrim($searchPageObj->Link(), '/');
        $url .= '/similar/Member/0';
        $response = $this->get($url);
        $this->assertEquals(200, $response->getStatusCode());

        $this->assertSelectorStartsWithOrEquals('div.error', 0,
            'Class Member is either not found or not searchable');
    }

    public function testSimilarClassDoesNotExist()
    {
        $searchPageObj = $this->ElasticSearchPage2;
        $url = rtrim($searchPageObj->Link(), '/');
        $url .= '/similar/asdfsadfsfd/4';
        $response = $this->get($url);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertSelectorStartsWithOrEquals('div.error', 0,
            'Class asdfsadfsfd is either not found or not searchable');
    }

    public function testSimilarSearchServerDown()
    {
        $searchPageObj = $this->ElasticSearchPage2;
        $url = rtrim($searchPageObj->Link(), '/');
        $url .= '/similar/FlickrPhotoTO/77?ServerDown=1';
        $response = $this->get($url);
        $this->assertSelectorStartsWithOrEquals('div.error', 0,
            'Unable to connect to search server');
    }

    public function testNormalSearchServerDown()
    {
        $searchPageObj = $this->ElasticSearchPage2;
        $url = rtrim($searchPageObj->Link(), '/');
        $url .= '?q=Zealand&ServerDown=1';
        $response = $this->get($url);
        $this->assertSelectorStartsWithOrEquals('div.error', 0,
            'Unable to connect to search server');
    }

    public function testSimilarValid()
    {
        $searchPageObj = $this->ElasticSearchPage2;
        $url = rtrim($searchPageObj->Link(), '/');
        $url .= '/similar/FlickrPhotoTO/77';
        $response = $this->get($url);
        $ctr = 0;

        // results vary slightly due to sharding, hence check for a string instead of absolute results
        while ($ctr < 18) {
            $this->assertSelectorContains('div.searchResult a', $ctr, 'New Orleans, Southern Pacific');
            ++$ctr;
            $this->assertSelectorStartsWithOrEquals('div.searchResult a', $ctr, 'Similar');
            ++$ctr;
        }
    }

    /*
    Search for New Zealind and get search results for New Zealand.  Show option to click on
    actual search of New Zealind
     */
    public function testSuggestion()
    {
        $searchPageObj = $this->ElasticSearchPage2;
        $url = rtrim($searchPageObj->Link(), '/');
        $url .= '?q=New%20Zealind&TestMode=true';
        $response = $this->get($url);
        $this->assertEquals(200, $response->getStatusCode());
        $ctr = 0;
        $this->assertSelectorStartsWithOrEquals('p.showingResultsForMsg', 0, 'Showing results for ');
        $this->assertSelectorStartsWithOrEquals('p.showingResultsForMsg a', 0, 'New ');
        $this->assertSelectorStartsWithOrEquals('p.showingResultsForMsg strong.hl', 0, 'Zealand');

        $this->assertSelectorStartsWithOrEquals('p.searchOriginalQueryMsg', 0, 'Search instead for ');
        $this->assertSelectorStartsWithOrEquals('p.searchOriginalQueryMsg a', 0, 'New Zealind');

        // simulate following the link to search for 'New Zealind'
        $url = rtrim($searchPageObj->Link(), '/');
        $url .= '?q=New Zealind&is=1';
        $response = $this->get($url);
        $this->assertEquals(200, $response->getStatusCode());

                // results vary slightly due to sharding, hence check for a string instead of absolute results
        while ($ctr < 18) {
            $this->assertSelectorContains('div.searchResult a', $ctr, 'New', true);
            ++$ctr;
            $this->assertSelectorStartsWithOrEquals('div.searchResult a', $ctr, 'Similar');
            ++$ctr;
        }

        //no suggestions shown, the is flag prevents this
        $this->assertExactHTMLMatchBySelector('p.showingResultsForMsg', array());
        $this->assertExactHTMLMatchBySelector('p.searchOriginalQueryMsg', array());

        // reconfirm lack of suggestions when searching for 'New Zealand' with the is flag set
        $url = rtrim($searchPageObj->Link(), '/');
        $url .= '?q=New Zealand&is=1';
        $response = $this->get($url);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertExactHTMLMatchBySelector('p.showingResultsForMsg', array());
        $this->assertExactHTMLMatchBySelector('p.searchOriginalQueryMsg', array());
    }
/*
<p class="showingResultsForMsg">Showing results for <a href="./?q=New Zealand">New <strong class="hl">Zealand</strong></a></p>
<p class="searchOriginalQueryMsg">Search instead for <a href="/search-2?q=New Zealind&is=1">New Zealind</a></p>


 */

    public function testSearchOnePageNoAggregation()
    {
        $this->enableHighlights();
        $this->autoFollowRedirection = false;
        $searchTerm = 'mineralogy';

        //Note pages need to be published, by default fixtures only reside in Stage
        $searchPageObj = $this->ElasticSearchPage2;
        $url = rtrim($searchPageObj->Link(), '/');
        $url = $url.'?q='.$searchTerm;
        $response = $this->get($url);
        $this->assertEquals(200, $response->getStatusCode());

        //There are 3 results for mineralogy
        $this->assertSelectorStartsWithOrEquals('div.resultsFound', 0,
            'Page 1 of 1  (3 results found');

        //Check all the result highlights for mineralogy matches
        $this->assertSelectorStartsWithOrEquals('strong.hl', 1, 'mineralogy');
        $this->assertSelectorStartsWithOrEquals('strong.hl', 2, 'Mineralogy');
        $this->assertSelectorStartsWithOrEquals('strong.hl', 3, 'mineralogy');
        $this->assertSelectorStartsWithOrEquals('strong.hl', 4, 'Mineralogy');
        $this->assertSelectorStartsWithOrEquals('strong.hl', 5, 'mineralogy');

        // Check the start text of the 3 results
        $this->assertSelectorStartsWithOrEquals('div.searchResult h4 a', 0,
            'Image taken from page 273 of');
        $this->assertSelectorStartsWithOrEquals('div.searchResult h4 a', 1,
            'Image taken from page 69 of');
        $this->assertSelectorStartsWithOrEquals('div.searchResult h4 a', 2,
            'Image taken from page 142 of');

        // No span.count means no aggregations
        $this->assertExactHTMLMatchBySelector('span.count', array());
    }

    /* When a search is posted, should redirect to the same URL with the search term attached.  This
    means that searches can be bookmarked if so required */
    public function testRedirection()
    {
        $this->autoFollowRedirection = false;

        //Note pages need to be published, by default fixtures only reside in Stage
        $searchPageObj = $this->ElasticSearchPage;
        $url = $searchPageObj->Link();
        $searchPage = $this->get($searchPageObj->URLSegment);
        $this->assertEquals(200, $searchPage->getStatusCode());

        $response = $this->submitForm('ElasticSearchForm_SearchForm', null, array(
            'q' => 'New Zealand',
        ));

        $url = rtrim($url, '/');
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals($url.'?q=New Zealand&sfid=testwithagg', $response->getHeader('Location'));
    }

    /*
    Add test for redirection of /search/?q=XXX to /search?q=XXX
     */

    /*
    Test a search for an uncommon term, no pagination here
     */
    public function testSearchOnePage()
    {
        $this->enableHighlights();
        $this->autoFollowRedirection = false;
        $searchTerm = 'mineralogy';

        //Note pages need to be published, by default fixtures only reside in Stage
        $searchPageObj = $this->ElasticSearchPage;
        $url = rtrim($searchPageObj->Link(), '/');
        $url = $url.'?q='.$searchTerm;
        $response = $this->get($url);
        $this->assertEquals(200, $response->getStatusCode());

        //There are 3 results for mineralogy
        $this->assertSelectorStartsWithOrEquals('div.resultsFound', 0,
            'Page 1 of 1  (3 results found');

        //Check all the result highlights for mineralogy matches
        $this->assertSelectorStartsWithOrEquals('strong.hl', 1, 'mineralogy');
        $this->assertSelectorStartsWithOrEquals('strong.hl', 2, 'Mineralogy');
        $this->assertSelectorStartsWithOrEquals('strong.hl', 3, 'mineralogy');
        $this->assertSelectorStartsWithOrEquals('strong.hl', 4, 'Mineralogy');
        $this->assertSelectorStartsWithOrEquals('strong.hl', 5, 'mineralogy');

        // Check the start text of the 3 results
        $this->assertSelectorStartsWithOrEquals('div.searchResult h4 a', 0,
            'Image taken from page 273 of');
        $this->assertSelectorStartsWithOrEquals('div.searchResult h4 a', 1,
            'Image taken from page 69 of');
        $this->assertSelectorStartsWithOrEquals('div.searchResult h4 a', 2,
            'Image taken from page 142 of');
    }

    /*
    Test a search for a common term, in order to induce pagination
     */
    public function testSiteTreeSearch()
    {
        $this->enableHighlights();
        $this->autoFollowRedirection = false;

        //One of the default pages
        $searchTerm = 'Contact Us';

        //Note pages need to be published, by default fixtures only reside in Stage
        $searchPageObj = $this->ElasticSearchPage;
        $searchPageObj->SiteTreeOnly = true;
        $searchPageObj->write();
        $searchPageObj->publish('Stage', 'Live');

        $pageLength = 10; // the default
        $searchPageObj->ResultsPerPage = $pageLength;
        $url = rtrim($searchPageObj->Link(), '/');
        $url = $url.'?q='.$searchTerm;
        $firstPageURL = $url;
        $response = $this->get($url);
        $this->assertEquals(200, $response->getStatusCode());

        //There are 2 results for 'Contact Us', as 'About Us' has an Us in the title.
        $this->assertSelectorStartsWithOrEquals('div.resultsFound', 0,
            'Page 1 of 1  (2 results found in');

        //The classname 'searchResults' appears to be matching the contained 'searchResult', hence
        //the apparently erroneous addition of 1 to the required 2
        $this->assertNumberOfNodes('div.searchResult', 3);

        $this->assertSelectorStartsWithOrEquals('strong.hl', 0, 'Contact'); // CONTACT US
        $this->assertSelectorStartsWithOrEquals('strong.hl', 1, 'Us'); // Contact US
        $this->assertSelectorStartsWithOrEquals('strong.hl', 2, 'Us'); // About US
    }

    /*
    Test a search for a common term, in order to induce pagination
     */
    public function testSearchSeveralPagesPage()
    {
        $this->enableHighlights();
        $this->autoFollowRedirection = false;
        $searchTerm = 'railroad';

        //Note pages need to be published, by default fixtures only reside in Stage
        $searchPageObj = $this->ElasticSearchPage;
        $pageLength = 10; // the default
        $searchPageObj->ResultsPerPage = $pageLength;
        $url = rtrim($searchPageObj->Link(), '/');
        $url = $url.'?q='.$searchTerm;
        $firstPageURL = $url;
        $response = $this->get($url);
        $this->assertEquals(200, $response->getStatusCode());

        //There are 3 results for mineralogy
        $this->assertSelectorStartsWithOrEquals('div.resultsFound', 0,
            'Page 1 of 2  (11 results found in');

        //The classname 'searchResults' appears to be matching the contained 'searchResult', hence
        //the apparently erroneous addition of 1 to the required 10
        $this->assertNumberOfNodes('div.searchResult', 11);

        //Check for a couple of highlighed 'Railroad' terms
        $this->assertSelectorStartsWithOrEquals('strong.hl', 0, 'Railroad');
        $this->assertSelectorStartsWithOrEquals('strong.hl', 1, 'Railroad');

        $this->assertSelectorStartsWithOrEquals('div.pagination a', 0, '2');
        $this->assertSelectorStartsWithOrEquals('div.pagination a.next', 0, '→');

        $resultsP1 = $this->collateSearchResults();

        $page2url = $url.'&start='.$pageLength;

        //Check pagination on page 2
        $response2 = $this->get($page2url);
        $this->assertEquals(200, $response2->getStatusCode());

        //FIXME pluralisation probably needs fixed here, change test later acoordingly
        $this->assertSelectorStartsWithOrEquals('div.resultsFound', 0,
            'Page 2 of 2  (11 results found in');

        //The classname 'searchResults' appears to be matching the contained 'searchResult', hence
        //the apparently erroneous addition of 1 to the required 1
        $this->assertNumberOfNodes('div.searchResult', 2);

        $this->assertSelectorStartsWithOrEquals('div.pagination a', 1, '1');
        $this->assertSelectorStartsWithOrEquals('div.pagination a.prev', 0, '←');

        $resultsP2 = $this->collateSearchResults();

        $resultsFrom2Pages = array_merge($resultsP1, $resultsP2);

        //there are 11 results in total
        $this->assertEquals(11, sizeof($resultsFrom2Pages));

        //increase the number of results and assert that they are the same as per pages 1,2 joined
        $searchPageObj->ResultsPerPage = 20;
        $searchPageObj->write();
        $searchPageObj->publish('Stage', 'Live');
        $response3 = $this->get($firstPageURL);
    }

    private function enableHighlights()
    {
        foreach (SearchableField::get()->filter('Name', 'Title') as $sf) {
            $sf->ShowHighlights = true;
            $sf->write();
        }

        foreach (SearchableField::get()->filter('Name', 'Content') as $sf) {
            $sf->ShowHighlights = true;
            $sf->write();
        }

        //FIXME - do this with ORM
        //$sql = "UPDATE ElasticSearchPage_ElasticaSearchableFields SET "
    }
}
