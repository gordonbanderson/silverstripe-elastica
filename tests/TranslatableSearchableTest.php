<?php


/**
 * Test the functionality of the Searchable extension.
 */
class TranslatableSearchableTest extends ElasticsearchBaseTest
{
    public static $fixture_file = 'elastica/tests/ElasticaTest.yml';

    public function setUpOnce()
    {
        //Add translatable if it exists
        if (class_exists('Translatable')) {
            SiteTree::add_extension('Translatable');
        }
        parent::setUpOnce();
    }

    public function setUp()
    {
        // this needs to be called in order to create the list of searchable
        // classes and fields that are available.  Simulates part of a build
        $classes = array('SearchableTestPage', 'SiteTree', 'Page', 'FlickrPhotoTO', 'FlickrSetTO',
            'FlickrTagTO', 'FlickrAuthorTO', 'FlickrSetTO', );
        $this->requireDefaultRecordsFrom = $classes;
        // load fixtures
        parent::setUp();
    }

    public function testgetFieldValuesAsArrayWithLocale()
    {
        if (!class_exists('Translatable')) {
            $this->markTestSkipped('Translatable not installed');
        }

        $manyTypes = $this->objFromFixture('ManyTypesPage', 'manytypes0001');
        $result = $manyTypes->getFieldValuesAsArray();
        $this->generateAssertionsFromArray($result);

        $expected = array(
            'BooleanField' => '1',
            'CurrencyField' => '100.25',
            'DateField' => '2014-04-15',
            'DecimalField' => '0',
            'EnumField' => '',
            'HTMLTextField' => '',
            'HTMLVarcharField' => 'This is some *HTML*varchar field',
            'IntField' => '677',
            'PercentageField' => '27',
            'SS_DatetimeField' => '2014-10-18 08:24:00',
            'TextField' => 'This is a text field',
            'TimeField' => '17:48:18',
            'Title' => 'Many Types Page',
            'Content' => 'Many types of fields',
        );
        $this->assertEquals($expected, $result);
    }

    /**
     * Test a valid identifier.
     */
    public function testMappingWithLocale()
    {
        if (!class_exists('Translatable')) {
            $this->markTestSkipped('Translatable not installed');
        }

        $manyTypes = $this->objFromFixture('ManyTypesPage', 'manytypes0001');
        $mapping = $manyTypes->getElasticaMapping();

        // Only check mapping of locale as that is what has changed
        $properties = $mapping->getProperties();
        $localeProperties = $properties['Locale'];
        $this->assertEquals(array('type' => 'string', 'index' => 'not_analyzed'), $localeProperties);
    }

    /*
    Get a record as an Elastic document and check values
     */
    public function testGetElasticaDocumentWithLocale()
    {
        if (!class_exists('Translatable')) {
            $this->markTestSkipped('Translatable not installed');
        }

        $this->markTestSkipped('Translatable not installed');

        // Need to get something from the SiteTree
        $manyTypes = $this->objFromFixture('ManyTypesPage', 'manytypes0001');
        $result = $manyTypes->getFieldValuesAsArray();
        $doc = $manyTypes->getElasticaDocument()->getData();

        $expected = array(
            'BooleanField' => '1',
            'CurrencyField' => '100.25',
            'DateField' => '2014-04-15',
            'DecimalField' => '0',
            'EnumField' => '',
            'HTMLTextField' => '',
            'HTMLVarcharField' => 'This is some *HTML*varchar field',
            'IntField' => '677',
            'PercentageField' => '27',
            'SS_DatetimeField' => '2014-10-18 08:24:00',
            'TextField' => 'This is a text field',
            'TimeField' => '17:48:18',
            'Title' => 'Many Types Page',
            'Content' => 'Many types of fields',
            'IsInSiteTree' => '1',
            'Link' => 'http://moduletest.silverstripe/many-types-page/',
            'Locale' => 'en_US',
        );
        $this->assertEquals($expected, $doc);
    }
}
