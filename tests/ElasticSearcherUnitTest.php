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


	public function testSuggested() {
		$es = new ElasticSearcher();
		$locale = \i18n::default_locale();
		$es->setLocale($locale);
		$es->setClasses('FlickrPhotoTO');

		//FIXME when awake

		// This doesn't work, possibly a bug $fields = array('Description.standard' => 1,'Title.standard' => 1);
		$fields = array('Description' => 1,'Title' => 1);
		$results = $es->search('New Zealind', $fields);

		$this->assertEquals('New Zealand', $es->getSuggestedQuery());
	}

	public function testResultsForEmptySearch() {
		$es = new ElasticSearcher();

		$es->hideResultsForEmptySearch();
		$this->assertFalse($es->getShowResultsForEmptySearch());

		$es->showResultsForEmptySearch();
		$this->assertTrue($es->getShowResultsForEmptySearch());
	}


	public function testMoreLikeThisSinglePhoto() {
		$fp = $this->objFromFixture('FlickrPhotoTO', 'photo0076');

		echo "FP: Original title: {$fp->Title}\n";
		$es = new ElasticSearcher();
		$locale = \i18n::default_locale();
		$es->setLocale($locale);
		$es->setClasses('FlickrPhotoTO');

		$fields = array('Description.standard' => 1,'Title.standard' => 1);
		$results = $es->moreLikeThis($fp, $fields, true);

		echo "RESULTS:\n";
		foreach ($results as $result) {
			echo "-\t{$result->Title}\n";
		}

		$terms = $results->getList()->MoreLikeThisTerms;

		$fieldNamesReturned = array_keys($terms);
		$fieldNames = array_keys($fields);
		sort($fieldNames);
		sort($fieldNamesReturned);

		$this->assertEquals($fieldNames, $fieldNamesReturned);

		//FIXME - this seems anomolyous, check in more detail
		$expected = array('texas');
		$this->assertEquals($expected, $terms['Title.standard']);

		$expected = array('new', 'see','photographs', 'information','resolution', 'company', 'view',
			'high', 'collection', 'pacific', 'orleans', 'degolyer', 'southern', 'everett',
			'railroad', 'texas');

		$expected = array('collection', 'company', 'degolyer', 'everett', 'file', 'high',
			'information', 'new', 'orleans', 'pacific', 'photographs', 'railroad', 'resolution',
			'see', 'southern', 'texas', 'view');



		$actual = $terms['Description.standard'];
		sort($expected);
		sort($actual);


		$this->assertEquals($expected, $actual);
	}

}
