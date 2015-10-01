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


	public function testBulkIndexing() {
		//Reset the index, so that nothing is indexed
		$this->service->reset();

		//Number of requests indexing wise made to Elasticsearch server
		$reqs = $this->service->getIndexingRequestCtr();

		$task = new ReindexTask($this->service);

		// null request is fine as no parameters used
		$task->run(null);

		//Check that the number of indexing requests has increased by 3
		$deltaReqs = $this->service->getIndexingRequestCtr() - $reqs;

		//Each SilverStripe class invocates a bulk index call, here we have
		//Page and FlickrPhoto
		$this->assertEquals(2,$deltaReqs);

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
		$this->checkNumberOfIndexedDocuments(100);
		$this->service->reset();
		$this->checkNumberOfIndexedDocuments(-1);
	}

	public function testEnsureIndex() {
		$index = $this->service->getIndex();
		$this->assertTrue($index->exists());

		// Check the number of documents
		$this->checkNumberOfIndexedDocuments(100);


		// Delete the index
		$task = new DeleteIndexTask($this->service);

		// null request is fine as no parameters used
		$task->run(null);

		// call protected method
		$this->invokeMethod($this->service, 'ensureIndex', array());

		$this->checkNumberOfIndexedDocuments(-1);


		//FIXME better options for testing here?
	}


	public function testDeleteIndex() {
		$index = $this->service->getIndex();
		$this->assertTrue($index->exists());

		// Check the number of documents
		$this->checkNumberOfIndexedDocuments(100);


		// Delete the index
		$task = new DeleteIndexTask($this->service);

		//null request is fine as no parameters used
		$task->run(null);

		$this->checkNumberOfIndexedDocuments(-1);

		//FIXME better options for testing here?
	}






}
