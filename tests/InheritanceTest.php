<?php

use SilverStripe\Elastica\Searchable;

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
		$this->assertEquals($expected, array_keys($terms['Title.standard']['terms']));
		// ---- Test a page of parent parent class ----


		$page = $this->objFromFixture('SearchableTestGrandFatherPage', 'grandfather0001');
		$this->assertTrue($page->hasExtension('SilverStripe\Elastica\Searchable'),
			'Page extending SiteTree has Searchable extension');

		$fields = $page->getElasticaFields();
		$this->assertTrue(isset($fields['Title']['fields']['autocomplete']));

		echo "---- TERMS ----\n";

		$terms = $page->getTermVectors();
		print_r(array_keys($terms));

		$fatherTerms = $terms['FatherText']['terms'];
		$grandFatherTerms = $terms['GrandFatherText']['terms'];

		$expected = array();
		$this->assertEquals($expected, $fatherTerms);

	}


}
