<?php

/**
 * Teste the functionality of the Searchable extension
 * @package elastica
 */
class SearchAndIndexingTest extends ElasticsearchBaseTest {
	//public static $fixture_file = 'elastica/tests/lotsOfPhotos.yml';
	public static $fixture_file = 'elastica/tests/lotsOfPhotos.yml';


	/*
	Notes:
	Searching string on number fields fails
	http://elasticsearch-users.115913.n3.nabble.com/Error-when-searching-multiple-fields-with-different-types-td3897459.html


	*/
	public function testNonTextFields() {
		$numericFields = array(
			'FlickrID' => 1,
			'TakenAt' => 1,
			'FirstViewed' => 1,
			'Aperture' => 1,
			'ShutterSpeed' => 1,
			'FocalLength35mm' => 1,
			'ISO' => 1
		);

		$this->search('New Zealand', 0, $numericFields);

		//There are 10 entries in the fixtures, 3 default indexed pages
		$this->search(400, 13, $numericFields);

	}



	public function testSetStopwordsConfigurationCSV() {
		$stopwords = "a,the,then,this";
		$englishIndex = new EnglishIndexSettings();
		$englishIndex->setStopwords($stopwords);
		$expected = array('a','the','then','this');
		$this->assertEquals($expected, $englishIndex->getStopwords());
	}


	public function testSetStopwordsConfigurationArray() {
		$stopwords = array('a','the','then','this');
		$englishIndex = new EnglishIndexSettings();
		$englishIndex->setStopwords($stopwords);
		$expected = array('a','the','then','this');
		$this->assertEquals($expected, $englishIndex->getStopwords());
	}


	public function testConfigurationInvalidStopwords() {
		$stopwords = 45; // deliberately invalid
		$englishIndex = new EnglishIndexSettings();
		try {
			$englishIndex->setStopwords($stopwords);
			// should not get this far, should fail
			$this->assertTrue(false, "Invalid stopwords were not correctly prevented");
		} catch (Exception $e) {
			$this->assertTrue(true, "Invalid stopwords correctly prevented");
		}
	}


	/*
	Search for stop words and assert that they are not found
	 */
	public function testConfiguredStopWords() {
		//Commenting::set_config_value('CommentableItem','require_moderation', true);
		$englishIndex = new EnglishIndexSettings();
		$stopwords = $englishIndex->getStopwords();
		//print_r($stopwords);
		//
		// FIXME lenient flag needs added
		$allFields = array(
			'Title' => 1,
			'FlickrID' => 1,
			'Description' => 1,
			'TakenAt' => 1,
			'FirstViewed' => 1,
			'Aperture' => 1,
			'ShutterSpeed' => 1,
			'FocalLength35mm' => 1,
			'ISO' => 1
		);

		foreach ($stopwords as $stopword) {
			//FIXME - what should happen in this case?
			//Currently it is matching and it should not
			//$this->search($stopword, 0);
			$this->search($stopword, 0, array('Title' => 1));
			$this->search($stopword, 0, array('Description' => 1));
			$this->search($stopword, 0, array('Title' => 2, 'Description' => 1));

			// this tests numeric fields also
			$this->search($stopword, 0, $allFields);
		}

	}


	public function testFoldedIndexes() {
		$this->assertTrue(false, 'To do');
	}


	public function testSynonymIndexes() {
		$this->assertTrue(false, 'To do');
	}


	public function testNonExistentField() {
		try {
			// this should fail as field Fwlubble does not exist
			$this->search('zealand', 0, array('Fwlubble' => 1));
			$this->assertTrue(false, "Field Fwlubble does not exist and an exception should have been thrown");
		} catch (Exception $e) {
			$this->assertTrue(true, "Field Fwlubble does not exist, exception thrown as expected");
		}

	}


	public function testPriming() {
		$searchableClasses = SearchableClass::get();
		/*foreach ($searchableClasses->getIterator() as $key) {
			echo $key->Name."\n";
		}*/

		//There are seven classes with Searchable extension added
		$this->assertEquals(7, $searchableClasses->count());


		$searchableFields = SearchableField::get();
		/*foreach ($searchableFields->getIterator() as $key) {
			echo $key->Name."\n";
		}*/

		$this->assertEquals(27, $searchableFields->count());
	}


	/**
	 * Test searching
	 * http://stackoverflow.com/questions/28305250/elasticsearch-customize-score-for-synonyms-stemming
	 */
	private function search($query, $resultsExpected = 10, $fields = null) {
		$es = new \ElasticSearcher();
		$es->setStart(0);
		$es->setPageLength(100);
		//$es->addFilter('IsInSiteTree', false);
		$es->setClasses('FlickrPhoto');
		$results = $es->search($query, $fields);
		$ctr = 0;
		/*echo "{$results->count()} items found searching for '$query'\n\n";
		foreach ($results as $result) {
			$ctr++;
			echo("($ctr) ".$result->Title."\n");
			if ($result->SearchHighlightsByField->Content) {
				foreach ($result->SearchHighlightsByField->Content as $highlight) {
					echo("- ".$highlight->Snippet);
				}
			}
		}
		*/

		$this->assertEquals($resultsExpected, $results->count());

		return $results->count();
	}

}
