<?php

namespace SilverStripe\Elastica;

use Elastica\Client;
use Elastica\Query;
use Elastica\Search;
use SilverStripe\Elastica\ElasticaUtil;

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
	 * Array of highlighted fields, e.g. Title, Title.standard.  If this is empty then the
	 * ShowHighlight field of SearchableField is used to determine which fields to highlight
	 * @var array
	 */
	private $highlightedFields = array();


	/**
	 * The number of documents to index currently for this locale
	 * @var integer The number of documents left to index
	 */
	private $nDocumentsToIndexForLocale = 0;


	/*
	Set the highlight fields for subsequent searches
	 */
	public function setHighlightedFields($newHighlightedFields) {
		$this->highlightedFields = $newHighlightedFields;
	}


	/*
	Enable this to allow test classes not to be ignored when indexing
	 */
	public $test_mode = false;


	/**
	 * @param \Elastica\Client $client
	 * @param string $newIndexName Name of the new index
	 */
	public function __construct(Client $client, $newIndexName) {
		$this->client = $client;
		$this->indexName = $newIndexName;
		$this->locale = \i18n::default_locale();
	}


	public function setTestMode($newTestMode) {
		$this->test_mode = $newTestMode;
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

	public function getIndexName() {
		return $this->indexName;
	}

	private function getLocaleIndexName() {
		$name = $this->indexName . '-' . $this->locale;
		$name = strtolower($name);
		$name = str_replace('-', '_', $name);
		return $name;
	}


	/**
	 * Performs a search query and returns a result list.
	 *
	 * @param \Elastica\Query|string|array $query
	 * @param string|array $types List of comma separated SilverStripe classes to search, or blank for all
	 * @return \Elastica\ResultList
	 */
	public function search($query, $types = '') {
		$query = Query::create($query); // may be a string
		if(is_string($types)) {
			$types = explode(',', $types);
		}

		$data = $query->toArray();
		if(isset($data['query']['more_like_this'])) {
			$query->MoreLikeThis = true;
		} else {
			$query->MoreLikeThis = false;
		}

		$search = new Search(new Client());

		if($this->test_mode) {
			$search->setOption('search_type', Search::OPTION_SEARCH_TYPE_DFS_QUERY_THEN_FETCH);
		}



		$search->addIndex($this->getLocaleIndexName());

		// If the query is a 'more like this' we can get the terms used for searching by performing
		// an extra query, in this case a query validation with explain and rewrite turned on
		if($query->MoreLikeThis) {
			$path = $search->getPath();

			$termData = array();
			$termData['query'] = $data['query'];

			$path = str_replace('_search', '_validate/query', $path);
			$params = array('explain' => true, 'rewrite' => true);
			if($this->test_mode) {
				$params['search_type'] = Search::OPTION_SEARCH_TYPE_DFS_QUERY_THEN_FETCH;
			}

			$response = $this->getClient()->request(
				$path,
				\Elastica\Request::GET,
				$termData,
				$params
			);

			$rData = $response->getData();
			$terms = null; // keep in scope

			if(isset($rData['explanations'])) {
				$explanation = $rData['explanations'][0]['explanation'];
				$terms = ElasticaUtil::parseSuggestionExplanation($explanation);
			}

			if(isset($terms)) {
				$this->MoreLikeThisTerms = $terms;
			}
		}

		if(!empty($types)) {
			foreach($types as $type) {
				$search->addType($type);
			}
		}

		$highlightsCfg = \Config::inst()->get('Elastica', 'Highlights');
		$preTags = $highlightsCfg['PreTags'];
		$postTags = $highlightsCfg['PostTags'];
		$fragmentSize = $highlightsCfg['Phrase']['FragmentSize'];
		$nFragments = $highlightsCfg['Phrase']['NumberOfFragments'];

		$stringFields = $this->highlightedFields;
		$usingProvidedHighlightFields = true;

		if(sizeof($stringFields) == 0) {
			$filter = array('Type' => 'string', 'ShowHighlights' => true);
			$stringFields = \SearchableField::get()->filter($filter)->map('Name')->toArray();
			$usingProvidedHighlightFields = false;
		}


		$highlightFields = array();
		foreach($stringFields as $name) {
			// Add the stemmed and the unstemmed for now
			$fieldName = $name;
			if(!$usingProvidedHighlightFields) {
				$fieldName .= '.standard';
			}
			$highlightFields[$fieldName] = array(
				'fragment_size' => $fragmentSize,
				'number_of_fragments' => $nFragments,
				'no_match_size'=> 200
			);
		}

		$highlights = array(
			'pre_tags' => array($preTags),
			'post_tags' => array($postTags),
			'fields' => $highlightFields
		);

		if($query->MoreLikeThis) {
			$termsMatchingQuery = array();
			foreach($this->MoreLikeThisTerms as $field => $terms) {
				$termQuery = array('multi_match' => array(
					'query' => implode(' ', $terms),
					'type' => 'most_fields',
					'fields' => array($field)
				));
				$termsMatchingQuery[$field] = array('highlight_query' => $termQuery);
			}

			$highlights['fields'] = $termsMatchingQuery;
		}

		$query->setHighlight($highlights);
		$search->addIndex($this->getLocaleIndexName());
		if(!empty($types)) {
			foreach($types as $type) {
				$search->addType($type);
			}
		}

		$params = $search->getOptions();
		$searchResults = $search->search($query, $params);
		if(isset($this->MoreLikeThisTerms)) {
			$searchResults->MoreLikeThisTerms = $this->MoreLikeThisTerms;
		}

		return $searchResults;
	}


	/**
	 * Ensure that the index is present
	 */
	protected function ensureIndex() {
		$index = $this->getIndex();
		if(!$index->exists()) {
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
		$mapping = $type->getMapping();
		if($mapping == array()) {
			$this->ensureIndex();
			$mapping = $record->getElasticaMapping();
			$type->setMapping($mapping);
			$mapping = $mapping->toArray();
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

		if($this->buffered) {
			if(array_key_exists($typeName, $this->buffer)) {
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


	public function listIndexes($trace) {
		$command = "curl 'localhost:9200/_cat/indices?v'";
		exec($command, $op);
		ElasticaUtil::message("\n++++ $trace ++++\n");
		ElasticaUtil::message(print_r($op, 1));
		ElasticaUtil::message("++++ /{$trace} ++++\n\n");
		return $op;
	}


	/**
	 * Ends the current bulk index operation and indexes the buffered documents.
	 */
	public function endBulkIndex() {
		$index = $this->getIndex();
		foreach($this->buffer as $type => $documents) {
			$amount = 0;

			foreach(array_keys($this->buffer) as $key) {
				$amount += sizeof($this->buffer[$key]);
			}
			$index->getType($type)->addDocuments($documents);
			$index->refresh();

			ElasticaUtil::message("\tAdding $amount documents to the index\n");
			if(isset($this->StartTime)) {
				$elapsed = microtime(true) - $this->StartTime;
				$timePerDoc = ($elapsed) / ($this->nDocumentsIndexed);
				$documentsRemaining = $this->nDocumentsToIndexForLocale - $this->nDocumentsIndexed;
				$eta = ($documentsRemaining) * $timePerDoc;
				$hours = (int)($eta / 3600);
				$minutes = (int)(($eta - $hours * 3600) / 60);
				$seconds = (int)(0.5 + $eta - $minutes * 60 - $hours * 3600);
				$etaHR = "{$hours}h {$minutes}m {$seconds}s";
				ElasticaUtil::message("ETA to completion of indexing $this->locale ($documentsRemaining documents): $etaHR");
			}
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
		if($index->exists()) {
			$index->delete();
		}
		$this->createIndex();

		foreach($this->getIndexedClasses() as $class) {
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
		foreach($records as $record) {
			if($record->showRecordInSearch()) {
				$this->index($record);
			}
		}
	}


	/**
	 * Get a List of all records by class. Get the "Live data" If the class has the "Versioned" extension
	 *
	 * @param string $class Class Name
	 * @param  int $pageSize Optional page size, only a max of this number of records returned
	 * @param  int $page Page number to return
	 * @return \DataObject[] $records
	 */
	protected function recordsByClassConsiderVersioned($class, $pageSize = 0, $page = 0) {
		$offset = $page * $pageSize;

		if($class::has_extension("Versioned")) {
			if($pageSize > 0) {
				$records = \Versioned::get_by_stage($class, 'Live')->limit($pageSize, $offset);
			} else {
				$records = \Versioned::get_by_stage($class, 'Live');
			}
		} else {
			if($pageSize > 0) {
				$records = $class::get()->limit($pageSize, $offset);
			} else {
				$records = $class::get();
			}

		}
		return $records;
	}


	/**
	 * Refresh the records of a given class within the search index
	 *
	 * @param string $class Class Name
	 */
	protected function refreshClass($class) {
		$nRecords = $this->recordsByClassConsiderVersioned($class)->count();
		$batchSize = 500;
		$pages = $nRecords / $batchSize + 1;

		for($i = 0; $i < $pages; $i++) {
			$this->startBulkIndex();
			$pagedRecords = $this->recordsByClassConsiderVersioned($class, $batchSize, $i);
			$this->nDocumentsIndexed += $pagedRecords->count();
			$batch = $pagedRecords->toArray();
			$this->refreshRecords($batch);
			$this->endBulkIndex();
		}
	}


	/**
	 * Re-indexes each record in the index.
	 */
	public function refresh() {
		$this->StartTime = microtime(true);

		$classes = $this->getIndexedClasses();

		//Count the number of documents for this locale
		$amount = 0;
		echo "CURRENT LOCALE:" . $this->locale;
		foreach($classes as $class) {
			$amount += $this->recordsByClassConsiderVersioned($class)->count();
		}

		$this->nDocumentsToIndexForLocale = $amount;
		$this->nDocumentsIndexed = 0;

		foreach($this->getIndexedClasses() as $classname) {
			ElasticaUtil::message("Indexing class $classname");

			$inSiteTree = $classname === 'SiteTree' ? true : false;
			if(isset(self::$site_tree_classes[$classname])) {
				$inSiteTree = self::$site_tree_classes[$classname];
			} else {
				$class = new \ReflectionClass($classname);
				while($class = $class->getParentClass()) {
					$parentClass = $class->getName();
					if($parentClass == 'SiteTree') {
						$inSiteTree = true;
						break;
					}
				}
				self::$site_tree_classes[$classname] = $inSiteTree;
			}

			if($inSiteTree) {
				// this prevents the same item being indexed twice due to class inheritance
				if($classname === 'SiteTree') {
					$this->refreshClass($classname);
				}
			// Data objects
			} else {
				$this->refreshClass($classname);
			}

		}

		echo "Completed indexing documents for locale $this->locale\n";

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
		$index = $this->getIndex();
		$settings = $this->getIndexSettingsForCurrentLocale()->generateConfig();
		$index->create($settings, true);
	}


	/**
	 * Get the index settings for the current locale
	 * @return IndexSettings index settings for the current locale
	 */
	public function getIndexSettingsForCurrentLocale() {
		$result = null;
		$indexSettings = \Config::inst()->get('Elastica', 'indexsettings');
		if(isset($indexSettings[$this->locale])) {
			$settingsClassName = $indexSettings[$this->locale];
			\Injector::inst()->create($settingsClassName);
			$this->fail('Test should have failed, no index settings provided for locale');
		} else {
			throw new \Exception('ERROR: No index settings are provided for locale ' . $this->locale . "\n");

		}
		return $result;
	}


	/**
	 * Gets the classes which are indexed (i.e. have the extension applied).
	 *
	 * @return array
	 */
	public function getIndexedClasses() {
		$classes = array();

		$whitelist = array('SearchableTestPage', 'SearchableTestFatherPage', 'SearchableTestGrandFatherPage',
			'FlickrPhotoTO', 'FlickrTagTO', 'FlickrPhotoTO', 'FlickrAuthorTO', 'FlickrSetTO');

		foreach(\ClassInfo::subclassesFor('DataObject') as $candidate) {
			$instance = singleton($candidate);

			$interfaces = class_implements($candidate);
			// Only allow test classes in testing mode
			if(isset($interfaces['TestOnly'])) {
				if(in_array($candidate, $whitelist)) {
					if(!$this->test_mode) {
						continue;
					}
				} else {
					// If it's not in the test whitelist we definitely do not want to know
					continue;
				}
			}

			if($instance->hasExtension('SilverStripe\\Elastica\\Searchable')) {
				$classes[] = $candidate;
			}
		}

		return $classes;
	}


	/**
	 * Get the number of indexing requests made.  Used for testing bulk indexing
	 * @return integer indexing request counter
	 */
	public function getIndexingRequestCtr() {
		return self::$indexing_request_ctr;
	}


	/**
	 * Get the term vectors in the index for the provided  Searchable is_object
	 * @param  Searchable $searchable An object that implements Searchable
	 * @return array             array of field name to terms indexed
	 */
	public function getTermVectors($searchable) {
		$params = array();

		$fieldMappings = $searchable->getElasticaMapping()->getProperties();
		$fields = array_keys($fieldMappings);
		$allFields = array();
		foreach($fields as $field) {
			array_push($allFields, $field);

			$mapping = $fieldMappings[$field];


			if(isset($mapping['fields'])) {
				$subFields = array_keys($mapping['fields']);
				foreach($subFields as $subField) {
					$name = $field . '.' . $subField;
					array_push($allFields, $name);
				}
			}
		}
		sort($allFields);
		$data = array(
			'fields' => $allFields,
			'offsets' => true,
			'payloads' => true,
			'positions' => true,
			'term_statistics' => true,
			'field_statistics' => true
		);

		$path = $this->getIndex()->getName() . '/' . $searchable->ClassName . '/' . $searchable->ID . '/_termvector';
		$response = $this->getClient()->request(
				$path,
				\Elastica\Request::GET,
				$data,
				$params
		);

		$data = $response->getData();
		return $data['term_vectors'];
	}
}
