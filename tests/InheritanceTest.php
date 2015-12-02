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

		echo "\n\n\n\n\n\n\n\n+++++++++++++++++ GRANDFATHER PAGE +++++++++++++++++\n\n\n\n";

		$page = $this->objFromFixture('SearchableTestGrandFatherPage', 'grandfather0001');
		$this->assertTrue($page->hasExtension('SilverStripe\Elastica\Searchable'),
			'Page extending SiteTree has Searchable extension');

		$fields = $page->getElasticaFields();
		$this->assertTrue(isset($fields['Title']['fields']['autocomplete']));

		echo "---- TERM VECTORS FOR GF PAGE ----\n";

		$terms = $page->getTermVectors();

		//Check the expected fields are indexed
		$expected = array('Content','Content.shingles','Content.standard','FatherText','FatherText.shingles','FatherText.standard',
			'GrandFatherText','GrandFatherText.shingles','GrandFatherText.standard','Link','Title','Title.autocomplete',
			'Title.shingles','Title.standard');
		$indexedFields = array_keys($terms);
		sort($indexedFields);
		foreach ($indexedFields as $field) {
			echo "'".$field."',";
		}
		$this->assertEquals($expected, $indexedFields);

		$fatherTerms = $terms['FatherText.standard']['terms'];
		$grandFatherTerms = $terms['GrandFatherText.standard']['terms'];

		$expected = array('father', 'field', 'grandfather', 'page', 'trace3');;
		$this->assertEquals($expected, array_keys($fatherTerms));

		$expected = array('grandfather', 'page', 'trace4');
		$this->assertEquals($expected, array_keys($grandFatherTerms));
	}

}
