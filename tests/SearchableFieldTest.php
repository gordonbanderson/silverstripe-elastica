<?php

/**
 * Test the functionality of the Searchable extension
 * @package elastica
 */
class SearchableFieldTest extends ElasticsearchBaseTest {
	public static $fixture_file = 'elastica/tests/ElasticaTest.yml';

	public function testCMSFields() {
		$sf = new SearchableField();
		$sf->Name = 'TestField';
		$sf->ClazzName = 'TestClazz';
		$sf->write();

		$fields = $sf->getCMSFields();

		$tab = $this->checkTabExists($fields,'Main');

		//Check fields
		$nf = $this->checkFieldExists($tab, 'Name');
		$this->assertTrue($nf->isDisabled());
	}




	/* Zero weight is pointless as it means not part of the search */
	public function testZeroWeight() {
		$searchPage = $this->objFromFixture('ElasticSearchPage', 'search');
		$lastEdited = $searchPage->LastEdited;

		$extraFields = array('Searchable' => 1, 'SimilarSearchable' => 1, 'Active' => 1,
			'Weight' => 0);
		$esfs = $searchPage->ElasticaSearchableFields();
		foreach ($esfs as $sf) {
			if ($sf->Name == 'Title' || $sf->Name == 'Description') {
				$esfs->remove($sf);
				$esfs->add($sf, $extraFields);
			}
		}

		try {
			$searchPage->write();
			$this->fail('Searchable fail should have failed to write');
		} catch (ValidationException $e) {
			$this->assertInstanceOf('ValidationException', $e);
			$expected = 'The field SearchableTestPage.Title has a zero weight. ; The field '.
			'SiteTree.Title has a zero weight. ; The field Page.Title has a zero weight. ';
			$this->assertEquals($expected, $e->getMessage());
		}

		//Effectively assert that the search page has not been written by checking LastEdited
		$pageFromDB = DataObject::get_by_id('ElasticSearchPage', $searchPage->ID);
		$this->assertEquals($lastEdited, $pageFromDB->LastEdited);
	}


	/* Weights must be positive */
	public function testNegativeWeight() {
		$searchPage = $this->objFromFixture('ElasticSearchPage', 'search');
		$lastEdited = $searchPage->LastEdited;

		$extraFields = array('Searchable' => 1, 'SimilarSearchable' => 1, 'Active' => 1,
			'Weight' => -1);
		$esfs = $searchPage->ElasticaSearchableFields();
		foreach ($esfs as $sf) {
			if ($sf->Name == 'Title' || $sf->Name == 'Description') {
				$esfs->remove($sf);
				$esfs->add($sf, $extraFields);
			}
		}

		try {
			$searchPage->write();
			$this->fail('Searchable fail should have failed to write');
		} catch (ValidationException $e) {
			$this->assertInstanceOf('ValidationException', $e);
			$expected = 'The field SearchableTestPage.Title has a negative weight. ; The field '.
			'SiteTree.Title has a negative weight. ; The field Page.Title has a negative weight. ';
			$this->assertEquals($expected, $e->getMessage());
		}
		//Effectively assert that the search page has not been written by checking LastEdited
		$pageFromDB = DataObject::get_by_id('ElasticSearchPage', $searchPage->ID);
		$this->assertEquals($lastEdited, $pageFromDB->LastEdited);
	}


	public function testDefaultWeight() {
		$searchPage = $this->objFromFixture('ElasticSearchPage', 'search');
		//$searchPage->write();
		$sf = $searchPage->ElasticaSearchableFields()->first();
		$this->assertEquals(1, $sf->Weight);
		$this->assertTrue($sf->ID > 0);
	}


	public function testPositiveWeight() {
		$sf = new SearchableField();
		$sf->Name = 'TestField';
		$sf->Weight = 10;
		$sf->write();
		$this->assertEquals(10, $sf->Weight);
		$this->assertTrue($sf->ID > 0);
	}


	public function testHumanReadableSearchable() {

		$searchPage = $this->objFromFixture('ElasticSearchPage', 'search');

		//ensure valid
		$extraFields = array('Searchable' => 1, 'SimilarSearchable' => 1, 'Active' => 1,
			'Weight' => 1);
		$sf = $searchPage->ElasticaSearchableFields()->first();


		$sf->Name = 'TestField';
		$sf->Searchable = false;
		$this->assertEquals('No', $sf->HumanReadableSearchable());
		$sf->Searchable = true;
		$this->assertEquals('Yes', $sf->HumanReadableSearchable());
	}


	// ---- searchable fields are created via a script, so test do not allow creation/deletion ----

	/*
	Ensure CMS users cannot delete searchable fields
	 */
	public function testCanDelete() {
		$sf = new SearchableField();
		$this->assertFalse($sf->canDelete());
	}

	/*
	Ensure CMS users cannot create searchable fields
	 */
	public function testCanCreate() {
		$sf = new SearchableField();
		$this->assertFalse($sf->canCreate());
	}


}
