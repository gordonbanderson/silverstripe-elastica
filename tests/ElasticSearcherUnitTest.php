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

		$fields = array('Description' => 1,'Title' => 1);
		$results = $es->search('New Zealind', $fields, true);

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



	public function testSimilarNoWeighting() {
		$fp = $this->objFromFixture('FlickrPhotoTO', 'photo0076');
		$es = new ElasticSearcher();
		$es->setClasses('FlickrPhotoTO');
		$fields = array('Title.standard', 'Description.standard');
		try {
			$paginated = $es->moreLikeThis($fp, $fields, true);

		} catch (InvalidArgumentException $e) {
			$this->assertEquals('Fields must be of the form fieldname => weight', $e->getMessage());
		}
	}


	public function testSimilarWeightingNotNumeric() {
		$fp = $this->objFromFixture('FlickrPhotoTO', 'photo0076');
		$es = new ElasticSearcher();
		$es->setClasses('FlickrPhotoTO');
		$fields = array('Title.standard' => 4, 'Description.standard' => 'not numeric');
		try {
			$paginated = $es->moreLikeThis($fp, $fields, true);

		} catch (InvalidArgumentException $e) {
			$this->assertEquals('Fields must be of the form fieldname => weight', $e->getMessage());
		}
	}


	public function testSimilarToNonSearchable() {
		$m = Member::get()->first(); // this is not by default Searchable
		$es = new ElasticSearcher();
		$es->setClasses('FlickrPhotoTO');
		$fields = array('Title.standard' => 4, 'Description.standard' => 2);
		try {
			$paginated = $es->moreLikeThis($m, $fields, true);

		} catch (InvalidArgumentException $e) {
			$this->assertEquals('Objects of class Member are not searchable', $e->getMessage());
		}
	}

	/*
	test blank fields
	test fields with no weighting (ie not associative)

	 */

	public function testSimilarGood() {
		$fp = $this->objFromFixture('FlickrPhotoTO', 'photo0076');
		$es = new ElasticSearcher();
		$es->setClasses('FlickrPhotoTO');
		$fields = array('Title.standard' => 1, 'Description.standard' => 1);
		$paginated = $es->moreLikeThis($fp, $fields, true);
		foreach ($paginated->getList() as $result) {
			echo $result->ID. ' : '.$result->Title."\n";
		}
		$this->assertEquals(32, $paginated->getTotalItems());
		$results = $paginated->getList()->toArray();
		$this->assertEquals("[Texas and New Orleans, Southern Pacific Railroad Station, Stockdale, Texas]", $results[0]->Title);
		$this->assertEquals("[Texas and New Orleans, Southern Pacific Railroad Station, Taft, Texas]", $results[1]->Title);
		$this->assertEquals("[Texas and New Orleans, Southern Pacific Railroad Station, Sierra Blanca, Texas]", $results[2]->Title);
		$this->assertEquals("[Texas and New Orleans, Southern Pacific Freight Station, Waxahachie, Texas]", $results[3]->Title);
		$this->assertEquals("[Texas and New Orleans, Southern Pacific Passenger Station, Waxahachie, Texas]", $results[4]->Title);
		$this->assertEquals("[Texas and New Orleans, Southern Pacific, Tower No. 63, Mexia, Texas]", $results[5]->Title);
		$this->assertEquals("[Texas and New Orleans, Southern Pacific, Eakin Street Yard Office, Dallas, Texas]", $results[6]->Title);
		$this->assertEquals("[Texas and New Orleans, Southern Pacific Locomotive Scrap Line, Englewood Yards, Houston, Texas]", $results[7]->Title);
		$this->assertEquals("[Texas and New Orleans, Southern Pacific, Switchman's Tower, San Antonio, Texas]", $results[8]->Title);
		$this->assertEquals("Flash Light view in new Subterranean", $results[9]->Title);
	}


	// if this is not set to unbounded, zero, a conditional is triggered to add max doc freq to the request
	public function testSimilarChangeMaxDocFreq() {
		$fp = $this->objFromFixture('FlickrPhotoTO', 'photo0076');
		$es = new ElasticSearcher();
		$es->setMaxDocFreq(4);
		$es->setClasses('FlickrPhotoTO');
		$fields = array('Title.standard' => 1, 'Description.standard' => 1);
		$paginated = $es->moreLikeThis($fp, $fields, true);
		foreach ($paginated->getList() as $result) {
			echo $result->ID. ' : '.$result->Title."\n";
		}
		$this->assertEquals(14, $paginated->getTotalItems());
		$results = $paginated->getList()->toArray();
		$this->makeCode($paginated);
	}


	public function testSimilarNullFields() {
		$fp = $this->objFromFixture('FlickrPhotoTO', 'photo0076');
		$es = new ElasticSearcher();
		$es->setClasses('FlickrPhotoTO');
		try {
			$paginated = $es->moreLikeThis($fp, null, true);
		} catch (InvalidArgumentException $e) {
			$this->assertEquals('Fields cannot be null', $e->getMessage());
		}
	}


	public function testSimilarNullItem() {
		$es = new ElasticSearcher();
		$es->setClasses('FlickrPhotoTO');
		$fields = array('Title.standard' => 1, 'Description.standard' => 1);

		try {
			$paginated = $es->moreLikeThis(null, $fields, true);
		} catch (InvalidArgumentException $e) {
			$this->assertEquals('A searchable item cannot be null', $e->getMessage());
		}
	}



	public function testHighlightsAsIfCMSEdited() {
		$es = new ElasticSearcher();
		$locale = \i18n::default_locale();
		$es->setLocale($locale);
		$es->setClasses('FlickrPhotoTO');

		$filter = array('ClazzName' => 'FlickrPhotoTO', 'Name' => 'Title');
		$titleField = SearchableField::get()->filter($filter)->first();
		$titleField->ShowHighlights = true;
		$titleField->write();

		$filter = array('ClazzName' => 'FlickrPhotoTO', 'Name' => 'Description');
		$nameField = SearchableField::get()->filter($filter)->first();
		$nameField->ShowHighlights = true;
		$nameField->write();

		$fields = array('Title' => 1, 'Description' => 1);
		$query = 'New Zealand';
		$paginated = $es->search($query, $fields);
		$ctr = 0;

		foreach ($paginated->getList()->toArray() as $result) {
			$ctr++;
			foreach ($result->SearchHighlightsByField->Description_standard->getIterator() as $highlight) {
				$snippet = $highlight->Snippet;
				$snippet = strtolower($snippet);
				$wordFound = false;
				$lcquery = explode(' ', strtolower($query));
				foreach ($lcquery as $part) {
					$bracketed = '<strong class="hl">'.$part.'</strong>';
					if (strpos($snippet, $bracketed) > 0) {
						$wordFound = true;
					}
				}
				$this->assertTrue($wordFound,'Highlight should have been found');
			}
		}
	}


	public function testHighlightPassingFields() {
		$es = new ElasticSearcher();
		$es->setClasses('FlickrPhotoTO');
		$es->setHighlightedFields(array('Title', 'Title.standard', 'Description'));

		$fields = array('Title' => 1, 'Description' => 1);
		$query = 'New Zealand';
		$paginated = $es->search($query, $fields);
		$ctr = 0;

		foreach ($paginated->getList()->toArray() as $result) {
			$ctr++;

			foreach ($result->SearchHighlightsByField->Description->getIterator() as $highlight) {
				$snippet = $highlight->Snippet;
				$snippet = strtolower($snippet);
				$wordFound = false;
				$lcquery = explode(' ', strtolower($query));
				foreach ($lcquery as $part) {
					$bracketed = '<strong class="hl">'.$part.'</strong>';
					if (strpos($snippet, $bracketed) > 0) {
						$wordFound = true;
					}
				}
				$this->assertTrue($wordFound,'Highlight should have been found');
			}
		}
	}


	public function testAutoCompleteGood() {
		$es = new ElasticSearcher();
		$es->setClasses('FlickrPhotoTO');
		$fields = array('Title' => 1, 'Description' => 1);
		$query = 'Lond';
		$results = $es->autocomplete_search($query, 'Title');
		$this->assertEquals(7, $results->getTotalItems());
		foreach ($results->toArray() as $result) {
			$this->assertTrue(strpos($result->Title, $query) > 0);
		}
	}


	private function makeCode($paginated) {
		$results = $paginated->getList()->toArray();
		$ctr = 0;
		echo '$result = $paginated->getList()->toArray();'."\n";
		foreach ($results as $result) {
			echo '$this->assertEquals("'.$result->Title.'", $results['.$ctr.']->Title);'."\n";
			$ctr++;
		}
	}

}
