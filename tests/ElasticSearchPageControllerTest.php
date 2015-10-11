<?php


/**
 * @package comments
 */
class ElasticSearchPageControllerTest extends ElasticsearchFunctionalTestBase {

	public static $fixture_file = 'elastica/tests/ElasticaTest.yml';

	/* When a search is posted, should redirect to the same URL with the search term attached.  This
	means that searches can be bookmarked if so required */
	public function testRedirection() {
		$this->autoFollowRedirection = false;

		//Note pages need to be published, by default fixtures only reside in Stage
		$searchPageObj = $this->objFromFixture('ElasticSearchPage', 'search');

		$url = $searchPageObj->Link();


		$searchPage = $this->get($searchPageObj->URLSegment);
		$this->assertEquals(200, $searchPage->getStatusCode());

		/*
		RESEARCH: Why is data['url'] an issue here?

        $searchResults = $this->submitForm("ElasticSearchForm_SearchForm", null, array(
            'q' => 'New Zealand',
            'url' => $url
        ));
        */

        $formURL = $url.'SearchForm';
        echo "Posting to $formURL\n";

        //Using POST instead, as per
        //https://github.com/silverstripe/silverstripe-comments/blob/master/tests/CommentingControllerTest.php
        //Check that form redirects to the original URL with the search term appended
        $response = $this->post(
			$formURL,
			array(
				'q' => 'New Zealand',
            	'url' => $url
			)
		);

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals($url.'New Zealand', $response->getHeader('Location'));
	}
}
