<?php

use SilverStripe\Elastica\ElasticaSearcher;
use SilverStripe\Elastica\ReindexTask;

class TranslatableUnitTest extends ElasticsearchBaseTest
{
    public static $fixture_file = 'elastica/tests/lotsOfPhotos.yml';

    public static $ignoreFixtureFileFor = array('testResultsForEmptySearch');

    public function setUpOnce()
    {
        //Add translatable if it exists
        if (class_exists('Translatable')) {
            SiteTree::add_extension('Translatable');
        }
        parent::setUpOnce();
    }

    public function testElasticSearchForm()
    {
        if (!class_exists('Translatable')) {
            $this->markTestSkipped('Translatable not installed');
        }

        $form = new \ElasticSearchForm(new \Controller(), 'TestForm');
        $fields = $form->Fields();
        $result = array();
        foreach ($fields as $field) {
            $result[$field->getName()] = $field->Value();
        }

        $expected = array('q' => '', 'searchlocale' => 'en_US');
        $this->assertEquals($expected, $result);
    }

    public function testHighlightPassingFields()
    {
        if (!class_exists('Translatable')) {
            $this->markTestSkipped('Translatable not installed');
        }

        $es = new ElasticaSearcher();
        $es->setClasses('FlickrPhotoTO');

        $es->setHighlightedFields(array('Title', 'Title.standard', 'Description'));

        $fields = array('Title' => 1, 'Description' => 1);
        $query = 'New Zealand';
        $paginated = $es->search($query, $fields);
        $ctr = 0;

        foreach ($paginated->getList()->toArray() as $result) {
            ++$ctr;

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
                $this->assertTrue($wordFound, 'Highlight should have been found');
            }
        }
    }

    public function testAutoCompleteGood()
    {
        if (!class_exists('Translatable')) {
            $this->markTestSkipped('Translatable not installed');
        }

        $es = new ElasticaSearcher();
        $es->setClasses('FlickrPhotoTO');
        $query = 'Lond';
        $results = $es->autocomplete_search($query, 'Title');
        $this->assertEquals(7, $results->getTotalItems());
        foreach ($results->toArray() as $result) {
            $this->assertTrue(strpos($result->Title, $query) > 0);
        }
    }

// ---------------------

    // FIXME - this test is shardy unfortunately
    public function testMoreLikeThisSinglePhoto()
    {
        if (!class_exists('Translatable')) {
            $this->markTestSkipped('Translatable not installed');
        }

        $fp = $this->objFromFixture('FlickrPhotoTO', 'photo0076');
        $es = new ElasticaSearcher();
        $locale = \i18n::default_locale();
        $es->setLocale($locale);
        $es->setClasses('FlickrPhotoTO');

        $fields = array('Description.standard' => 1, 'Title.standard' => 1);
        $results = $es->moreLikeThis($fp, $fields, true);

        $terms = $results->getList()->MoreLikeThisTerms;

        $fieldNamesReturned = array_keys($terms);
        $fieldNames = array_keys($fields);
        sort($fieldNames);
        sort($fieldNamesReturned);

        $this->assertEquals($fieldNames, $fieldNamesReturned);

        //FIXME - this seems anomolyous, check in more detail
        $expected = array('texas');
        $this->assertEquals($expected, $terms['Title.standard']);

        $expected = array('collection', 'company', 'degolyer', 'everett', 'file', 'high',
            'information', 'new', 'orleans', 'pacific', 'photographs', 'railroad', 'resolution',
            'see', 'southern', 'texas', 'view', );

        $actual = $terms['Description.standard'];
        sort($expected);
        sort($actual);
        $this->assertEquals($expected, $actual);
    }

    /*
    test blank fields
    test fields with no weighting (ie not associative)

     */

    public function testSimilarGood()
    {
        if (!class_exists('Translatable')) {
            $this->markTestSkipped('Translatable not installed');
        }

        $fp = $this->objFromFixture('FlickrPhotoTO', 'photo0076');
        $es = new ElasticaSearcher();
        $es->setClasses('FlickrPhotoTO');
        $fields = array('Title.standard' => 1, 'Description.standard' => 1);
        $paginated = $es->moreLikeThis($fp, $fields, true);

        $this->assertEquals(32, $paginated->getTotalItems());
        $results = $paginated->getList()->toArray();
        $this->assertEquals('[Texas and New Orleans, Southern Pacific Railroad Station, Stockdale, Texas]', $results[0]->Title);
        $this->assertEquals('[Texas and New Orleans, Southern Pacific Railroad Station, Taft, Texas]', $results[1]->Title);
        $this->assertEquals('[Texas and New Orleans, Southern Pacific Railroad Station, Sierra Blanca, Texas]', $results[2]->Title);
        $this->assertEquals('[Texas and New Orleans, Southern Pacific Freight Station, Waxahachie, Texas]', $results[3]->Title);
        $this->assertEquals('[Texas and New Orleans, Southern Pacific Passenger Station, Waxahachie, Texas]', $results[4]->Title);
        $this->assertEquals('[Texas and New Orleans, Southern Pacific, Tower No. 63, Mexia, Texas]', $results[5]->Title);
        $this->assertEquals('[Texas and New Orleans, Southern Pacific, Eakin Street Yard Office, Dallas, Texas]', $results[6]->Title);
        $this->assertEquals('[Texas and New Orleans, Southern Pacific Locomotive Scrap Line, Englewood Yards, Houston, Texas]', $results[7]->Title);
        $this->assertEquals("[Texas and New Orleans, Southern Pacific, Switchman's Tower, San Antonio, Texas]", $results[8]->Title);
        $this->assertEquals('Flash Light view in new Subterranean', $results[9]->Title);
    }

    /*
    FIXME - this is not working, not sure why.  Trying to complete coverage of ReindexTask
     */
    public function testBulkIndexing()
    {
        if (!class_exists('Translatable')) {
            $this->markTestSkipped('Translatable not installed');
        }

        //Reset the index, so that nothing has been indexed
        $this->service->reset();

        //Number of requests indexing wise made to Elasticsearch server
        $reqs = $this->service->getIndexingRequestCtr();

        $task = new ReindexTask($this->service);

        // null request is fine as no parameters used
        $task->run(null);

        //Check that the number of indexing requests has increased by 2
        $deltaReqs = $this->service->getIndexingRequestCtr() - $reqs;
        //One call is made for each of Page and FlickrPhotoTO
        $this->assertEquals(2, $deltaReqs);

        // default installed pages plus 100 FlickrPhotoTOs
        $this->checkNumberOfIndexedDocuments(103);
    }

    // if this is not set to unbounded, zero, a conditional is triggered to add max doc freq to the request
    public function testSimilarChangeMaxDocFreq()
    {
        if (!class_exists('Translatable')) {
            $this->markTestSkipped('Translatable not installed');
        }

        $fp = $this->objFromFixture('FlickrPhotoTO', 'photo0076');
        $es = new ElasticaSearcher();
        $es->setMaxDocFreq(4);
        $es->setClasses('FlickrPhotoTO');
        $fields = array('Title.standard' => 1, 'Description.standard' => 1);
        $paginated = $es->moreLikeThis($fp, $fields, true);

        $this->assertEquals(14, $paginated->getTotalItems());
    }

    public function testSimilarNullFields()
    {
        if (!class_exists('Translatable')) {
            $this->markTestSkipped('Translatable not installed');
        }

        $fp = $this->objFromFixture('FlickrPhotoTO', 'photo0076');
        $es = new ElasticaSearcher();
        $es->setClasses('FlickrPhotoTO');
        try {
            $es->moreLikeThis($fp, null, true);
            $this->fail('More like this search should have failed');
        } catch (InvalidArgumentException $e) {
            $this->assertEquals('Fields cannot be null', $e->getMessage());
        }
    }

    public function testSimilarNullItem()
    {
        if (!class_exists('Translatable')) {
            $this->markTestSkipped('Translatable not installed');
        }

        $es = new ElasticaSearcher();
        $es->setClasses('FlickrPhotoTO');
        $fields = array('Title.standard' => 1, 'Description.standard' => 1);

        try {
            $es->moreLikeThis(null, $fields, true);
            $this->fail('Search should have failed');
        } catch (InvalidArgumentException $e) {
            $this->assertEquals('A searchable item cannot be null', $e->getMessage());
        }
    }
}
