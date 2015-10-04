<?php

use SilverStripe\Elastica\ElasticaService;
use SilverStripe\Elastica\ReindexTask;
use SilverStripe\Elastica\DeleteIndexTask;
use SilverStripe\Elastica\Searchable;

/**
 * Teste the functionality ElasticaService class
 * @package elastica
 */
class ElasticaServiceTest extends ElasticsearchBaseTest {

	public static $fixture_file = 'elastica/tests/lotsOfPhotos.yml';


	public function testCreateIndexInvalidLocale() {
		// fake locale
		$this->service->setLocale('sw_NZ');
		try {
			$this->invokeMethod($this->service, 'createIndex', array());
			$this->assertFalse(true, "Creation of index with unknown locale should have failed");
		} catch (Exception $e) {
			$this->assertTrue(true,"Creation of index with unknown locale failed as expected");
		}


	}


	public function testEnsureMapping() {
		$this->service->reset();

		$index = $this->service->getIndex();


		$mapping = $index->getMapping();
		//print_r($mapping);

		$type = $index->getType('FlickrPhoto');
		$record = FlickrPhoto::get()->first();
		$mapping = $this->invokeMethod($this->service, 'ensureMapping', array($type, $record));
 		//assertTrue(false, 'Figure out what this method should do');
		//print_r($mapping);
	}


	public function testEnsureIndex() {
		// Check that an index currently exists, it will from setup method
		$this->assertTrue($this->service->getIndex()->exists());

		// Ensure the index exists when it already exists case
		$this->invokeMethod($this->service, 'ensureIndex', array());
		$this->assertTrue($this->service->getIndex()->exists());

		// Delete and assert that it does not exist
		$this->service->getIndex()->delete();
		$this->assertFalse($this->service->getIndex()->exists());

		// Ensure the index exists when it does not exist case
		$this->invokeMethod($this->service, 'ensureIndex', array());
		$this->assertTrue($this->service->getIndex()->exists());
	}


	public function testShowInSearch() {
		$nDocsAtStart = $this->getNumberOfIndexedDocuments();
		$this->checkNumberOfIndexedDocuments($nDocsAtStart);

		$fp = Page::get()->first();
		$fp->ShowInSearch = false;
		$fp->write();
		$this->service->getIndex()->refresh();

		$this->checkNumberOfIndexedDocuments($nDocsAtStart-1);

		$fp->ShowInSearch = true;
		$fp->write();
		$this->service->getIndex()->refresh();

		$this->checkNumberOfIndexedDocuments($nDocsAtStart);
	}


	public function testUnpublish() {
		$nDocsAtStart = $this->getNumberOfIndexedDocuments();
		$this->checkNumberOfIndexedDocuments($nDocsAtStart);

		$fp = Page::get()->first();
		$fp->doUnpublish();

		$this->service->getIndex()->refresh();
		$this->checkNumberOfIndexedDocuments($nDocsAtStart-1);
	}


	public function testDeleteFromSiteTree() {
		$nDocsAtStart = $this->getNumberOfIndexedDocuments();
		$this->checkNumberOfIndexedDocuments($nDocsAtStart);

		$fp = Page::get()->first();
		$fp->delete();

		$this->service->getIndex()->refresh();
		$this->checkNumberOfIndexedDocuments($nDocsAtStart-1);
	}


	public function testDeleteDataObject() {
		$nDocsAtStart = $this->getNumberOfIndexedDocuments();
		$this->checkNumberOfIndexedDocuments($nDocsAtStart);

		$fp = FlickrPhoto::get()->first();
		$fp->delete();

		$this->service->getIndex()->refresh();
		$this->checkNumberOfIndexedDocuments($nDocsAtStart-1);
	}


	public function testBulkIndexing() {
		//Reset the index, so that nothing has been indexed
		$this->service->reset();

		//Number of requests indexing wise made to Elasticsearch server
		$reqs = $this->service->getIndexingRequestCtr();

		$task = new ReindexTask($this->service);

		// null request is fine as no parameters used
		$task->run(null);

		//Check that the number of indexing requests has increased by 2
		$deltaReqs = $this->service->getIndexingRequestCtr() - $reqs;
		//One call is made for each of Page and FlickrPhoto
		$this->assertEquals(2,$deltaReqs);

		// default installed pages plus 100 FlickrPhotos
		$this->checkNumberOfIndexedDocuments(103);
	}



	public function testNonBulkIndexing() {
		//Number of requests indexing wise made to Elasticsearch server
		$reqs = $this->service->getIndexingRequestCtr();
		$nDocsAtStart = $this->getNumberOfIndexedDocuments();
		$this->checkNumberOfIndexedDocuments($nDocsAtStart);

		//Check index size after each document indexed
		$fp = new FlickrPhoto();
		$fp->Title = 'The cat sits on the mat';
		$fp->write();
		$this->checkNumberOfIndexedDocuments($nDocsAtStart+1);

		$fp2 = new FlickrPhoto();
		$fp2->Title = 'The cat sat on the hat';
		$fp2->write();
		$this->checkNumberOfIndexedDocuments($nDocsAtStart+2);

		$fp3 = new FlickrPhoto();
		$fp3->Title = 'The bat flew around the cat';
		$fp3->write();
		$this->checkNumberOfIndexedDocuments($nDocsAtStart+3);

		//Check that the number of indexing requests has increased by 3
		$deltaReqs = $this->service->getIndexingRequestCtr() - $reqs;
		$this->assertEquals(3,$deltaReqs);
	}



	/*
	Check for classes that should be there.   Others will possibly exist depending on
	which other modules are installed, hence no array comparison
	 */
	public function testGetIndexedClasses() {
		$indexedClasses = $this->service->getIndexedClasses();
		$this->assertContains('Page', $indexedClasses);
		$this->assertContains('SiteTree', $indexedClasses);
		$this->assertContains('FlickrAuthor', $indexedClasses);
		$this->assertContains('FlickrPhoto', $indexedClasses);
		$this->assertContains('FlickrSet', $indexedClasses);
		$this->assertContains('FlickrTag', $indexedClasses);
		$this->assertContains('SearchableTestPage', $indexedClasses);
	}


	/*
	-------------------------------
	FIXME: The following tests are problematic in that I'm not sure exactly what to check for
	to differentiate between deleted, reset, recreated etc.  It seems that checking an index
	status recreates a virgin copy of the index
	-------------------------------
	 */

	public function testResetIndex() {
		$index = $this->service->getIndex();
		$nDocsAtStart = $this->getNumberOfIndexedDocuments();
		$this->checkNumberOfIndexedDocuments($nDocsAtStart);
		$this->service->reset();
		$this->checkNumberOfIndexedDocuments(-1);
	}


	public function testDeleteIndex() {
		$index = $this->service->getIndex();
		$this->assertTrue($index->exists());

		// Check the number of documents
		$nDocsAtStart = $this->getNumberOfIndexedDocuments();
		$this->checkNumberOfIndexedDocuments($nDocsAtStart);


		// Delete the index
		$task = new DeleteIndexTask($this->service);

		//null request is fine as no parameters used
		$task->run(null);

		$this->checkNumberOfIndexedDocuments(-1);

		//FIXME better options for testing here?
	}

}
