<?php

namespace SilverStripe\Elastica;

use Elastica\Client;
use Elastica\Query;
use Elastica\Search;

/**
 * A service used to interact with elastic search.
 */
class ElasticaService {

	/**
	 * @var \Elastica\Document[]
	 */
	protected $buffer = array();

	/**
	 * @var bool controls whether indexing operations are buffered or not
	 */
	protected $buffered = false;

	/**
	 * @var \Elastica\Client Elastica Client object
	 */
	private $client;

	/**
	 * @var string index name
	 */
	private $indexName;

	/**
	 * The code of the locale being indexed or searched
	 * @var string e.g. th_TH, en_US
	 */
	private $locale;

	/**
	 * Mapping of DataObject ClassName and whether it is in the SiteTree or not
	 * @var array $site_tree_classes;
	 */
	private static $site_tree_classes = array();

	/**
	 * Counter used to for testing, records indexing requests
	 * @var integer
	 */
	public static $indexing_request_ctr = 0;

	/**
	 * @param \Elastica\Client $client
	 * @param string $index
	 */
	public function __construct(Client $client, $newIndexName) {
		$this->client = $client;
		$this->indexName = $newIndexName;
		$this->locale = \i18n::default_locale();
	}

	/**
	 * @return \Elastica\Client
	 */
	public function getClient() {
		return $this->client;
	}

	/**
	 * @return \Elastica\Index
	 */
	public function getIndex() {
		$index = $this->getClient()->getIndex($this->getLocaleIndexName());
		return $index;
	}


	public function setLocale($newLocale) {
		$this->locale = $newLocale;
	}

	private function getLocaleIndexName() {
		$name = $this->indexName.'-'.$this->locale;
		$name = strtolower($name);
		$name = str_replace('-', '_', $name);
		return $name;
	}

	/**
	 * Performs a search query and returns a result list.
	 *
	 * @param \Elastica\Query|string|array $query
	 * @param array $types List of comma separated SilverStripe classes to search, or blank for all
	 * @return ResultList
	 */
	public function search($searchterms, $types = '') {
		$query = Query::create($searchterms);

		$highlightsCfg = \Config::inst()->get('Elastica', 'Highlights');
		$preTags = $highlightsCfg['PreTags'];
		$postTags = $highlightsCfg['PostTags'];
		$fragmentSize = $highlightsCfg['Phrase']['FragmentSize'];
		$nFragments = $highlightsCfg['Phrase']['NumberOfFragments'];

		$query->setHighlight(array(
			'pre_tags' => array($preTags),
			'post_tags' => array($postTags),
			'fields' => array(
				"*" => json_decode('{}'),
				'phrase' => array(
					'fragment_size' => $fragmentSize,
					'number_of_fragments' => $nFragments,
				),
			),
		));

		$search = new Search(new Client());
		$search->addIndex($this->getLocaleIndexName());
        if ($types) {
        	$search->addType($types);
        }

        return $search->search($query);
	}


	/**
	 * Ensure that the index is present
	 */
	protected function ensureIndex() {
		$index = $this->getIndex();
		if (!$index->exists()) {
			$this->createIndex();
		}
	}


	/**
	 * Ensure that there is a mapping present
	 *
	 * @param \Elastica\Type Type object
	 * @param \DataObject Data record
	 * @return \Elastica\Mapping Mapping object
	 */
	protected function ensureMapping(\Elastica\Type $type, \DataObject $record) {
		try {
			$mapping = $type->getMapping();
		}
		catch(\Elastica\Exception\ResponseException $e) {
			$this->ensureIndex();
			$mapping = $record->getElasticaMapping();
			$type->setMapping($mapping);
		}
		return $mapping;
	}


	/**
	 * Either creates or updates a record in the index.
	 *
	 * @param Searchable $record
	 */
	public function index($record) {
		$document = $record->getElasticaDocument();
		$typeName = $record->getElasticaType();

		if ($this->buffered) {
			if (array_key_exists($typeName, $this->buffer)) {
				$this->buffer[$typeName][] = $document;
			} else {
				$this->buffer[$typeName] = array($document);
			}
		} else {
			$index = $this->getIndex();
			$type = $index->getType($typeName);
			$this->ensureMapping($type, $record);
			$type->addDocument($document);
			$index->refresh();
			self::$indexing_request_ctr++;
		}
	}


	/**
	 * Begins a bulk indexing operation where documents are buffered rather than
	 * indexed immediately.
	 */
	public function startBulkIndex() {
		$index = $this->getIndex();
		$this->buffered = true;
	}

/*
	public function listIndexes($trace) {
		$command = "curl 'localhost:9200/_cat/indices?v'";
        exec($command,$op);
        echo "\n++++ $trace ++++\n";
        print_r($op);
        echo "++++ /{$trace} ++++\n\n";
	}
*/

	/**
	 * Ends the current bulk index operation and indexes the buffered documents.
	 */
	public function endBulkIndex() {
		$index = $this->getIndex();
		foreach ($this->buffer as $type => $documents) {
			$index->getType($type)->addDocuments($documents);
			$index->refresh();
			self::$indexing_request_ctr++;
		}

		$this->buffered = false;
		$this->buffer = array();
	}


	/**
	 * Deletes a record from the index.
	 *
	 * @param Searchable $record
	 */
	public function remove($record) {
		$index = $this->getIndex();
		$type = $index->getType($record->getElasticaType());
		$type->deleteDocument($record->getElasticaDocument());
		$index->refresh();
	}


	/**
	 * Creates the index and the type mappings.
	 */
	public function define() {
		$index = $this->getIndex();

		# Recreate the index
		if ($index->exists()) {
			$index->delete();
		}
		$this->createIndex();

		foreach ($this->getIndexedClasses() as $class) {
			/** @var $sng Searchable */
			$sng = singleton($class);
			$mapping = $sng->getElasticaMapping();
			$mapping->setType($index->getType($sng->getElasticaType()));
			$mapping->send();
		}
	}


	/**
	 * Refresh a list of records in the index
	 *
	 * @param \DataList $records
	 */
	protected function refreshRecords($records) {
		foreach ($records as $record) {
			//echo 'Indexing '.$record->ClassName.' ('.$record->ID.")\n";
			if ($record->showRecordInSearch()) {
				$this->index($record);
			}
		}
	}


	/**
	 * Get a List of all records by class. Get the "Live data" If the class has the "Versioned" extension
	 *
	 * @param string $class Class Name
	 * @return \DataObject[] $records
	 */
	protected function recordsByClassConsiderVersioned($class) {
		if ($class::has_extension("Versioned")) {
			$records = \Versioned::get_by_stage($class, 'Live');
		} else {
			$records = $class::get();
		}
		return $records->toArray();
	}


	/**
	 * Refresh the records of a given class within the search index
	 *
	 * @param string $class Class Name
	 */
	protected function refreshClass($class) {
		$records = $this->recordsByClassConsiderVersioned($class);
		$this->refreshRecords($records);
	}


	/**
	 * Re-indexes each record in the index.
	 */
	public function refresh() {
		$index = $this->getIndex();
		$this->startBulkIndex();

		echo "REFRESH T1\n";

		echo "INDEXED CLASSES\n";
		print_r($this->getIndexedClasses());

		foreach ($this->getIndexedClasses() as $classname) {
			$inSiteTree = false;
			if (isset(self::$site_tree_classes[$classname])) {
				$inSiteTree = self::$site_tree_classes[$classname];
			} else {
				$class = new \ReflectionClass($classname);
				while ($class = $class->getParentClass()) {
				    $parentClass = $class->getName();
				    if ($parentClass == 'SiteTree') {
				    	$inSiteTree = true;
				    	break;
				    }
				}
				self::$site_tree_classes[$classname] = $inSiteTree;
			}

			//$this->refreshClass($classname);


			if ($inSiteTree) {
				// this prevents the same item being indexed twice due to class inheritance
				if ($classname === 'SiteTree') {
					$this->refreshClass($classname);
				}
			// Data objects
			} else {
				$this->refreshClass($classname);
			}

		}

		$this->endBulkIndex();
	}


	/**
	 * Reset the current index
	 */
	public function reset() {
		$index = $this->getIndex();
		$index->delete();
		$this->createIndex();
	}


	private function createIndex() {
		$indexSettings = \Config::inst()->get('Elastica', 'indexsettings');

		$index = $this->getIndex();
		if (isset($indexSettings[$this->locale])) {
			$settingsClassName = $indexSettings[$this->locale];
			$settingsInstance = \Injector::inst()->create($settingsClassName);
			$settings = $settingsInstance->generateConfig();
			$index->create($settings, true);
		} else {
			throw new \Exception('ERROR: No index settings are provided for locale '.$this->locale."\n");

		}
	}


	/**
	 * Gets the classes which are indexed (i.e. have the extension applied).
	 *
	 * @return array
	 */
	public function getIndexedClasses() {
		$classes = array();

		//FIXME - make this configurable
		$whitelist = array('SearchableTestPage','FlickrPhotoTO','FlickrTagTO','FlickrPhotoTO','FlickrAuthorTO','FlickrSetTO');

		foreach (\ClassInfo::subclassesFor('DataObject') as $candidate) {
			$instance = singleton($candidate);

			$interfaces = class_implements($candidate);

			if (isset($interfaces['TestOnly']) && !in_array($candidate, $whitelist)) {
				continue;
			}

			if ($instance->hasExtension('SilverStripe\\Elastica\\Searchable')) {
				$classes[] = $candidate;
			}
		}

		return $classes;
	}


	/**
	 * Get the number of indexing requests made.  Used for testing bulk indexing
	 * @return [type] [description]
	 */
	public function getIndexingRequestCtr() {
		return self::$indexing_request_ctr;
	}

}
