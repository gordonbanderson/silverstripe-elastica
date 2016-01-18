<?php

/**
 */
class FindElasticaPageExtensionTest extends ElasticsearchTestBase
{
    public static $fixture_file = 'elastica/tests/ElasticaTest.yml';

    /**
     * Test a valid identifier.
     */
    public function testValidIdentifier()
    {
        $searchPage = $this->objFromFixture('ElasticSearchPage', 'search');

        //FindElasticaPageExtension is attached to SiteTree so we can search with any page.  Here it
        //convenient to use the target pgae in order to test for comparison
        $found = $searchPage->getSearchPage('testsearchpage');

        $this->assertEquals($searchPage->ID, $found->ID);
        $this->assertEquals($searchPage->ClassName, $found->ClassName);
        $this->assertEquals($searchPage->Title, $found->Title);
        $this->assertEquals('testsearchpage', $found->Identifier);

        // don't check the server name as this will differ, just check the path is /search/
        $uri = $searchPage->SearchPageURI('testsearchpage');
        $splits = explode('/', $uri);
        $this->assertEquals($splits[3], 'search');

        // check the form
        $form = $searchPage->SearchPageForm('testsearchpage');
        $this->assertInstanceOf('ElasticSearchForm', $form);

        $fields = $form->Fields();
        $actions = $form->Actions();

        // check for the query text box field
        $this->assertEquals('q', $fields->FieldByName('q')->getName());

        // check for the submit button
        $this->assertEquals('action_submit', $actions->FieldByName('action_submit')->getName());
    }

    public function testButtonOverride()
    {
        $searchPage = $this->objFromFixture('ElasticSearchPage', 'search');
        $buttonText = 'Search Me!';
        $form = $searchPage->SearchPageForm('testsearchpage', $buttonText);
        $actions = $form->Actions();
        $this->assertEquals($buttonText, $actions->FieldByName('action_submit')->Title());
    }

    public function testInvalidIdentifier()
    {
        $searchPage = $this->objFromFixture('ElasticSearchPage', 'search');

        //FindElasticaPageExtension is attached to SiteTree so we can search with any page.  Here it
        //convenient to use the target pgae in order to test for comparison
        $found = $searchPage->getSearchPage('notasearchpageidentifier');

        $this->assertEquals(null, $found);
        $uri = $searchPage->SearchPageURI('notasearchpageidentifier');

        $this->assertEquals('', $uri);

        $this->assertEquals(null, $searchPage->SearchPageForm('notasearchpageidentifier'));
    }

    public function testNullIdentifier()
    {
        $searchPage = $this->objFromFixture('ElasticSearchPage', 'search');

        //FindElasticaPageExtension is attached to SiteTree so we can search with any page.  Here it
        //convenient to use the target pgae in order to test for comparison
        $found = $searchPage->getSearchPage(null);
        $this->assertEquals(null, $found);
        $uri = $searchPage->SearchPageURI(null);
        $this->assertEquals('', $uri);
        $this->assertEquals(null, $searchPage->SearchPageForm(null));
    }

    public function testDuplicateIdentifier()
    {
        $searchPage = $this->objFromFixture('ElasticSearchPage', 'search');

        $esp = new ElasticSearchPage();
        // ensure default identifier
        $esp->Identifier = $searchPage->Identifier;
        $esp->Title = 'This should not be saved';
        try {
            $esp->write();
            $this->assertFalse(true, 'Duplicate identifier was incorrectly saved');
        } catch (Exception $e) {
            $this->assertTrue(true, 'The page could not be saved as expected, due to duplicate '.
                                'identifier');
        }
    }
}
