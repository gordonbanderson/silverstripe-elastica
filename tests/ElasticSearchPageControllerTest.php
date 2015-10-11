<?php


/**
 * @package comments
 */
class ElasticSearchPageControllerTest extends ElasticsearchFunctionalTestBase {

	public static $fixture_file = 'elastica/tests/lotsOfPhotos.yml';


	public function setup() {
		parent::setup();
		$esp = new ElasticSearchPage();
		$esp->Title = 'Search';
		$esp->Content = 'Example search page';
		$esp->IndexingOff = true;
		$esp->URLSegment = 'search';
		$esp->SiteTreeOnly = false;
		$esp->ClassesToSearch = 'FlickrPhoto';
		$esp->write();

		//Simulate selecting Title and Description as searchable fields in the CMS interface
		$espf1 = new ElasticSearchPageSearchField();
		$espf1->ElasticPageID = $esp->ID;
		$espf1->Searchable = true;
		$espf1->Name = 'Title';
		$espf1->Type = 'string';
		$espf1->write();

		$espf2 = new ElasticSearchPageSearchField();
		$espf2->ElasticPageID = $esp->ID;
		$espf2->Searchable = true;
		$espf2->Name = 'Description';
		$espf2->Type = 'string';
		$espf2->write();

		$esp->publish('Stage','Live');
		$this->ElasticSearchPage = $esp;
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
        $this->assertEquals($url.'?q=New Zealand', $response->getHeader('Location'));
	}


	/*
	Add test for redirection of /search/?q=XXX to /search?q=XXX
	 */


	/*
	Test a search for an uncommon term, no pagination here
	 */
	public function testSearchOnePage() {
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
			"Page 1 of 1 (3 results found");


		//Check all the result highlights for mineralogy matches
		$this->assertSelectorStartsWithOrEquals('strong.highlight', 1, 'mineralogy');
		$this->assertSelectorStartsWithOrEquals('strong.highlight', 2, 'Mineralogy');
		$this->assertSelectorStartsWithOrEquals('strong.highlight', 3, 'mineralogy');
		$this->assertSelectorStartsWithOrEquals('strong.highlight', 4, 'Mineralogy');
		$this->assertSelectorStartsWithOrEquals('strong.highlight', 5, 'mineralogy');
		$this->assertSelectorStartsWithOrEquals('strong.highlight', 6, 'Mineralogy');
		$this->assertSelectorStartsWithOrEquals('strong.highlight', 7, 'mineralogy');
		$this->assertSelectorStartsWithOrEquals('strong.highlight', 8, 'Mineralogy');
		$this->assertSelectorStartsWithOrEquals('strong.highlight', 9, 'mineralogy');
		$this->assertSelectorStartsWithOrEquals('strong.highlight', 10, 'Mineralogy');
		$this->assertSelectorStartsWithOrEquals('strong.highlight', 11, 'mineralogy');

		// Check the start text of the 3 results
		$this->assertSelectorStartsWithOrEquals('div.searchResult h4 a', 0,
			"Image taken from page 273 of");
		$this->assertSelectorStartsWithOrEquals('div.searchResult h4 a', 1,
			'Image taken from page 69 of');
		$this->assertSelectorStartsWithOrEquals('div.searchResult h4 a', 2,
			'Image taken from page 142 of');
	}
}
