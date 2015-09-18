<?php

/**
 * @package comments
 */
class FindElasticaPageExtensionTest extends SapphireTest {
	public static $fixture_file = 'elastica/tests/ElasticaTest.yml';

	protected $extraDataObjects = array(
		'SearchableTestPage','FlickrPhoto','FlickrAuthor','FlickrSet','FlickrTag'
	);

	/**
	 * Test a valid identifier
	 */
	public function testValidIdentifier() {
		$searchPage = $this->objFromFixture('ElasticSearchPage', 'search');

		//FindElasticaPageExtension is attached to SiteTree so we can search with any page.  Here it
		//convenient to use the target pgae in order to test for comparison
		$found = $searchPage->getSearchPage('testsearchpage');

		$this->assertEquals($searchPage->ID, $found->ID);
		$this->assertEquals($searchPage->ClassName, $found->ClassName);
		$this->assertEquals($searchPage->Title, $found->Title);
		$this->assertEquals('testsearchpage', $found->Identifier);
	}


	public function testInvalidIdentifier() {
		$searchPage = $this->objFromFixture('ElasticSearchPage', 'search');

		//FindElasticaPageExtension is attached to SiteTree so we can search with any page.  Here it
		//convenient to use the target pgae in order to test for comparison
		$found = $searchPage->getSearchPage('notasearchpageidentifier');

		$this->assertEquals(null, $found);
	}


	public function testNullIdentifier() {
		$searchPage = $this->objFromFixture('ElasticSearchPage', 'search');

		//FindElasticaPageExtension is attached to SiteTree so we can search with any page.  Here it
		//convenient to use the target pgae in order to test for comparison
		$found = $searchPage->getSearchPage('notasearchpageidentifier');

		$this->assertEquals(null, $found);
	}


	public function testDuplicateIdentifier() {
		$searchPage = $this->objFromFixture('ElasticSearchPage', 'search');

		$esp = new ElasticSearchPage();
		// ensure default identifier
		$esp->Identifier = $searchPage->Identifier;
		$esp->Title='This should not be saved';
		try {
			$esp->write();
			$this->assertFalse('Duplicate identifier was incorrectly saved');
		} catch (Exception $e) {
			$this->assertTrue('The page could not be saved as expected, due to duplicate
								identifier');
		}

		$this->assertEquals(null, $found);
	}

}
