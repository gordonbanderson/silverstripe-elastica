<?php

use SilverStripe\Elastica\ElasticSearcher;

/**
 * Test the functionality of the Searchable extension
 * @package elastica
 */
class SearchAndIndexingTest extends ElasticsearchBaseTest {
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




	public function testInvalidSearchFields() {
		// FIXME test to check unweighted fields
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



	public function testGetResults() {
		// several checks needed  here including aggregations
	}


	public function testResultListGetMap() {
		$resultList = $this->getResultsFor('New Zealand',10);
		//default is ID -> Title, useful for dropdowns
		$mapping = $resultList->map();
		$ctr = 0;
		foreach ($resultList->getIterator() as $item) {
			$mappedTitle = $mapping[$item->ID];
			$this->assertEquals($item->Title, $mappedTitle);
			$ctr++;
		}
	}


	public function testResultListColumn() {
		$resultList = $this->getResultsFor('New Zealand',10);
		$ids = $resultList->column();

		$expected = array();
		foreach ($resultList as $item) {
			array_push($expected, $item->ID);
		}

		$this->assertEquals($expected,$ids);

		$expected = array();
		foreach ($resultList as $item) {
			array_push($expected, $item->Title);
		}
		$titles = $resultList->column('Title');
		$this->assertEquals($expected,$titles);
	}


	public function testEach() {
		$callback = function($fp) {
		    $this->assertTrue(true, 'Callback reached');
		};
		$resultList = $this->getResultsFor('New Zealand',10);
		$resultList->each($callback);
	}


	/*
	The search term 'New Zealand' was used against Flickr to create the fixtures file, so this means
	that all of the fixtures should have 'New Zealand' in them.  Test page length from 1 to 100
	 */
	public function testResultListPageLength() {
		for ($i=1; $i <= 100 ; $i++) {
			$resultList = $this->getResultsFor('New Zealand',$i);
			$this->assertEquals($i, $resultList->count());
		}
	}


	public function testResultListIndex() {
		$resultList = $this->getResultsFor('New Zealand',10);
		$index = $resultList->getService()->getIndex();
		$this->assertEquals('elastica_ss_module_test_en_us', $index->getName());
	}


	public function testResultListGetQuery() {
		$resultList = $this->getResultsFor('New Zealand',10);
		$query = $resultList->getQuery()->toArray();

		$expected = array();
		$expected['query'] = array('multi_match' => array(
				'query' => 'New Zealand',
				'fields' => array('Title', 'Title.*', 'Description', 'Description.*'),
				'type' => 'most_fields',
				'lenient' => true
			)
		);
		$expected['size'] = 10;
		$expected['from'] = 0;


		$this->assertEquals($expected['size'], $query['size']);
		$this->assertEquals($expected['from'], $query['from']);
		$this->assertEquals($expected['query'], $query['query']);
	}


	/*
	Check that the time for the search was more than zero
	 */
	public function testResultListGetTotalTime() {
		$resultList = $this->getResultsFor('New Zealand',10);
		$time = $resultList->getTotalTime();
		$this->assertGreaterThan(0, $time);
	}


	/*
	Test the result list iterator function
	 */
	public function testResultListGetIterator() {
		$resultList = $this->getResultsFor('New Zealand',100);
		$ctr = 0;
		foreach ($resultList->getIterator() as $result) {
			$ctr++;
		}
		$this->assertEquals(100,$ctr);
	}


	/*
	Check some basic properties of the array returned for a result
	 */
	public function testToArrayFunction() {
		$resultList = $this->getResultsFor('New Zealand',1);
		$expected = array();
		$result = $resultList->toArray();

		$this->assertEquals(1,sizeof($result));
		$fp = $result[0];
		$this->assertEquals('FlickrPhotoTO', $fp->ClassName);
		$this->assertEquals(2147483647, $fp->FlickrID);
		$this->assertTrue(preg_match('/New Zealand/',$fp->Title) == 1);
	}


	/*
	Check some basic properties of the array returned for a result
	 */
	public function testToNestedArrayFunction() {
		$resultList = $this->getResultsFor('New Zealand',4);
		$expected = array();
		$result = $resultList->toNestedArray();

		$this->assertEquals(4,sizeof($result));
		$fp = $result[0];
		$this->assertEquals('FlickrPhotoTO', $fp['ClassName']);
		$this->assertEquals(2147483647, $fp['FlickrID']);
		$this->assertTrue(preg_match('/New Zealand/',$fp['Title']) == 1);
	}


	public function testResultListOffsetExistsNotImplemented() {
		try {
			$resultList = $this->getResultsFor('New Zealand',10);
			$resultList->offsetExists(10);
			$this->assertFalse(true, "This line should not have been reached");
		} catch (Exception $e) {
			$this->assertEquals('Not implemented', $e->getMessage());
		}
	}


	public function testResultListOffsetGetNotImplemented() {
		try {
			$resultList = $this->getResultsFor('New Zealand',10);
			$resultList->offsetGet(10);
			$this->assertFalse(true, "This line should not have been reached");
		} catch (Exception $e) {
			$this->assertEquals('Not implemented', $e->getMessage());
		}
	}


	public function testResultListOffsetSetNotImplemented() {
		try {
			$resultList = $this->getResultsFor('New Zealand',10);
			$resultList->offsetSet(10,null);
			$this->assertFalse(true, "This line should not have been reached");
		} catch (Exception $e) {
			$this->assertEquals('Not implemented', $e->getMessage());
		}
	}


	public function testResultListOffsetUnsetNotImplemented() {
		try {
			$resultList = $this->getResultsFor('New Zealand',10);
			$resultList->offsetUnset(10);
			$this->assertFalse(true, "This line should not have been reached");
		} catch (Exception $e) {
			$this->assertEquals('Not implemented', $e->getMessage());
		}
	}


	public function testResultListAddNotImplemented() {
		try {
			$resultList = $this->getResultsFor('New Zealand',10);
			$fp = new FlickrPhotoTO();
			$resultList->add($fp);
			$this->assertFalse(true, "This line should not have been reached");
		} catch (Exception $e) {
			$this->assertEquals('Not implemented', $e->getMessage());
		}
	}


	public function testResultListRemoveNotImplemented() {
		try {
			$resultList = $this->getResultsFor('New Zealand',10);
			$fp = new FlickrPhotoTO();
			$resultList->remove($fp);
			$this->assertFalse(true, "This line should not have been reached");
		} catch (Exception $e) {
			$this->assertEquals('Not implemented', $e->getMessage());
		}
	}


	public function testResultListFindNotImplemented() {
		try {
			$resultList = $this->getResultsFor('New Zealand',10);
			$fp = new FlickrPhotoTO();
			$resultList->find(4,$fp);
			$this->assertFalse(true, "This line should not have been reached");
		} catch (Exception $e) {
			$this->assertEquals('Not implemented', $e->getMessage());
		}
	}


	public function testResultListFirstNotImplemented() {
		try {
			$resultList = $this->getResultsFor('New Zealand',10);
			$fp = new FlickrPhotoTO();
			$resultList->first(4,$fp);
			$this->assertFalse(true, "This line should not have been reached");
		} catch (Exception $e) {
			$this->assertEquals('Not implemented', $e->getMessage());
		}
	}


	public function testResultListLastNotImplemented() {
		try {
			$resultList = $this->getResultsFor('New Zealand',10);
			$fp = new FlickrPhotoTO();
			$resultList->last(4,$fp);
			$this->assertFalse(true, "This line should not have been reached");
		} catch (Exception $e) {
			$this->assertEquals('Not implemented', $e->getMessage());
		}
	}


	public function testFoldedIndexes() {
		$this->fail('Folded test to do');
	}


	public function testSynonymIndexes() {
		$this->fail('Synonym test to do');
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

		$this->assertEquals(28, $searchableFields->count());
	}


	/**
	 * Test searching
	 * http://stackoverflow.com/questions/28305250/elasticsearch-customize-score-for-synonyms-stemming
	 */
	private function search($query, $resultsExpected = 10, $fields = null) {
		$es = new ElasticSearcher();
		$es->setStart(0);
		$es->setPageLength(100);
		//$es->addFilter('IsInSiteTree', false);
		$es->setClasses('FlickrPhotoTO');
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


	private function getResultsFor($query, $pageLength = 10) {
		$es = new ElasticSearcher();
		$es->setStart(0);
		$es->setPageLength($pageLength);
		//$es->addFilter('IsInSiteTree', false);
		$es->setClasses('FlickrPhotoTO');
		$fields = array('Title' => 1, 'Description' => 1);
		$resultList = $es->search($query, $fields)->getList();
		$this->assertEquals('SilverStripe\Elastica\ResultList', get_class($resultList));
		return $resultList;
	}


}
