<?php
class ElasticSearcherUnitTest extends SapphireTest {

	public function setUp() {
		parent::setUp();
	}

	public function tearDown() {
		parent::tearDown();
	}


	// ---- tests for the toQuotedCSV function ----
	public function testToQuotedCSVFromString() {
		$expected = "'Bangkok','Nonthaburi','Saraburi','Chiang Mai'";
		$items = 'Bangkok,Nonthaburi,Saraburi,Chiang Mai';
		$quoted = ElasticSearcher::convertToQuotedCSV($items);
		$this->assertEquals($expected, $quoted);
	}


	public function testToQuotedCSVFromArray() {
		$expected = "'Bangkok','Nonthaburi','Saraburi','Chiang Mai'";
		$items = array('Bangkok','Nonthaburi','Saraburi','Chiang Mai');
		$quoted = ElasticSearcher::convertToQuotedCSV($items);
		$this->assertEquals($expected, $quoted);
	}

	public function testToQuotedCSVEmptyString() {
		$quoted = ElasticSearcher::convertToQuotedCSV('');
		$this->assertEquals('', $quoted);
	}

	public function testToQuotedCSVEmptyArray() {
		$quoted = ElasticSearcher::convertToQuotedCSV(array());
		$this->assertEquals('', $quoted);
	}

	public function testToQuotedCSVNull() {
		$quoted = ElasticSearcher::convertToQuotedCSV(null);
		$this->assertEquals('', $quoted);
	}

}
