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
		$sf = $searchPage->ElasticaSearchableFields()->first();
		$sf->Weight = 0;

		try {
			$searchPage->write();
			$this->fail('Searchable fail should have failed to write');
		} catch (ValidationException $e) {
			$this->assertInstanceOf('ValidationException', $e);
		}
		$this->assertEquals(0, $sf->ID);
	}


	/* Weights must be positive */
	public function testNegativeWeight() {
		$searchPage = $this->objFromFixture('ElasticSearchPage', 'search');
		$sf = $searchPage->ElasticaSearchableFields()->first();
		$sf->Weight = -1;
		try {
			$searchPage->Title="Some other title";
			echo "Writing....";
			$searchPage->write();
			$this->fail('Should have failed due to negative weighting');
		} catch (ValidationException $e) {
			$this->assertInstanceOf('ValidationException', $e);
		}

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
		$sf = new SearchableField();
		$sf->Name = 'TestField';
		$sf->Searchable = false;
		$this->assertEquals('No', $sf->HumanReadableSearchable());
		$sf->Searchable = true;
		$this->assertEquals('Yes', $sf->HumanReadableSearchable());
	}


}
