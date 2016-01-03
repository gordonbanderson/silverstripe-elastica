<?php

namespace SilverStripe\Elastica;

use Elastica\Document;
use Elastica\Type\Mapping;
use ShortcodeParser;

/**
 * Adds elastic search integration to a data object.
 */
class Searchable extends \DataExtension {

	/**
	 * Counter used to display progress of indexing
	 * @var integer
	 */
	public static $index_ctr = 0;

	/**
	 * Everytime progressInterval divides $index_ctr exactly display progress
	 * @var integer
	 */
	private static $progressInterval = 0;

	public static $mappings = array(
		'Boolean'     => 'boolean',
		'Decimal'     => 'double',
		'Currency'    => 'double',
		'Double'      => 'double',
		'Enum'        => 'string',
		'Float'       => 'float',
		'HTMLText'    => 'string',
		'HTMLVarchar' => 'string',
		'Int'         => 'integer',
		'Text'        => 'string',
		'VarChar'     => 'string',
		'Varchar'     => 'string',
		'Year'        => 'integer',
		'Percentage'  => 'double',
		'Time'  => 'date',
		// The 2 different date types will be stored with different formats
		'Date'        => 'date',
		'SS_Datetime' => 'date',
		'Datetime' => 'date',
		'DBLocale'    => 'string'
	);


	/**
	 * @var ElasticaService associated elastica search service
	 */
	protected $service;


	/**
	 * Array of fields that need HTML parsed
	 * @var array
	 */
	protected $html_fields = array();

	/**
	 * Store a mapping of relationship name to result type
	 */
	protected $relationship_methods = array();


	/**
	 * If importing a large number of items from a fixtures file, or indeed some other source, then
	 * it is quicker to set a flag of value IndexingOff => false.  This has the effect of ensuring
	 * no indexing happens, a request is normally made per fixture when loading.  One can then run
	 * the reindexing teask to bulk index in one HTTP POST request to Elasticsearch
	 *
	 * @var boolean
	 */
	private static $IndexingOff = false;


	/**
	 * @see getElasticaResult
	 * @var \Elastica\Result
	 */
	protected $elastica_result;

	public function __construct(ElasticaService $service) {
		$this->service = $service;
		parent::__construct();
	}


	/**
	 * Get the elasticsearch type name
	 *
	 * @return string
	 */
	public function getElasticaType() {
		return get_class($this->owner);
	}


	/**
	 * If the owner is part of a search result
	 * the raw Elastica search result is returned
	 * if set via setElasticaResult
	 *
	 * @return \Elastica\Result
	 */
	public function getElasticaResult() {
		return $this->elastica_result;
	}


	/**
	 * Set the raw Elastica search result
	 *
	 * @param \Elastica\Result
	 */
	public function setElasticaResult(\Elastica\Result $result) {
		$this->elastica_result = $result;
	}


	/**
	 * Gets an array of elastic field definitions.
	 *
	 * @return array
	 */
	public function getElasticaFields($storeMethodName = false, $recurse = true) {
		$db = $this->owner->db();
		$fields = $this->getAllSearchableFields();
		$result = array();

		foreach($fields as $name => $params) {
			$spec = array();
			$name = str_replace('()', '', $name);

			if(array_key_exists($name, $db)) {
				$class = $db[$name];
				SearchableHelper::assignSpecForStandardFieldType($name, $class, $spec, $this->html_fields, self::$mappings);
			} else {
				// field name is not in the db, it could be a method
				$has_lists = SearchableHelper::getListRelationshipMethods($this->owner);
				$has_ones = $this->owner->has_one();

				// check has_many and many_many relations
				if(isset($has_lists[$name])) {
					// the classes returned by the list method
					$resultType = $has_lists[$name];
					SearchableHelper::assignSpecForRelationship($name, $resultType, $spec, $storeMethodName, $recurse);
				} else if(isset($has_ones[$name])) {
					$resultType = $has_ones[$name];
					SearchableHelper::assignSpecForRelationship($name, $resultType, $spec, $storeMethodName, $recurse);
				}
				// otherwise fall back to string - Enum is one such category
				else {
					$spec["type"] = "string";
				}
			}

			SearchableHelper::addIndexedFields($name, $spec, $this->owner->ClassName);
			$result[$name] = $spec;
		}

		if($this->owner->hasMethod('updateElasticHTMLFields')) {
			$this->html_fields = $this->owner->updateElasticHTMLFields($this->html_fields);
		}

		return $result;
	}



	/**
	 * Get the elasticsearch mapping for the current document/type
	 *
	 * @return \Elastica\Type\Mapping
	 */
	public function getElasticaMapping() {
		$mapping = new Mapping();

		$fields = $this->getElasticaFields(false);

		$localeMapping = array();

		if($this->owner->hasField('Locale')) {
			$localeMapping['type'] = 'string';
			// we wish the locale to be stored as is
			$localeMapping['index'] = 'not_analyzed';
			$fields['Locale'] = $localeMapping;
		}

		// ADD CUSTOM FIELDS HERE THAT ARE INDEXED BY DEFAULT
		// add a mapping to flag whether or not class is in SiteTree
		$fields['IsInSiteTree'] = array('type'=>'boolean');
		$fields['Link'] = array('type' => 'string', 'index' => 'not_analyzed');

		$mapping->setProperties($fields);

		//This concatenates all the fields together into a single field.
		//Initially added for suggestions compatibility, in that searching
		//_all field picks up all possible suggestions
		$mapping->enableAllField();

		if($this->owner->hasMethod('updateElasticsearchMapping')) {
			$mapping = $this->owner->updateElasticsearchMapping($mapping);
		}
		return $mapping;
	}


	/**
	 * Get an elasticsearch document
	 *
	 * @return \Elastica\Document
	 */
	public function getElasticaDocument() {
		self::$index_ctr++;
		$fields = $this->getFieldValuesAsArray();
		$progress = \Controller::curr()->request->getVar('progress');
		if(!empty($progress)) {
			self::$progressInterval = (int)$progress;
		}

		if(self::$progressInterval > 0) {
			if(self::$index_ctr % self::$progressInterval === 0) {
				ElasticaUtil::message("\t" . $this->owner->ClassName . " - Prepared " . self::$index_ctr . " for indexing...");
			}
		}

		// Optionally update the document
		$document = new Document($this->owner->ID, $fields);
		if($this->owner->hasMethod('updateElasticsearchDocument')) {
			$document = $this->owner->updateElasticsearchDocument($document);
		}

		// Check if the current classname is part of the site tree or not
		// Results are cached to save reprocessing the same
		$classname = $this->owner->ClassName;
		$inSiteTree = SearchableHelper::isInSiteTree($classname);

		$document->set('IsInSiteTree', $inSiteTree);
		if($inSiteTree) {
			$document->set('Link', $this->owner->AbsoluteLink());
		}

		if(isset($this->owner->Locale)) {
			$document->set('Locale', $this->owner->Locale);
		}

		return $document;
	}


	public function getFieldValuesAsArray($recurse = true) {
		$fields = array();
		foreach($this->getElasticaFields($recurse) as $field => $config) {
			//This is the case of calling a method to get a value, the field does not exist in the DB
			if(null === $this->owner->$field && is_callable(get_class($this->owner) . "::" . $field)) {
				// call a method to get a field value
				SearchableHelper::storeMethodTextValue($this->owner, $field, $fields, $this->html_fields);
			} else {
				if(in_array($field, $this->html_fields)) {
					SearchableHelper::storeFieldHTMLValue($this->owner, $field, $fields);
				} else {
					SearchableHelper::storeRelationshipValue($this->owner, $field, $fields, $config, $recurse);
				}
			}
		}
		return $fields;
	}


	/**
	 * Returns whether to include the document into the search index.
	 * All documents are added unless they have a field "ShowInSearch" which is set to false
	 *
	 * @return boolean
	 */
	public function showRecordInSearch() {
		return !($this->owner->hasField('ShowInSearch') && false == $this->owner->ShowInSearch);
	}


	/**
	 * Delete the record from the search index if ShowInSearch is deactivated (non-SiteTree).
	 */
	public function onBeforeWrite() {
		if(
			$this->owner instanceof \SiteTree &&
			$this->owner->hasField('ShowInSearch') &&
			$this->owner->isChanged('ShowInSearch', 2) &&
			false == $this->owner->ShowInSearch
		) {
			$this->doDeleteDocument();
		}
	}


	/**
	 * Delete the record from the search index if ShowInSearch is deactivated (SiteTree).
	 */
	public function onBeforePublish() {
		if(false == $this->owner->ShowInSearch && $this->owner->isPublished()) {
			$liveRecord = \Versioned::get_by_stage(get_class($this->owner), 'Live')->
				byID($this->owner->ID);
			if($liveRecord->ShowInSearch != $this->owner->ShowInSearch) {
				$this->doDeleteDocument();
			}
		}
	}


	/**
	 * Updates the record in the search index (non-SiteTree).
	 */
	public function onAfterWrite() {
		$this->doIndexDocument();
	}


	/**
	 * Updates the record in the search index (SiteTree).
	 */
	public function onAfterPublish() {
		$this->doIndexDocument();
	}


	/**
	 * Updates the record in the search index.
	 */
	protected function doIndexDocument() {
		if($this->showRecordInSearch() && !$this->owner->IndexingOff) {
			$this->service->index($this->owner);
		}
	}


	/**
	 * Removes the record from the search index (non-SiteTree).
	 */
	public function onAfterDelete() {
		$this->doDeleteDocumentIfInSearch();
	}


	/**
	 * Removes the record from the search index (non-SiteTree).
	 */
	public function onAfterUnpublish() {
		$this->doDeleteDocumentIfInSearch();
	}


	/**
	 * Removes the record from the search index if the "ShowInSearch" attribute is set to true.
	 */
	protected function doDeleteDocumentIfInSearch() {
		if($this->showRecordInSearch()) {
			$this->doDeleteDocument();
		}
	}


	/**
	 * Removes the record from the search index.
	 */
	protected function doDeleteDocument() {
		try {
			if(!$this->owner->IndexingOff) {
				// this goes to elastica service
				$this->service->remove($this->owner);
			}
		} catch (\Elastica\Exception\NotFoundException $e) {
			trigger_error("Deleted document " . $this->owner->ClassName . " (" . $this->owner->ID .
				") not found in search index.", E_USER_NOTICE);
		}
	}


	/**
	 * Return all of the searchable fields defined in $this->owner::$searchable_fields and all the parent classes.
	 *
	 * @param  $recuse Whether or not to traverse relationships. First time round yes, subsequently no
	 * @return array searchable fields
	 */
	public function getAllSearchableFields($recurse = true) {
		$fields = \Config::inst()->get(get_class($this->owner), 'searchable_fields');

		// fallback to default method
		if(!$fields) {
			user_error('The field $searchable_fields must be set for the class ' . $this->owner->ClassName);
		}

		// get the values of these fields
		$elasticaMapping = SearchableHelper::fieldsToElasticaConfig($fields);

		if($recurse) {
			// now for the associated methods and their results
			$methodDescs = \Config::inst()->get(get_class($this->owner), 'searchable_relationships');
			$has_ones = $this->owner->has_one();
			$has_lists = SearchableHelper::getListRelationshipMethods($this->owner);

			if(isset($methodDescs) && is_array($methodDescs)) {
				foreach($methodDescs as $methodDesc) {
					// split before the brackets which can optionally list which fields to index
					$splits = explode('(', $methodDesc);
					$methodName = $splits[0];

					if(isset($has_lists[$methodName])) {

						$relClass = $has_lists[$methodName];
						$fields = \Config::inst()->get($relClass, 'searchable_fields');
						if(!$fields) {
							user_error('The field $searchable_fields must be set for the class ' . $relClass);
						}
						$rewrite = SearchableHelper::fieldsToElasticaConfig($fields);

						// mark as a method, the resultant fields are correct
						$elasticaMapping[$methodName . '()'] = $rewrite;
					} else if(isset($has_ones[$methodName])) {
						$relClass = $has_ones[$methodName];
						$fields = \Config::inst()->get($relClass, 'searchable_fields');
						if(!$fields) {
							user_error('The field $searchable_fields must be set for the class ' . $relClass);
						}
						$rewrite = SearchableHelper::fieldsToElasticaConfig($fields);

						// mark as a method, the resultant fields are correct
						$elasticaMapping[$methodName . '()'] = $rewrite;
					} else {
						user_error('The method ' . $methodName . ' not found in class ' . $this->owner->ClassName .
								', please check configuration');
					}
				}
			}
		}

		return $elasticaMapping;
	}




	public function requireDefaultRecords() {
		parent::requireDefaultRecords();
		$searchableFields = $this->getElasticaFields(true, true);
		$doSC = SearchableHelper::findOrCreateSearchableClass($this->owner->ClassName);

		foreach($searchableFields as $name => $searchableField) {
			// check for existence of methods and if they exist use that as the name
			if(!isset($searchableField['type'])) {
				$name = $searchableField['properties']['__method'];
			}

			SearchableHelper::findOrCreateSearchableField(
				$this->owner->ClassName,
				$name,
				$searchableField,
				$doSC
			);

			// FIXME deal with deletions
		}
	}


	/*
	Allow the option of overriding the default template with one of <ClassName>ElasticSearchResult
	 */
	public function RenderResult($linkToContainer = '') {
		$vars = new \ArrayData(array('SearchResult' => $this->owner, 'ContainerLink' => $linkToContainer));
		$possibleTemplates = array($this->owner->ClassName . 'ElasticSearchResult', 'ElasticSearchResult');
		return $this->owner->customise($vars)->renderWith($possibleTemplates);
	}


	public function getTermVectors() {
		return $this->service->getTermVectors($this->owner);
	}


	public function updateCMSFields(\FieldList $fields) {
		$isIndexed = false;
		// SIteTree object must have a live record, ShowInSearch = true
		if (\DB::getConn()->hasTable($this->owner->ClassName)) {
			if(SearchableHelper::isInSiteTree($this->owner->ClassName)) {
				$liveRecord = \Versioned::get_by_stage(get_class($this->owner), 'Live')->
					byID($this->owner->ID);
				if(!empty($liveRecord) && $liveRecord->ShowInSearch) {
					$isIndexed = true;
				} else {
					$isIndexed = false;
				}
			} else {
				// In the case of a DataObject we use the ShowInSearchFlag
				$isIndexed = true;
			}
		}

		if($isIndexed) {
			$termVectors = $this->getTermVectors();
			$termFields = array_keys($termVectors);
			sort($termFields);

			foreach($termFields as $field) {
				$terms = new \ArrayList();

				foreach(array_keys($termVectors[$field]['terms']) as $term) {
					$do = new \DataObject();
					$do->Term = $term;
					$stats = $termVectors[$field]['terms'][$term];
					if(isset($stats['ttf'])) {
						$do->TTF = $stats['ttf'];
					}

					if(isset($stats['doc_freq'])) {
						$do->DocFreq = $stats['doc_freq'];
					}

					if(isset($stats['term_freq'])) {
						$do->TermFreq = $stats['term_freq'];
					}
					$terms->push($do);
				}

				$config = \GridFieldConfig_RecordViewer::create(100);
				$viewer = $config->getComponentByType('GridFieldDataColumns');
				$viewer->setDisplayFields(array(
					'Term' => 'Term',
					'TTF' => 'Total term frequency (how often a term occurs in all documents)',
					'DocFreq' => 'n documents with this term',
					'TermFreq'=> 'n times this term appears in this field'
				));

				$underscored = str_replace('.', '_', $field);

				$alteredFieldName = str_replace('standard', 'unstemmed', $field);
				$splits = explode('_', $underscored);
				if (sizeof($splits) == 1) {
					$alteredFieldName .= '.stemmed';
				}

				$gridField = new \GridField(
					'TermsFor' . $underscored, // Field name
					$alteredFieldName.'T1' , // Field title
					$terms,
					$config
				);

				$underscored = str_replace('.', '_', $alteredFieldName);
			   $fields->addFieldToTab('Root.ElasticaTerms.' . $underscored, $gridField);
			}

		}

		return $fields;
	}


}
