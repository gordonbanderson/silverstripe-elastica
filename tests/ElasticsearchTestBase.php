<?php

use SilverStripe\Elastica\ElasticaUtil;
use SilverStripe\Elastica\ReindexTask;

class ElasticsearchBaseTest extends SapphireTest
{
    public static $ignoreFixtureFileFor = array();

    protected $extraDataObjects = array(
        'SearchableTestPage', 'FlickrPhotoTO', 'FlickrAuthorTO', 'FlickrSetTO', 'FlickrTagTO',
        'SearchableTestFatherPage', 'SearchableTestGrandFatherPage', 'AutoCompleteOption',
    );

    public function setUpOnce()
    {
        ElasticaUtil::setPrinterOutput(false);

        // add Searchable extension where appropriate
        FlickrSetTO::add_extension('SilverStripe\Elastica\Searchable');
        FlickrPhotoTO::add_extension('SilverStripe\Elastica\Searchable');
        FlickrTagTO::add_extension('SilverStripe\Elastica\Searchable');
        FlickrAuthorTO::add_extension('SilverStripe\Elastica\Searchable');
        SearchableTestPage::add_extension('SilverStripe\Elastica\Searchable');

        $config = Config::inst();
        $config->remove('Injector', 'SilverStripe\Elastica\ElasticaService');
        $constructor = array('constructor' => array('%$Elastica\Client', 'elastica_ss_module_test'));
        $config->update('Injector', 'SilverStripe\Elastica\ElasticaService', $constructor);
        parent::setUpOnce();
    }

    public function setUp()
    {
        // no need to index here as it's done when fixtures are loaded during setup method
        $cache = SS_Cache::factory('elasticsearch');
        $cache->clean(Zend_Cache::CLEANING_MODE_ALL);
        SS_Cache::set_cache_lifetime('elasticsearch', 3600, 1000);

        // this needs to be called in order to create the list of searchable
        // classes and fields that are available.  Simulates part of a build
        $classes = array('SearchableTestPage', 'SiteTree', 'Page', 'FlickrPhotoTO', 'FlickrSetTO',
            'FlickrTagTO', 'FlickrAuthorTO', );
        $this->requireDefaultRecordsFrom = $classes;

        // clear the index
        $this->service = Injector::inst()->create('SilverStripe\Elastica\ElasticaService');
        $this->service->setTestMode(true);

        $elasticException = false;

        try {
            // A previous test may have deleted the index and then failed, so check for this
            if (!$this->service->getIndex()->exists()) {
                $this->service->getIndex()->create();
            }
            $this->service->reset();
            // FIXME - use request getVar instead?
            $_GET['progress'] = 20;
            // load fixtures

            $orig_fixture_file = static::$fixture_file;

            foreach (static::$ignoreFixtureFileFor as $testPattern) {
                $pattern = '/'.$testPattern.'/';
                if (preg_match($pattern, $this->getName())) {
                    static::$fixture_file = null;
                }
            }
        } catch (Exception $e) {
            $elasticException = true;
        }

        // this needs to run otherwise nested injector errors show up
        parent::setUp();

        if ($elasticException) {
            $this->fail('T1 An error has occurred trying to contact Elasticsearch server');
        }

        try {
            static::$fixture_file = $orig_fixture_file;

            $this->publishSiteTree();
            $this->service->reset();

            // index loaded fixtures
            $task = new ReindexTask($this->service);
            // null request is fine as no parameters used

            $task->run(null);
        } catch (Exception $e) {
            $elasticException = true;
        }

        if ($elasticException) {
            $this->fail('T2 An error has occurred trying to contact Elasticsearch server');
        }
    }

    protected function devBuild()
    {
        $task = new \BuildTask();
        // null request is fine as no parameters used
        $task->run(null);
    }

    private function publishSiteTree()
    {
        foreach (SiteTree::get()->getIterator() as $page) {
            // temporarily disable Elasticsearch indexing, it will be done in a batch
            $page->IndexingOff = true;
            $page->publish('Stage', 'Live');
        }
    }

    public function generateAssertionsFromArray($toAssert)
    {
        echo '$expected = array('."\n";
        foreach ($toAssert as $key => $value) {
            $escValue = str_replace("'", '\\\'', $value);
            echo "'$key' => '$escValue',\n";
        }
        echo ");\n";
        echo '$this->assertEquals($expected, $somevar);'."\n";
    }

    public function generateAssertionsFromArray1D($toAssert)
    {
        echo '$expected = array('."\n";
        foreach ($toAssert as $key => $value) {
            $escValue = str_replace("'", '\\\'', $value);
            echo "'$escValue',";
        }
        echo ");\n";
        echo '$this->assertEquals($expected, $somevar);'."\n";
    }

    public function generateAssertionsFromArrayRecurse($toAssert)
    {
        echo '$expected = ';
        $this->recurseArrayAssertion($toAssert, 1, 'FIXME');
        echo '$this->assertEquals($expected, $somevar);'."\n";
    }

    private function recurseArrayAssertion($toAssert, $depth, $parentKey)
    {
        $prefix = str_repeat("\t", $depth);
        echo "\t{$prefix}'$parentKey' => array(\n";
        $ctr = 0;
        $len = sizeof(array_keys($toAssert));
        foreach ($toAssert as $key => $value) {
            if (is_array($value)) {
                $this->recurseArrayAssertion($value, $depth + 1, $key);
            } else {
                $escValue = str_replace("'", '\\\'', $value);
                $comma = ',';
                if ($ctr == $len - 1) {
                    $comma = '';
                }
                echo "\t\t$prefix'$key' => '$escValue'$comma\n";
            }

            ++$ctr;
        }
        echo "\t$prefix),\n";
    }

    /*
    Helper methods for testing CMS fields
     */
    public function checkTabExists($fields, $tabName)
    {
        $tab = $fields->findOrMakeTab("Root.{$tabName}");
        $actualTabName = $tab->getName();
        $splits = explode('.', $tabName);
        $size = sizeof($splits);
        $nameToCheck = end($splits);
        $this->assertEquals($actualTabName, $nameToCheck);
        if ($size == 1) {
            $this->assertEquals("Root_${tabName}", $tab->id());
        } else {
            $expected = "Root_{$splits[0]}_set_{$splits[1]}";
            $this->assertEquals($expected, $tab->id());
        }

        return $tab;
    }

    public function checkFieldExists($tab, $fieldName)
    {
        $fields = $tab->Fields();
        $field = $tab->fieldByName($fieldName);
        $this->assertTrue($field != null);

        return $field;
    }

    /**
     * From https://jtreminio.com/2013/03/unit-testing-tutorial-part-3-testing-protected-private-methods-coverage-reports-and-crap/
     * Call protected/private method of a class.
     *
     * @param object &$object    Instantiated object that we will run method on.
     * @param string $methodName Method name to call
     * @param array  $parameters Array of parameters to pass into method.
     *
     * @return mixed Method return.
     */
    public function invokeMethod(&$object, $methodName, array $parameters = array())
    {
        $reflection = new \ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $parameters);
    }

    public function checkNumberOfIndexedDocuments($expectedAmount)
    {
        $index = $this->service->getIndex();
        $status = $index->getStatus()->getData();

        $numberDocsInIndex = -1; // flag value for not yet indexed

        if (isset($status['indices']['elastica_ss_module_test_en_us']['docs'])) {
            $numberDocsInIndex = $status['indices']['elastica_ss_module_test_en_us']['docs']['num_docs'];
        } else {
            $numberDocsInIndex = 0;
        }

        $this->assertEquals($expectedAmount, $numberDocsInIndex);
    }

    /*
    Get the number of documents in an index.  It is assumed the index exists, if not the test will fail
     */
    public function getNumberOfIndexedDocuments()
    {
        $index = $this->service->getIndex();
        $status = $index->getStatus()->getData();

        $numberDocsInIndex = -1; // flag value for not yet indexed
        if (isset($status['indices']['elastica_ss_module_test_en_us']['docs'])) {
            $numberDocsInIndex = $status['indices']['elastica_ss_module_test_en_us']['docs']['num_docs'];
        }

        $this->assertGreaterThan(-1, $numberDocsInIndex);

        return $numberDocsInIndex;
    }
}
