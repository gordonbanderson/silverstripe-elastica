<?php

use SilverStripe\Elastica\Searchable;
use SilverStripe\Elastica\ReindexTask;


/**
 * Test that inheritance works correctly with configuration properties
 * @package elastica
 */
class InheritanceTest extends ElasticsearchBaseTest {
	public static $fixture_file = 'elastica/tests/ElasticaTest.yml';


	/*
	Check that searchable and autocomplete are inherited mapping wise
	 */
	public function testSearchableAndAutocompleteInherited() {
		$page = $this->objFromFixture('SearchableTestPage', 'first');
		$this->assertTrue($page->hasExtension('SilverStripe\Elastica\Searchable'),
			'Page extending SiteTree has Searchable extension');

		$fields = $page->getElasticaFields();
		$terms = $page->getTermVectors();

		$expected = array('first');
		$this->assertEquals($expected, array_keys($terms['Title.standard']['terms']));

		$expected = array('fi','fir','firs','first','ir','irs','irst','rs','rst','st');
		$this->assertEquals($expected, array_keys($terms['Title.autocomplete']['terms']));

		// ---- now a parental class page ----
		$this->assertTrue(isset($fields['Title']['fields']['autocomplete']));


		$page = $this->objFromFixture('SearchableTestFatherPage', 'father0001');
		$this->assertTrue($page->hasExtension('SilverStripe\Elastica\Searchable'),
			'Page extending SiteTree has Searchable extension');

		$fields = $page->getElasticaFields();
		$this->assertTrue(isset($fields['Title']['fields']['autocomplete']));

		$expected = array('first');
		$terms = $page->getTermVectors();
		//$this->assertEquals($expected, array_keys($terms['Title.standard']['terms']));
		// ---- Test a page of parent parent class ----


		echo "\n\n\n\n\n\n\n\n+++++++++++++++++ GRANDFATHER PAGE +++++++++++++++++\n\n\n\n";

		$page = $this->objFromFixture('SearchableTestGrandFatherPage', 'grandfather0001');
		$this->assertTrue($page->hasExtension('SilverStripe\Elastica\Searchable'),
			'Page extending SiteTree has Searchable extension');

		$fields = $page->getElasticaFields();
		$this->assertTrue(isset($fields['Title']['fields']['autocomplete']));

		echo "---- TERM VECTORS FOR GF PAGE ----\n";



		$terms = $page->getTermVectors();
		print_r($terms);

		echo "\nAKEYS\n";

		print_r(array_keys($terms));

		$fatherTerms = $terms['FatherText']['terms'];
		$grandFatherTerms = $terms['GrandFatherText']['terms'];




		$expected = array('a','father','field','grandfather','in','is','page','the','trace3');
		$this->assertEquals($expected, array_keys($fatherTerms));
		$expected = array();
		$this->assertEquals($expected, array_keys($grandFatherTerms));


	}



	public function testSearchableFatherTestPage() {
		echo "\n\n\n\n>>>>>>>>>>>>> TEST SEARCHABLE FATHER TEST PAGE <<<<<<<<<<<<<<<<<<\n\n\n\n";
		$page = $this->objFromFixture('SearchableTestFatherPage', 'father0001');
		$page->getAllSearchableFields();
	}


	public function testSearchableTestPage() {
				echo "\n\n\n\n>>>>>>>>>>>>> TEST SEARCHABLE TEST PAGE <<<<<<<<<<<<<<<<<<\n\n\n\n";

		$page = $this->objFromFixture('SearchableTestPage', 'first');
		$page->getAllSearchableFields();
	}



	public function testReindexing() {
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
		$this->assertEquals(2,$deltaReqs);

		// default installed pages plus 100 FlickrPhotoTOs
		$this->checkNumberOfIndexedDocuments(20);

	}


}
