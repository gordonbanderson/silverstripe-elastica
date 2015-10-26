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
	public function search($query, $types = '', $debugTerms = false) {
		$query = Query::create($query); // may be a string

        $data = $query->toArray();
		if (isset($data['query']['more_like_this'])) {
			$query->MoreLikeThis = true;
		} else {
			$query->MoreLikeThis = false;
		}


		$search = new Search(new Client());
		$search->addIndex($this->getLocaleIndexName());

		// If the query is a 'more like this' we can get the terms used for searching
		if ($query->MoreLikeThis) {
			$termsQuery = clone $query;
			$path = $search->getPath();
	        $params = $search->getOptions();

	        $termData = array();
	        $termData['query'] = $data['query'];



	        $path = str_replace('_search', '_validate/query', $path);
	        $params = array('explain' => true, 'rewrite' => true);



	        $response = $this->getClient()->request(
	            $path,
	            \Elastica\Request::GET,
	            $termData,
	            $params
	        );

			$r = $response->getData();

			if (isset($r['explanations'])) {
				$explanation = $r['explanations'][0]['explanation'];

				if (substr($explanation,0, 2) == '((') {
					$explanation = explode('-ConstantScore', $explanation)[0];

			        $bracketPos = strpos($explanation, ')~');
			       	$explanation = substr($explanation, 2, $bracketPos-2);

			        //Field name(s) => terms
			        $terms = array();
			        $splits = explode(' ', $explanation);
			        foreach ($splits as $fieldAndTerm) {
			        	$splits = explode(':', $fieldAndTerm);
			        	$fieldname = $splits[0];
			        	$term = $splits[1];

			        	if (!isset($terms[$fieldname])) {
			        		$terms[$fieldname] = array();
			        	}

			        	array_push($terms[$fieldname], $term);
			        }
				}
			}

			$this->MoreLikeThisTerms = $terms;
		}


		//If the query is a more like this, perform a validation request to get the terms used

        if ($types) {
        	$search->addType($types);
        }

        $path = $search->getPath();
        $params = $search->getOptions();


		$highlightsCfg = \Config::inst()->get('Elastica', 'Highlights');
		$preTags = $highlightsCfg['PreTags'];
		$postTags = $highlightsCfg['PostTags'];
		$fragmentSize = $highlightsCfg['Phrase']['FragmentSize'];
		$nFragments = $highlightsCfg['Phrase']['NumberOfFragments'];

		$highlights = array(
			'pre_tags' => array($preTags),
			'post_tags' => array($postTags),
			'fields' => array(
				"*" => json_decode('{}'),
				'phrase' => array(
					'fragment_size' => $fragmentSize,
					'number_of_fragments' => $nFragments,
				),
			),
		);


		if ($query->MoreLikeThis) {
			$termsMatchingQuery = array();
			foreach ($this->MoreLikeThisTerms as $field => $terms) {
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



		$search = new Search(new Client());
		$search->addIndex($this->getLocaleIndexName());
        if ($types) {
        	$search->addType($types);
        }

        $path = $search->getPath();
        $params = $search->getOptions();



		$searchResults = $search->search($query);

		if (isset($this->MoreLikeThisTerms)) {
			$searchResults->MoreLikeThisTerms = $this->MoreLikeThisTerms;
		}


        return $searchResults;
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


	public function listIndexes($trace) {
		$command = "curl 'localhost:9200/_cat/indices?v'";
        exec($command,$op);
        ElasticaUtil::message("\n++++ $trace ++++\n");
        ElasticaUtil::message(print_r($op,1));
        ElasticaUtil::message("++++ /{$trace} ++++\n\n");
	}


	/**
	 * Ends the current bulk index operation and indexes the buffered documents.
	 */
	public function endBulkIndex() {
		$index = $this->getIndex();
		foreach ($this->buffer as $type => $documents) {
			$amount = 0;

			foreach (array_keys($this->buffer) as $key) {
				$amount += sizeof($this->buffer[$key]);
			}
			$index->getType($type)->addDocuments($documents);
			$index->refresh();

			ElasticaUtil::message("\tAdding $amount documents to the index\n");
			if (isset($this->StartTime)) {
				$elapsed = microtime(true) - $this->StartTime;
				$timePerDoc = ($elapsed)/($this->nDocumentsIndexed);
				$documentsRemaining = $this->nDocumentsToIndexForLocale - $this->nDocumentsIndexed;
				$eta = ($documentsRemaining)*$timePerDoc;
				$hours = (int)($eta/3600);
				$minutes = (int)(($eta-$hours*3600)/60);
				$seconds = (int)(0.5+$eta-$minutes*60-$hours*3600);
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
	 * @param  int $pageSize Optional page size, only a max of this number of records returned
	 * @param  int $page Page number to return
	 * @return \DataObject[] $records
	 */
	protected function recordsByClassConsiderVersioned($class, $pageSize = 0, $page = 0) {
		$offset = $page*$pageSize;

		if ($class::has_extension("Versioned")) {
			if ($pageSize >0) {
				$records = \Versioned::get_by_stage($class, 'Live')->limit($pageSize, $offset);
			} else {
				$records = \Versioned::get_by_stage($class, 'Live');
			}
		} else {
			if ($pageSize >0) {
				$records = $class::get()->limit($pageSize,$offset);
			} else {
				$records = $class::get();
			}

		}
		return $records;
	}


	protected function recordsByClassConsiderVersionedToArray($class) {
		return $this->recordsByClassConsiderVersioned($class)->toArray();;
	}


	/**
	 * Refresh the records of a given class within the search index
	 *
	 * @param string $class Class Name
	 */
	protected function refreshClass($class) {
		$nRecords = $this->recordsByClassConsiderVersioned($class)->count();
		$batchSize = 500;
		$pages = $nRecords/$batchSize + 1;
		$processing = true;



		for ($i=0; $i < $pages; $i++) {
			$this->startBulkIndex();
			$pagedRecords = $this->recordsByClassConsiderVersioned($class,$batchSize, $i);
			$this->nDocumentsIndexed += $pagedRecords->count();
			$batch = $pagedRecords->toArray();
			$this->refreshRecords($batch);
//			ElasticaUtil::message("Indxed $this->nDocumentsIndexed\n");
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
		foreach ($classes as $class) {
			$amount += $this->recordsByClassConsiderVersioned($class)->count();
		}

		$this->nDocumentsToIndexForLocale = $amount;
		$this->nDocumentsIndexed = 0;
		echo "Indexing $amount documents for locale $this->locale\n";

		$index = $this->getIndex();

		foreach ($this->getIndexedClasses() as $classname) {
			ElasticaUtil::message("Indexing class $classname");

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


/*
curl -XGET 'http://localhost:9200/elasticademo_en_us/FlickrPhoto/3829/_termvector?pretty' -d '{
  "fields" : ["Title", "Title.standard","Description","Description.standard"],
  "offsets" : true,
  "payloads" : true,
  "positions" : true,
  "term_statistics" : true,
  "field_statistics" : true
 */

	public function getTermVectors($searchable) {
		$params = array();

		$fieldMappings = $searchable->getElasticaMapping()->getProperties();


		$fields = array_keys($fieldMappings);
		$allFields = array();
		foreach ($fields as $field) {
			array_push($allFields, $field);

			$mapping = $fieldMappings[$field];


			if (isset($mapping['fields'])) {
				$subFields = array_keys($mapping['fields']);
				foreach ($subFields as $subField) {
					$name = $field.'.'.$subField;
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

		//FlickrPhoto/3829/_termvector
		$path = $this->getIndex()->getName().'/'.$searchable->ClassName.'/'.$searchable->ID.'/_termvector';
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
