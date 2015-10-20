<?php
use SilverStripe\Elastica\ElasticSearcher;
use Elastica\Type;
class ElasticSearcherUnitTest extends ElasticsearchBaseTest {
	public static $fixture_file = 'elastica/tests/lotsOfPhotos.yml';

	public static $ignoreFixtureFileFor = array('testResultsForEmptySearch');

	public function setUp() {
		parent::setUp();
	}

	public function tearDown() {
		parent::tearDown();
	}

	public function testResultsForEmptySearch() {
		$es = new ElasticSearcher();

		$es->hideResultsForEmptySearch();
		$this->assertFalse($es->getShowResultsForEmptySearch());

		$es->showResultsForEmptySearch();
		$this->assertTrue($es->getShowResultsForEmptySearch());
	}


	public function testMoreLikeThis() {
		$fp = $this->objFromFixture('FlickrPhotoTO', 'photo0076');
		$es = new ElasticSearcher();
		$locale = \i18n::default_locale();
		$es->setLocale($locale);
		$es->setClasses('FlickrPhoto');
		$results = $es->moreLikeThis($fp);

		echo "RESULTS:\n";
		foreach ($results as $result) {
			echo "-\t{$result->Title}\n";
		}
	}

}
