<?php

/**
 * Teste the functionality of the Searchable extension
 * @package elastica
 */
class ElasticSearchPageSearchFieldTest extends ElasticsearchBaseTest {

	public function testCMSFields() {
		$sf = new ElasticSearchPageSearchField();
		$sf->Name = 'TestField';
		$sf->write();


		$fields = $sf->getCMSFields();

		$tab = $this->checkTabExists($fields,'Main');

		//Check fields
		$nf = $this->checkFieldExists($tab, 'Name');
		$this->assertTrue($nf->isDisabled());

		$this->checkFieldExists($tab, 'Weight');
		$this->checkFieldExists($tab, 'Searchable');
	}


	/* Zero weight is pointless as it means not part of the search */
	public function testZeroWeight() {
		$sf = new ElasticSearchPageSearchField();
		$sf->Name = 'TestField';
		$sf->Weight = 0;
		try {
			$sf->write();
		} catch (ValidationException $e) {
			$this->assertInstanceOf('ValidationException', $e);
		}
		$this->assertEquals(0, $sf->ID);
	}


	/* Weights must be positive */
	public function testNegativeWeight() {
		$sf = new ElasticSearchPageSearchField();
		$sf->Name = 'TestField';
		$sf->Weight = -1;
		try {
			$sf->write();
		} catch (ValidationException $e) {
			$this->assertInstanceOf('ValidationException', $e);
		}
		$this->assertEquals(0, $sf->ID);
	}


	public function testDefaultWeight() {
		$sf = new ElasticSearchPageSearchField();
		$sf->Name = 'TestField';
		$sf->write();
		$this->assertEquals(1, $sf->Weight);
		$this->assertTrue($sf->ID > 0);
	}


	public function testPositiveWeight() {
		$sf = new ElasticSearchPageSearchField();
		$sf->Name = 'TestField';
		$sf->Weight = 10;
		$sf->write();
		$this->assertEquals(1, $sf->Weight);
		$this->assertEquals(10, $sf->Weight);
		$this->assertTrue($sf->ID > 0);
	}

}
