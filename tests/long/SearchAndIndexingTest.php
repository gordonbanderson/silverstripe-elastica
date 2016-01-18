<?php

use SilverStripe\Elastica\ElasticaSearcher;

/**
 * Test the functionality of the Searchable extension.
 */
class SearchAndIndexingTest extends ElasticsearchTestBase
{
    public static $fixture_file = 'elastica/tests/lotsOfPhotos.yml';

    /*
    Notes:
    Searching string on number fields fails
    http://elasticsearch-users.115913.n3.nabble.com/Error-when-searching-multiple-fields-with-different-types-td3897459.html
    */
    public function testNonTextFields()
    {
        $numericFields = array(
            'FlickrID' => 1,
            'TakenAt' => 1,
            'FirstViewed' => 1,
            'Aperture' => 1,
            'ShutterSpeed' => 1,
            'FocalLength35mm' => 1,
            'ISO' => 1,
        );

        $this->search('New Zealand', 0, $numericFields);

        //There are 10 entries in the fixtures, 3 default indexed pages
        $this->search(400, 13, $numericFields);
    }

    public function testInvalidSearchFields()
    {
        // FIXME test to check unweighted fields
    }

    public function testSetStopwordsConfigurationCSV()
    {
        $stopwords = 'a,the,then,this';
        $englishIndex = new EnglishIndexSettings();
        $englishIndex->setStopwords($stopwords);
        $expected = array('a', 'the', 'then', 'this');
        $this->assertEquals($expected, $englishIndex->getStopwords());
    }

    public function testSetStopwordsConfigurationArray()
    {
        $stopwords = array('a', 'the', 'then', 'this');
        $englishIndex = new EnglishIndexSettings();
        $englishIndex->setStopwords($stopwords);
        $expected = array('a', 'the', 'then', 'this');
        $this->assertEquals($expected, $englishIndex->getStopwords());
    }

    public function testConfigurationInvalidStopwords()
    {
        $stopwords = 45; // deliberately invalid
        $englishIndex = new EnglishIndexSettings();
        try {
            $englishIndex->setStopwords($stopwords);
            // should not get this far, should fail
            $this->assertTrue(false, 'Invalid stopwords were not correctly prevented');
        } catch (Exception $e) {
            $this->assertTrue(true, 'Invalid stopwords correctly prevented');
        }
    }

    /*
    Search for stop words and assert that they are not found
     */
    public function testConfiguredStopWords()
    {
        $englishIndex = new EnglishIndexSettings();
        $stopwords = $englishIndex->getStopwords();
        $expected = array('that', 'into', 'a', 'an', 'and', 'are', 'as', 'at', 'be', 'but', 'by', 'for', 'if',
            'in', 'into', 'is', 'it', 'of', 'on', 'or', 'such', 'that', 'the', 'their', 'then', 'there', 'these',
            'they', 'this', 'to', 'was', 'will', 'with', );
        $this->assertEquals($expected, $stopwords);
    }

    public function testGetResults()
    {
        // several checks needed  here including aggregations
    }

    public function testResultListGetMap()
    {
        $resultList = $this->getResultsFor('New Zealand', 10);
        //default is ID -> Title, useful for dropdowns
        $mapping = $resultList->map();
        $ctr = 0;
        foreach ($resultList->getIterator() as $item) {
            $mappedTitle = $mapping[$item->ID];
            $this->assertEquals($item->Title, $mappedTitle);
            ++$ctr;
        }
    }

    public function testResultListColumn()
    {
        $resultList = $this->getResultsFor('New Zealand', 10);
        $ids = $resultList->column();

        $expected = array();
        foreach ($resultList as $item) {
            array_push($expected, $item->ID);
        }

        $this->assertEquals($expected, $ids);

        $expected = array();
        foreach ($resultList as $item) {
            array_push($expected, $item->Title);
        }
        $titles = $resultList->column('Title');
        $this->assertEquals($expected, $titles);
    }

    public function testEach()
    {
        $callback = function ($fp) {
            $this->assertTrue(true, 'Callback reached');
        };
        $resultList = $this->getResultsFor('New Zealand', 10);
        $resultList->each($callback);
    }

    /*
    The search term 'New Zealand' was used against Flickr to create the fixtures file, so this means
    that all of the fixtures should have 'New Zealand' in them.  Test page length from 1 to 100
     */
    public function testResultListPageLength()
    {
        for ($i = 1; $i <= 100; ++$i) {
            $resultList = $this->getResultsFor('New Zealand', $i);
            $this->assertEquals($i, $resultList->count());
        }
    }

    public function testResultListIndex()
    {
        $resultList = $this->getResultsFor('New Zealand', 10);
        $index = $resultList->getService()->getIndex();
        $this->assertEquals('elastica_ss_module_test_en_us', $index->getName());
    }

    public function testResultListGetQuery()
    {
        $resultList = $this->getResultsFor('New Zealand', 10);
        $query = $resultList->getQuery()->toArray();

        $expected = array();
        $expected['query'] = array('multi_match' => array(
                'query' => 'New Zealand',
                'fields' => array('Title', 'Title.*', 'Description', 'Description.*'),
                'type' => 'most_fields',
                'lenient' => true,
            ),
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
    public function testResultListGetTotalTime()
    {
        $resultList = $this->getResultsFor('New Zealand', 10);
        $time = $resultList->getTotalTime();
        $this->assertGreaterThan(0, $time);
    }

    /*
    Test the result list iterator function
     */
    public function testResultListGetIterator()
    {
        $resultList = $this->getResultsFor('New Zealand', 100);
        $ctr = 0;
        foreach ($resultList->getIterator() as $result) {
            ++$ctr;
        }
        $this->assertEquals(100, $ctr);
    }

    /*
    Check some basic properties of the array returned for a result
     */
    public function testToArrayFunction()
    {
        $resultList = $this->getResultsFor('New Zealand', 1);
        $result = $resultList->toArray();

        $this->assertEquals(1, sizeof($result));
        $fp = $result[0];
        $this->assertEquals('FlickrPhotoTO', $fp->ClassName);
        $this->assertEquals(2147483647, $fp->FlickrID);
        $this->assertTrue(preg_match('/New Zealand/', $fp->Title) == 1);
    }

    /*
    Check some basic properties of the array returned for a result
     */
    public function testToNestedArrayFunction()
    {
        $resultList = $this->getResultsFor('New Zealand', 4);
        $result = $resultList->toNestedArray();

        $this->assertEquals(4, sizeof($result));
        $fp = $result[0];
        $this->assertEquals('FlickrPhotoTO', $fp['ClassName']);
        $this->assertEquals(2147483647, $fp['FlickrID']);
        $this->assertTrue(preg_match('/New Zealand/', $fp['Title']) == 1);
    }

    public function testResultListOffsetExistsNotImplemented()
    {
        try {
            $resultList = $this->getResultsFor('New Zealand', 10);
            $resultList->offsetExists(10);
            $this->assertFalse(true, 'This line should not have been reached');
        } catch (Exception $e) {
            $this->assertEquals('Not implemented', $e->getMessage());
        }
    }

    public function testResultListOffsetGetNotImplemented()
    {
        try {
            $resultList = $this->getResultsFor('New Zealand', 10);
            $resultList->offsetGet(10);
            $this->assertFalse(true, 'This line should not have been reached');
        } catch (Exception $e) {
            $this->assertEquals('Not implemented', $e->getMessage());
        }
    }

    public function testResultListOffsetSetNotImplemented()
    {
        try {
            $resultList = $this->getResultsFor('New Zealand', 10);
            $resultList->offsetSet(10, null);
            $this->assertFalse(true, 'This line should not have been reached');
        } catch (Exception $e) {
            $this->assertEquals('Not implemented', $e->getMessage());
        }
    }

    public function testResultListOffsetUnsetNotImplemented()
    {
        try {
            $resultList = $this->getResultsFor('New Zealand', 10);
            $resultList->offsetUnset(10);
            $this->assertFalse(true, 'This line should not have been reached');
        } catch (Exception $e) {
            $this->assertEquals('Not implemented', $e->getMessage());
        }
    }

    public function testResultListAddNotImplemented()
    {
        try {
            $resultList = $this->getResultsFor('New Zealand', 10);
            $fp = new FlickrPhotoTO();
            $resultList->add($fp);
            $this->assertFalse(true, 'This line should not have been reached');
        } catch (Exception $e) {
            $this->assertEquals('Not implemented', $e->getMessage());
        }
    }

    public function testResultListRemoveNotImplemented()
    {
        try {
            $resultList = $this->getResultsFor('New Zealand', 10);
            $fp = new FlickrPhotoTO();
            $resultList->remove($fp);
            $this->assertFalse(true, 'This line should not have been reached');
        } catch (Exception $e) {
            $this->assertEquals('Not implemented', $e->getMessage());
        }
    }

    public function testResultListFindNotImplemented()
    {
        try {
            $resultList = $this->getResultsFor('New Zealand', 10);
            $fp = new FlickrPhotoTO();
            $resultList->find(4, $fp);
            $this->assertFalse(true, 'This line should not have been reached');
        } catch (Exception $e) {
            $this->assertEquals('Not implemented', $e->getMessage());
        }
    }

    public function testResultListToArray()
    {
        $sfs = SearchableField::get()->filter(array('ClazzName' => 'FlickrPhotoTO', 'Type' => 'string'));
        foreach ($sfs->getIterator() as $sf) {
            $sf->ShowHighlights = true;
            $sf->write();
        }

        $fields = array('Title' => 1, 'Description' => 1);
        $resultList = $this->getResultsFor('New Zealand', 10, $fields);

        $toarr = $resultList->toArray();

        foreach ($toarr as $item) {
            $hl = $item->SearchHighlights;
            foreach ($hl as $shl) {
                $html = $shl->Snippet;
                $splits = explode('<strong class="hl">', $html);
                if (sizeof($splits) > 1) {
                    $splits = explode('</strong>', $splits[1]);
                    $term = $splits[0];
                    $term = strtolower($term);
                    $this->assertEquals('new', $term);
                }
            }
        }
    }

    public function testResultListFirstNotImplemented()
    {
        try {
            $resultList = $this->getResultsFor('New Zealand', 10);
            $resultList->first();
            $this->assertFalse(true, 'This line should not have been reached');
        } catch (Exception $e) {
            $this->assertEquals('Not implemented', $e->getMessage());
        }
    }

    public function testResultListLastNotImplemented()
    {
        try {
            $resultList = $this->getResultsFor('New Zealand', 10);
            $resultList->last();
            $this->assertFalse(true, 'This line should not have been reached');
        } catch (Exception $e) {
            $this->assertEquals('Not implemented', $e->getMessage());
        }
    }

    public function testFoldedIndexes()
    {
        $this->markTestIncomplete('Folded test to do');
    }

    public function testSynonymIndexes()
    {
        $this->markTestIncomplete('Synonym test to do');
    }

    public function testNonExistentField()
    {
        try {
            // this should fail as field Fwlubble does not exist
            $this->search('zealand', 0, array('Fwlubble' => 1));
            $this->assertTrue(false, 'Field Fwlubble does not exist and an exception should have been thrown');
        } catch (Exception $e) {
            $this->assertTrue(true, 'Field Fwlubble does not exist, exception thrown as expected');
        }
    }

    public function testPriming()
    {
        $searchableClasses = SearchableClass::get();
        $sortedNames = $searchableClasses->Map('Name')->toArray();
        sort($sortedNames);

        $expected = array(
        '0' => 'FlickrAuthorTO',
        '1' => 'FlickrPhotoTO',
        '2' => 'FlickrSetTO',
        '3' => 'FlickrTagTO',
        '4' => 'Page',
        '5' => 'SearchableTestPage',
        '6' => 'SiteTree',
        );
        $this->assertEquals($expected, $sortedNames);

        $searchableFields = SearchableField::get();
        $expected = array(
            '0' => 'Aperture',
            '1' => 'AspectRatio',
            '2' => 'Content',
            '3' => 'Country',
            '4' => 'Description',
            '5' => 'DisplayName',
            '6' => 'FirstViewed',
            '7' => 'FlickrID',
            '8' => 'FlickrPhotoTOs',
            '9' => 'FlickrSetTOs',
            '10' => 'FlickrTagTOs',
            '11' => 'FocalLength35mm',
            '12' => 'ISO',
            '13' => 'PageDate',
            '14' => 'PathAlias',
            '15' => 'Photographer',
            '16' => 'RawValue',
            '17' => 'ShutterSpeed',
            '18' => 'TakenAt',
            '19' => 'TakenAtDT',
            '20' => 'TestMethod',
            '21' => 'TestMethodHTML',
            '22' => 'Title',
        );

        $sortedNames = array_keys($searchableFields->Map('Name')->toArray());
        sort($sortedNames);
        $this->assertEquals($expected, $sortedNames);
    }

    /**
     * Test searching
     * http://stackoverflow.com/questions/28305250/elasticsearch-customize-score-for-synonyms-stemming.
     */
    private function search($query, $resultsExpected = 10, $fields = null)
    {
        $es = new ElasticaSearcher();
        $es->setStart(0);
        $es->setPageLength(100);
        $es->setClasses('FlickrPhotoTO');
        $results = $es->search($query, $fields);
        $this->assertEquals($resultsExpected, $results->count());

        return $results->count();
    }

    private function getResultsFor($query, $pageLength = 10, $fields = array('Title' => 1, 'Description' => 1))
    {
        $es = new ElasticaSearcher();
        $es->setStart(0);
        $es->setPageLength($pageLength);
        $es->setClasses('FlickrPhotoTO');
        $resultList = $es->search($query, $fields)->getList();
        $this->assertEquals('SilverStripe\Elastica\ResultList', get_class($resultList));

        return $resultList;
    }
}
