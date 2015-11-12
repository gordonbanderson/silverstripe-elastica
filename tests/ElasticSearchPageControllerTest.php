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
		$esp->Identifier = 'test';
		$esp->IndexingOff = true;
		$esp->URLSegment = 'search';
		$esp->SiteTreeOnly = false;
		$esp->ClassesToSearch = 'FlickrPhotoTO';
		$esp->write();

		//Simulate selecting Title and Description as searchable fields in the CMS interface
		$sfs = new ArrayList();

		$espf1 = new SearchableField();
		$espf1->ElasticPageID = $esp->ID;
		$espf1->Searchable = true;
		$espf1->Name = 'Title';
		$espf1->Type = 'string';
		$esp->ElasticaSearchableFields()->add($espf1);

		$espf2 = new SearchableField();
		$espf2->ElasticPageID = $esp->ID;
		$espf2->Searchable = true;
		$espf2->Name = 'Description';
		$espf2->Type = 'string';
		$esp->ElasticaSearchableFields()->add($espf2);



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
