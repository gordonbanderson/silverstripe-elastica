<?php

/**
 * @package comments
 */
class ElasticSearchPageControllerTest extends ElasticsearchFunctionalTestBase {

	public static $fixture_file = 'elastica/tests/ElasticaTest.yml';


	public function testDefaultSearch() {

		$searchPage = $this->objFromFixture('ElasticSearchPage', 'search');

		echo $searchPage->URLSegment."\n";
		echo $searchPage->Link()."\n";


		echo $searchPage;
		$response = $this->get($searchPage->URLSegment);
		echo $response->getStatusCode();


		$response = $this->get('home/');
		$x = $response->getStatusCode();
		echo $x;

		$sql = 'SELECT ID,ClassName,Title from SiteTree_Live';
		$records = DB::query($sql);
		echo $sql;
		print_r($records,1);

		$items = SiteTree::get();
		foreach ($items as $item) {
			echo "\nLINK:{$item->Link()} SEG={$item->URLSegment} PARENTID={$item->ParentID}\n";
		}

		$page = $this->objFromFixture('ElasticSearchPage', 'search');
		echo $page;

		//$page->publish('Stage', 'Live');

		$urls = array('fourth', 'fourth/', '/fourth', '/fourth/');
		foreach ($urls as $url) {
			echo "\n------------- Searching for $url ------------\n\n";
			$response = $this->get($url);
			echo "\nURL:$url  ".$response->getStatusCode();
		}





		$this->assertEquals(200, $response->getStatusCode());

        // Home page should load..

        $login = $this->submitForm("ElasticSearchForm_SearchForm", null, array(
            'q' => 'Zealand'
        ));
	}




}






