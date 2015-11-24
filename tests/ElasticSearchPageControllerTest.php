<?php


/**
 * @package comments
 */
class ElasticSearchPageControllerTest extends ElasticsearchFunctionalTestBase {

	public static $fixture_file = 'elastica/tests/lotsOfPhotos.yml';


	public function setup() {
		parent::setup();
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

		//Simulate selecting Title and Description as searchable fields in the CMS interface
		$sfs = new ArrayList();

		#FIXME fixed - how to edit extra extra fields programatically
		$extraFields = array('Searchable' => 1, 'SimilarSearchable' => 1, 'Active' => 1,
			'Weight' => 1);
		$esfs2 = $esp2->ElasticaSearchableFields();
		foreach ($esfs2 as $sf) {
			if ($sf->Name == 'Title' || $sf->Name == 'Description') {
				$esfs2->remove($sf);
				$esfs2->add($sf, $extraFields);
			}
		}
		$esp2->write();

		$esfs= $esp->ElasticaSearchableFields();

		foreach ($esfs as $sf) {
			if ($sf->Name == 'Title' || $sf->Name == 'Description') {
				$esfs->remove($sf);
				$esfs->add($sf, $extraFields);
			}
		}
		$esp->write();


/*
		$espf1 = new SearchableField();
		$espf1->ElasticPageID = $esp->ID;
		$espf1->Active = true;
		$espf1->Searchable = true;
		$espf1->SimilarSearchable = true;
		$espf1->Name = 'Title';
		$espf1->Type = 'string';
		$esp->ElasticaSearchableFields()->add($espf1, $extraFields);
		$esp2->ElasticaSearchableFields()->add($espf1, $extraFields);

		$espf2 = new SearchableField();
		$espf2->ElasticPageID = $esp->ID;
		$espf2->Active = true;
		$espf2->Searchable = true;
		$espf2->SimilarSearchable = true;
		$espf2->Name = 'Description';
		$espf2->Type = 'string';
		$esp->ElasticaSearchableFields()->add($espf2);
		$esp2->ElasticaSearchableFields()->add($espf2);
*/
		$esp->publish('Stage','Live');
		$esp2->publish('Stage','Live');
		$this->ElasticSearchPage = $esp;
		$this->ElasticSearchPage2= $esp2;


		echo "CHECK MYSQL";
		/*
		ElasticSearchPage:
  search:
    Title: Search
    Content: Example Search Page
    ClassesToSearch: ''
    ResultsPerPage: 4
    SiteTreeOnly: true
    Identifier: testsearchpage
    IndexingOff: true
    URLSegment: search
		 */
	}


	public function testAggregationNoneSelected() {
		//SearchHelper

		$this->autoFollowRedirection = false;

		//Note pages need to be published, by default fixtures only reside in Stage
		$searchPageObj = $this->ElasticSearchPage;

		$url = $searchPageObj->Link();
		$searchPage = $this->get($searchPageObj->URLSegment);
		$this->assertEquals(200, $searchPage->getStatusCode());
		$url = rtrim($url,'/');

        $response = $this->get($url);
		print_r($response);
        $this->assertEquals(200, $response->getStatusCode());

        $this->assertSelectorStartsWithOrEquals('span.count', 0, '(5)');
		$this->assertSelectorStartsWithOrEquals('span.count', 1, '(11)');
		$this->assertSelectorStartsWithOrEquals('span.count', 2, '(12)');
		$this->assertSelectorStartsWithOrEquals('span.count', 3, '(13)');
		$this->assertSelectorStartsWithOrEquals('span.count', 4, '(16)');
		$this->assertSelectorStartsWithOrEquals('span.count', 5, '(13)');
		$this->assertSelectorStartsWithOrEquals('span.count', 6, '(11)');
		$this->assertSelectorStartsWithOrEquals('span.count', 7, '(19)');
		$this->assertSelectorStartsWithOrEquals('span.count', 8, '(12)');
		$this->assertSelectorStartsWithOrEquals('span.count', 9, '(11)');
		$this->assertSelectorStartsWithOrEquals('span.count', 10, '(11)');
		$this->assertSelectorStartsWithOrEquals('span.count', 11, '(20)');
		$this->assertSelectorStartsWithOrEquals('span.count', 12, '(12)');
		$this->assertSelectorStartsWithOrEquals('span.count', 13, '(17)');
		$this->assertSelectorStartsWithOrEquals('span.count', 14, '(17)');
		$this->assertSelectorStartsWithOrEquals('span.count', 15, '(17)');
		$this->assertSelectorStartsWithOrEquals('span.count', 16, '(15)');
		$this->assertSelectorStartsWithOrEquals('span.count', 17, '(17)');
		$this->assertSelectorStartsWithOrEquals('span.count', 18, '(10)');
		$this->assertSelectorStartsWithOrEquals('span.count', 19, '(18)');
		$this->assertSelectorStartsWithOrEquals('span.count', 20, '(1)');
		$this->assertSelectorStartsWithOrEquals('span.count', 21, '(10)');
		$this->assertSelectorStartsWithOrEquals('span.count', 22, '(12)');
		$this->assertSelectorStartsWithOrEquals('span.count', 23, '(21)');
		$this->assertSelectorStartsWithOrEquals('span.count', 24, '(23)');
		$this->assertSelectorStartsWithOrEquals('span.count', 25, '(17)');
		$this->assertSelectorStartsWithOrEquals('span.count', 26, '(16)');
		$this->assertSelectorStartsWithOrEquals('span.count', 27, '(23)');
		$this->assertSelectorStartsWithOrEquals('span.count', 28, '(9)');
		$this->assertSelectorStartsWithOrEquals('span.count', 29, '(31)');
		$this->assertSelectorStartsWithOrEquals('span.count', 30, '(16)');
		$this->assertSelectorStartsWithOrEquals('span.count', 31, '(39)');
		$this->assertSelectorStartsWithOrEquals('span.count', 32, '(5)');
	}


	public function testAggregationOneSelected() {
		//SearchHelper

		$this->autoFollowRedirection = false;

		//Note pages need to be published, by default fixtures only reside in Stage
		$searchPageObj = $this->ElasticSearchPage;

		$url = $searchPageObj->Link();
		$searchPage = $this->get($searchPageObj->URLSegment);
		$this->assertEquals(200, $searchPage->getStatusCode());
		$url = rtrim($url,'/');
		$url .= '?ISO=400';

        $response = $this->get($url);
		print_r($response);
        $this->assertEquals(200, $response->getStatusCode());

        // These are less than in the no facets selected case, as expected
		$this->assertSelectorStartsWithOrEquals('span.count', 0, '(2)');
		$this->assertSelectorStartsWithOrEquals('span.count', 1, '(1)');
		$this->assertSelectorStartsWithOrEquals('span.count', 2, '(2)');
		$this->assertSelectorStartsWithOrEquals('span.count', 3, '(1)');
		$this->assertSelectorStartsWithOrEquals('span.count', 4, '(3)');
		$this->assertSelectorStartsWithOrEquals('span.count', 5, '(1)');
		$this->assertSelectorStartsWithOrEquals('span.count', 6, '(3)');
		$this->assertSelectorStartsWithOrEquals('span.count', 7, '(3)');
		$this->assertSelectorStartsWithOrEquals('span.count', 8, '(2)');
		$this->assertSelectorStartsWithOrEquals('span.count', 9, '(2)');
		$this->assertSelectorStartsWithOrEquals('span.count', 10, '(1)');
		$this->assertSelectorStartsWithOrEquals('span.count', 11, '(2)');
		$this->assertSelectorStartsWithOrEquals('span.count', 12, '(3)');
		$this->assertSelectorStartsWithOrEquals('span.count', 13, '(3)');
		$this->assertSelectorStartsWithOrEquals('span.count', 14, '(4)');
		$this->assertSelectorStartsWithOrEquals('span.count', 15, '(3)');
		$this->assertSelectorStartsWithOrEquals('span.count', 16, '(1)');
		$this->assertSelectorStartsWithOrEquals('span.count', 17, '(2)');
		$this->assertSelectorStartsWithOrEquals('span.count', 18, '(0)');
		$this->assertSelectorStartsWithOrEquals('span.count', 19, '(4)');
		$this->assertSelectorStartsWithOrEquals('span.count', 20, '(1)');
		$this->assertSelectorStartsWithOrEquals('span.count', 21, '(7)');
		$this->assertSelectorStartsWithOrEquals('span.count', 22, '(1)');


	}



	public function testAggregationTwoSelected() {
		//SearchHelper

		$this->autoFollowRedirection = false;

		//Note pages need to be published, by default fixtures only reside in Stage
		$searchPageObj = $this->ElasticSearchPage;

		$url = $searchPageObj->Link();
		$searchPage = $this->get($searchPageObj->URLSegment);
		$this->assertEquals(200, $searchPage->getStatusCode());
		$url = rtrim($url,'/');
		$url .= '?ISO=400&ShutterSpeed=2%2F250';

        $response = $this->get($url);
		print_r($response);
        $this->assertEquals(200, $response->getStatusCode());

        // These are less than in the one facet selected case, as expected
        $this->assertSelectorStartsWithOrEquals('span.count', 0, '(1)');
		$this->assertSelectorStartsWithOrEquals('span.count', 1, '(2)');
		$this->assertSelectorStartsWithOrEquals('span.count', 2, '(1)');
		$this->assertSelectorStartsWithOrEquals('span.count', 3, '(1)');
		$this->assertSelectorStartsWithOrEquals('span.count', 4, '(1)');
		$this->assertSelectorStartsWithOrEquals('span.count', 5, '(0)');
		$this->assertSelectorStartsWithOrEquals('span.count', 6, '(1)');
		$this->assertSelectorStartsWithOrEquals('span.count', 7, '(0)');
		$this->assertSelectorStartsWithOrEquals('span.count', 8, '(2)');
		$this->assertSelectorStartsWithOrEquals('span.count', 9, '(0)');


	}


	public function testAggregationThreeSelected() {
		//SearchHelper

		$this->autoFollowRedirection = false;

		//Note pages need to be published, by default fixtures only reside in Stage
		$searchPageObj = $this->ElasticSearchPage;

		$url = $searchPageObj->Link();
		$searchPage = $this->get($searchPageObj->URLSegment);
		$this->assertEquals(200, $searchPage->getStatusCode());
		$url = rtrim($url,'/');
		$url .= '?ISO=400&ShutterSpeed=2%2F250&Aspect=Vertical';

        $response = $this->get($url);
		print_r($response);
        $this->assertEquals(200, $response->getStatusCode());

        // These are less than in the one facet selected case, as expected
        $this->assertSelectorStartsWithOrEquals('span.count', 0, '(2)');
		$this->assertSelectorStartsWithOrEquals('span.count', 1, '(1)');
		$this->assertSelectorStartsWithOrEquals('span.count', 2, '(1)');
	}


	public function testQueryIsEmpty() {
		//Note pages need to be published, by default fixtures only reside in Stage
		$searchPageObj = $this->ElasticSearchPage2;
		$url = rtrim($searchPageObj->Link(), '/');
		echo "URL:$url\n";
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


	/*
	Test the button override function
	 */
	public function testModelSearchFormButtonOverride() {
		$searchPageObj = $this->ElasticSearchPage2;
		$form = $searchPageObj->SearchForm('My Button Override Text');
		$actions = $form->Actions();
		$button = $actions->fieldByName('action_submit');
		$this->assertEquals('', $button->Value());

		// no override, use default
		$form = $searchPageObj->SearchForm();
		$actions = $form->Actions();
		$button = $actions->fieldByName('action_submit');
		$this->assertEquals('', $button->Value());
	}


	public function testSimilarNotSearchable() {
		$searchPageObj = $this->ElasticSearchPage2;
		$url = rtrim($searchPageObj->Link(), '/');
		$url .= "/similar/Member/1";
		$response = $this->get($url);
		$this->assertEquals(200, $response->getStatusCode());

		$this->assertSelectorStartsWithOrEquals('div.error', 0,
			'Class Member is either not found or not searchable');

	}


	public function testSimilarNull() {
		$searchPageObj = $this->ElasticSearchPage2;
		$url = rtrim($searchPageObj->Link(), '/');
		$url .= "/similar/Member/0";
		$response = $this->get($url);
		$this->assertEquals(200, $response->getStatusCode());

		$this->assertSelectorStartsWithOrEquals('div.error', 0,
			'Class Member is either not found or not searchable');
	}


	public function testSimilarClassDoesNotExist() {
		$searchPageObj = $this->ElasticSearchPage2;
		$url = rtrim($searchPageObj->Link(), '/');
		$url .= "/similar/asdfsadfsfd/4";
		$response = $this->get($url);
		$this->assertEquals(200, $response->getStatusCode());

		$this->assertSelectorStartsWithOrEquals('div.error', 0,
			'Class asdfsadfsfd is either not found or not searchable');
	}


	public function testSimilarSearchServerDown() {
		$client = new Elastica\Client(array('port' => 12345));
		$indexName = $this->service->getIndexName();
		$this->service = new SilverStripe\Elastica\ElasticaService($client, $indexName);
		$searchPageObj = $this->ElasticSearchPage2;
		$url = rtrim($searchPageObj->Link(), '/');
		$url .= "/similar/FlickrPhotoTO/77";
		$response = $this->get($url);
		$this->fail('Server port is not configurable');
	}


	public function testSimilarValid() {
		$searchPageObj = $this->ElasticSearchPage2;
		$url = rtrim($searchPageObj->Link(), '/');
		$url .= "/similar/FlickrPhotoTO/77";
		$response = $this->get($url);
		print_r($response);
		//Title of the original is "[Texas and New Orleans, Southern Pacific Railroad Station, Sierra Blanca, Texas]"
		$this->assertSelectorStartsWithOrEquals('div.searchResult a', 0, '[ and New Orleans, Southern Pacific Railroad Station, Sinton, ]');
		$this->assertSelectorStartsWithOrEquals('div.searchResult a', 1, 'Similar');
		$this->assertSelectorStartsWithOrEquals('div.searchResult a', 2, '[ and New Orleans, Southern Pacific Railroad Station, Taft, ]');
		$this->assertSelectorStartsWithOrEquals('div.searchResult a', 3, 'Similar');
		$this->assertSelectorStartsWithOrEquals('div.searchResult a', 4, '[ and New Orleans, Southern Pacific Passenger Station, Waxahachie, ]');
		$this->assertSelectorStartsWithOrEquals('div.searchResult a', 5, 'Similar');
		$this->assertSelectorStartsWithOrEquals('div.searchResult a', 6, '[ and New Orleans, Southern Pacific, Tower No. 63, Mexia, ]');
		$this->assertSelectorStartsWithOrEquals('div.searchResult a', 7, 'Similar');
		$this->assertSelectorStartsWithOrEquals('div.searchResult a', 8, '[ and New Orleans, Southern Pacific Locomotive Scrap Line, Englewood Yards, Houston, ]');
		$this->assertSelectorStartsWithOrEquals('div.searchResult a', 9, 'Similar');
		$this->assertSelectorStartsWithOrEquals('div.searchResult a', 10, '[ and New Orleans, Southern Pacific Railroad Station, Stockdale, ]');
		$this->assertSelectorStartsWithOrEquals('div.searchResult a', 11, 'Similar');
		$this->assertSelectorStartsWithOrEquals('div.searchResult a', 12, '[ and New Orleans, Southern Pacific Freight Station, Waxahachie, ]');
		$this->assertSelectorStartsWithOrEquals('div.searchResult a', 13, 'Similar');
		$this->assertSelectorStartsWithOrEquals('div.searchResult a', 14, '[ and New Orleans, Southern Pacific, Eakin Street Yard Office, Dallas, ]');
		$this->assertSelectorStartsWithOrEquals('div.searchResult a', 15, 'Similar');
		$this->assertSelectorStartsWithOrEquals('div.searchResult a', 16, '[ and New Orleans, Southern Pacific, Switchman\'s Tower, San Antonio, ]');
		$this->assertSelectorStartsWithOrEquals('div.searchResult a', 17, 'Similar');
		$this->assertSelectorStartsWithOrEquals('div.searchResult a', 18, 'Villa Deserters Conducted to 11th Inf. Headquarters.');
		$this->assertSelectorStartsWithOrEquals('div.searchResult a', 19, 'Similar');
	}


	/*
	Search for New Zealind and get search results for New Zealand.  Show option to click on
	actual search of New Zealind
	 */
	public function testSuggestion() {
		$searchPageObj = $this->ElasticSearchPage2;
		$url = rtrim($searchPageObj->Link(), '/');
		$url .= "?q=New%20Zealind&TestMode=true";
		$response = $this->get($url);
		print_r($response);
		$this->assertEquals(200, $response->getStatusCode());
		$this->assertSelectorStartsWithOrEquals('p.showingResultsForMsg', 0, 'Showing results for ');
		$this->assertSelectorStartsWithOrEquals('p.showingResultsForMsg a', 0, 'New ');
		$this->assertSelectorStartsWithOrEquals('p.showingResultsForMsg strong.hl', 0, 'Zealand');

		$this->assertSelectorStartsWithOrEquals('p.searchOriginalQueryMsg', 0, 'Search instead for ');
		$this->assertSelectorStartsWithOrEquals('p.searchOriginalQueryMsg a', 0, 'New Zealind');


		$this->assertSelectorStartsWithOrEquals('div.searchResult a', 0, 'New ');
		$this->assertSelectorStartsWithOrEquals('div.searchResult a', 1, 'New Zealind');
		$this->assertSelectorStartsWithOrEquals('div.searchResult a', 2, 'Douglas DC-10-30 cn 47847 ZK-NZM Air New Zealand Feb74 [RJF]');
		$this->assertSelectorStartsWithOrEquals('div.searchResult a', 3, 'Similar');
		$this->assertSelectorStartsWithOrEquals('div.searchResult a', 4, 'Image taken from page 555 of \'New Zealand, its physical geography, geology and natural history, with special reference to the results of Government Expeditions in the provinces of Auckland and Nelson ... Translated from the German original ... by E. Saute');
		$this->assertSelectorStartsWithOrEquals('div.searchResult a', 5, 'Similar');
		$this->assertSelectorStartsWithOrEquals('div.searchResult a', 6, 'Image taken from page 59 of \'Illustrated Handbook to Plymouth, Stonehouse & Devonport, with a new map ... New and enlarged edition\'');
		$this->assertSelectorStartsWithOrEquals('div.searchResult a', 7, 'Similar');
		$this->assertSelectorStartsWithOrEquals('div.searchResult a', 8, '[Texas and New Orleans, Southern Pacific Passenger Station, Waxahachie, Texas]');
		$this->assertSelectorStartsWithOrEquals('div.searchResult a', 9, 'Similar');
		$this->assertSelectorStartsWithOrEquals('div.searchResult a', 10, '[Texas and New Orleans, Southern Pacific, Tower No. 63, Mexia, Texas]');
		$this->assertSelectorStartsWithOrEquals('div.searchResult a', 11, 'Similar');
		$this->assertSelectorStartsWithOrEquals('div.searchResult a', 12, '[Texas and New Orleans, Southern Pacific Railroad Station, Sierra Blanca, Texas]');
		$this->assertSelectorStartsWithOrEquals('div.searchResult a', 13, 'Similar');
		$this->assertSelectorStartsWithOrEquals('div.searchResult a', 14, 'Image taken from page 273 of \'Old and New London, etc\'');
		$this->assertSelectorStartsWithOrEquals('div.searchResult a', 15, 'Similar');
		$this->assertSelectorStartsWithOrEquals('div.searchResult a', 16, 'Flash Light view in new Subterranean');
		$this->assertSelectorStartsWithOrEquals('div.searchResult a', 17, 'Similar');
		$this->assertSelectorStartsWithOrEquals('div.searchResult a', 18, 'Gen. Pancho Villa Raid on Columbus, New Mexico, March 9th, at 4 A.M., 1916.');
		$this->assertSelectorStartsWithOrEquals('div.searchResult a', 19, 'Similar');
		$this->assertSelectorStartsWithOrEquals('div.searchResult a', 20, '[Texas and New Orleans, Southern Pacific Locomotive Scrap Line, Englewood Yards, Houston, Texas]');
		$this->assertSelectorStartsWithOrEquals('div.searchResult a', 21, 'Similar');



		// simulate following the link to search for 'New Zealind'
		$url = rtrim($searchPageObj->Link(), '/');
		$url .= '?q=New Zealind&is=1';
		$response = $this->get($url);
		$this->assertEquals(200, $response->getStatusCode());

		print_r($response);

		//Only the word New will match, Zealind does not exist.  Hence 'New York', 'New Orelans' etc
		$this->assertSelectorStartsWithOrEquals('div.searchResult a', 0, 'Image taken from page 59 of \'Illustrated Handbook to Plymouth, Stonehouse & Devonport, with a new map ... New and enlarged edition\'');
		$this->assertSelectorStartsWithOrEquals('div.searchResult a', 1, 'Similar');
		$this->assertSelectorStartsWithOrEquals('div.searchResult a', 2, '[Texas and New Orleans, Southern Pacific Passenger Station, Waxahachie, Texas]');
		$this->assertSelectorStartsWithOrEquals('div.searchResult a', 3, 'Similar');
		$this->assertSelectorStartsWithOrEquals('div.searchResult a', 4, 'Flash Light view in new Subterranean');
		$this->assertSelectorStartsWithOrEquals('div.searchResult a', 5, 'Similar');
		$this->assertSelectorStartsWithOrEquals('div.searchResult a', 6, '[Texas and New Orleans, Southern Pacific Railroad Station, Sinton, Texas]');
		$this->assertSelectorStartsWithOrEquals('div.searchResult a', 7, 'Similar');
		$this->assertSelectorStartsWithOrEquals('div.searchResult a', 8, '[Texas and New Orleans, Southern Pacific Railroad Station, Taft, Texas]');
		$this->assertSelectorStartsWithOrEquals('div.searchResult a', 9, 'Similar');
		$this->assertSelectorStartsWithOrEquals('div.searchResult a', 10, '[Texas and New Orleans, Southern Pacific, Tower No. 63, Mexia, Texas]');
		$this->assertSelectorStartsWithOrEquals('div.searchResult a', 11, 'Similar');
		$this->assertSelectorStartsWithOrEquals('div.searchResult a', 12, '[Texas and New Orleans, Southern Pacific Railroad Station, Sierra Blanca, Texas]');
		$this->assertSelectorStartsWithOrEquals('div.searchResult a', 13, 'Similar');
		$this->assertSelectorStartsWithOrEquals('div.searchResult a', 14, 'Image taken from page 273 of \'Old and New London, etc\'');
		$this->assertSelectorStartsWithOrEquals('div.searchResult a', 15, 'Similar');
		$this->assertSelectorStartsWithOrEquals('div.searchResult a', 16, '[Texas and New Orleans, Southern Pacific, Switchman\'s Tower, San Antonio, Texas]');
		$this->assertSelectorStartsWithOrEquals('div.searchResult a', 17, 'Similar');
		$this->assertSelectorStartsWithOrEquals('div.searchResult a', 18, 'Image taken from page 143 of \'Cassell\'s Old and New Edinburgh, etc\'');
		$this->assertSelectorStartsWithOrEquals('div.searchResult a', 19, 'Similar');



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


	public function testSearchOnePageNoAggregation() {
		$this->enableHighlights();
		$this->autoFollowRedirection = false;
		$searchTerm = 'mineralogy';

		//Note pages need to be published, by default fixtures only reside in Stage
		$searchPageObj = $this->ElasticSearchPage2;
		$url = rtrim($searchPageObj->Link(), '/');
		$url = $url.'?q='.$searchTerm;
		echo "URL:$url\n";
		$response = $this->get($url);
		$this->assertEquals(200, $response->getStatusCode());

		print_r($response);

		//There are 3 results for mineralogy
		$this->assertSelectorStartsWithOrEquals('div.resultsFound', 0,
			"Page 1 of 1  (3 results found");


		//Check all the result highlights for mineralogy matches
		$this->assertSelectorStartsWithOrEquals('strong.hl', 1, 'mineralogy');
		$this->assertSelectorStartsWithOrEquals('strong.hl', 2, 'Mineralogy');
		$this->assertSelectorStartsWithOrEquals('strong.hl', 3, 'mineralogy');
		$this->assertSelectorStartsWithOrEquals('strong.hl', 4, 'Mineralogy');
		$this->assertSelectorStartsWithOrEquals('strong.hl', 5, 'mineralogy');


		// Check the start text of the 3 results
		$this->assertSelectorStartsWithOrEquals('div.searchResult h4 a', 0,
			"Image taken from page 273 of");
		$this->assertSelectorStartsWithOrEquals('div.searchResult h4 a', 1,
			'Image taken from page 69 of');
		$this->assertSelectorStartsWithOrEquals('div.searchResult h4 a', 2,
			'Image taken from page 142 of');

		// No span.count means no aggregations
		$this->assertExactHTMLMatchBySelector('span.count', array());
	}


	/* When a search is posted, should redirect to the same URL with the search term attached.  This
	means that searches can be bookmarked if so required */
	public function testRedirection() {
		$this->autoFollowRedirection = false;

		//Note pages need to be published, by default fixtures only reside in Stage
		$searchPageObj = $this->ElasticSearchPage;
		$url = $searchPageObj->Link();
		$searchPage = $this->get($searchPageObj->URLSegment);
		$this->assertEquals(200, $searchPage->getStatusCode());

        $response = $this->submitForm("ElasticSearchForm_SearchForm", null, array(
            'q' => 'New Zealand'
        ));

		$url = rtrim($url,'/');
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals($url.'?q=New Zealand&sfid=testwithagg', $response->getHeader('Location'));
	}


	/*
	Add test for redirection of /search/?q=XXX to /search?q=XXX
	 */


	/*
	Test a search for an uncommon term, no pagination here
	 */
	public function testSearchOnePage() {
		$this->enableHighlights();
		$this->autoFollowRedirection = false;
		$searchTerm = 'mineralogy';

		//Note pages need to be published, by default fixtures only reside in Stage
		$searchPageObj = $this->ElasticSearchPage;
		$url = rtrim($searchPageObj->Link(), '/');
		$url = $url.'?q='.$searchTerm;
		echo "URL:$url\n";
		$response = $this->get($url);
		$this->assertEquals(200, $response->getStatusCode());

		print_r($response);

		//There are 3 results for mineralogy
		$this->assertSelectorStartsWithOrEquals('div.resultsFound', 0,
			"Page 1 of 1  (3 results found");


		//Check all the result highlights for mineralogy matches
		$this->assertSelectorStartsWithOrEquals('strong.hl', 1, 'mineralogy');
		$this->assertSelectorStartsWithOrEquals('strong.hl', 2, 'Mineralogy');
		$this->assertSelectorStartsWithOrEquals('strong.hl', 3, 'mineralogy');
		$this->assertSelectorStartsWithOrEquals('strong.hl', 4, 'Mineralogy');
		$this->assertSelectorStartsWithOrEquals('strong.hl', 5, 'mineralogy');


		// Check the start text of the 3 results
		$this->assertSelectorStartsWithOrEquals('div.searchResult h4 a', 0,
			"Image taken from page 273 of");
		$this->assertSelectorStartsWithOrEquals('div.searchResult h4 a', 1,
			'Image taken from page 69 of');
		$this->assertSelectorStartsWithOrEquals('div.searchResult h4 a', 2,
			'Image taken from page 142 of');
	}



	/*
	Test a search for a common term, in order to induce pagination
	 */
	public function testSiteTreeSearch() {
		$this->enableHighlights();
		$this->autoFollowRedirection = false;

		//One of the default pages
		$searchTerm = 'Contact Us';

		//Note pages need to be published, by default fixtures only reside in Stage
		$searchPageObj = $this->ElasticSearchPage;
		$searchPageObj->SiteTreeOnly = true;
		$searchPageObj->write();
		$searchPageObj->publish('Stage','Live');


		$pageLength = 10; // the default
		$searchPageObj->ResultsPerPage = $pageLength;
		$url = rtrim($searchPageObj->Link(), '/');
		$url = $url.'?q='.$searchTerm;
		$firstPageURL = $url;
		$response = $this->get($url);
		$this->assertEquals(200, $response->getStatusCode());
		print_r($response);

		//There are 2 results for 'Contact Us', as 'About Us' has an Us in the title.
		$this->assertSelectorStartsWithOrEquals('div.resultsFound', 0,
			"Page 1 of 1  (2 results found in");

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
	public function testSearchSeveralPagesPage() {
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

		print_r($response);


		//There are 3 results for mineralogy
		$this->assertSelectorStartsWithOrEquals('div.resultsFound', 0,
			"Page 1 of 2  (11 results found in");

		//The classname 'searchResults' appears to be matching the contained 'searchResult', hence
		//the apparently erroneous addition of 1 to the required 10
		$this->assertNumberOfNodes('div.searchResult', 11);

		//Check for a couple of highlighed 'Railroad' terms
		$this->assertSelectorStartsWithOrEquals('strong.hl', 0, 'Railroad');
		$this->assertSelectorStartsWithOrEquals('strong.hl', 1, 'Railroad');

		$this->assertSelectorStartsWithOrEquals('div.pagination a', 0, '2');
		$this->assertSelectorStartsWithOrEquals('div.pagination a.next', 0, '→');

		$resultsP1 = $this->collateSearchResults();

		$page2url = $url . '&start='.$pageLength;

		//Check pagination on page 2
		$response2 = $this->get($page2url);
		$this->assertEquals(200, $response2->getStatusCode());

		//FIXME pluralisation probably needs fixed here, change test later acoordingly
		$this->assertSelectorStartsWithOrEquals('div.resultsFound', 0,
			"Page 2 of 2  (11 results found in");

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
		$searchPageObj->publish('Stage','Live');
		$response3 = $this->get($firstPageURL);
	}


	private function enableHighlights() {
		foreach (SearchableField::get()->filter('Name', 'Title') as $sf) {
			echo "Highlighting {$sf->ClazzName} {$sf->Name}\n";
			$sf->ShowHighlights = true;
			$sf->write();
		}

		foreach (SearchableField::get()->filter('Name', 'Content') as $sf) {
			echo "Highlighting {$sf->ClazzName} {$sf->Name}\n";

			$sf->ShowHighlights = true;
			$sf->write();
		}

		//FIXME - do this with ORM
		//$sql = "UPDATE ElasticSearchPage_ElasticaSearchableFields SET "
	}
}
