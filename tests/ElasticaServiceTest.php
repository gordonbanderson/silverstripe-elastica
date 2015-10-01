<?php

use SilverStripe\Elastica\ElasticaService;
use SilverStripe\Elastica\DeleteIndexTask;
use SilverStripe\Elastica\Searchable;

/**
 * Teste the functionality ElasticaService class
 * @package elastica
 */
class ElasticaServiceTest extends ElasticsearchBaseTest {

	public static $fixture_file = 'elastica/tests/aFewPhotos.yml';


	public function testBulkIndexing() {
		echo "\n\n++++++++++++ BULK INDEXING TEST +++++++++++\n";
		$nDocsAtStart = $this->getNumberOfIndexedDocuments();
		$this->checkNumberOfIndexedDocuments($nDocsAtStart);

		echo "DOCS AT START:".$nDocsAtStart."\n";

		Injector::inst()->get('FlickrPhoto')->startBulkIndex();

		$fp = new FlickrPhoto();
		$fp->Title = 'The cat sits on the mat';
		$fp->write();

		// not yet indexed, so should still be 100
		$this->checkNumberOfIndexedDocuments($nDocsAtStart);

		$fp2 = new FlickrPhoto();
		$fp2->Title = 'The cat sat on the hat';
		$fp2->write();
		// not yet indexed, so should still be the same
		$this->checkNumberOfIndexedDocuments($nDocsAtStart);

		Injector::inst()->get('FlickrPhoto')->endBulkIndex();

		$this->checkNumberOfIndexedDocuments($nDocsAtStart+2);
	}



	public function testNonBulkIndexing() {
		echo "\n\n++++++++++++ NON BULK INDEXING TEST +++++++++++\n";

		$nDocsAtStart = $this->getNumberOfIndexedDocuments();
		echo "DOCS AT START:".$nDocsAtStart."\n";

		echo "COUNT:".$this->service->getIndex()->count();

		$this->checkNumberOfIndexedDocuments($nDocsAtStart);


		$fp = new FlickrPhoto();
		$fp->Title = 'The cat sits on the mat';
		$fp->write();
		echo "T1\n";
		$this->checkNumberOfIndexedDocuments($nDocsAtStart+1);

		$fp2 = new FlickrPhoto();
		$fp2->Title = 'The cat sat on the hat';
		$fp2->write();
		echo "T2\n";

		$this->checkNumberOfIndexedDocuments($nDocsAtStart+2);
		echo "T3\n";
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
