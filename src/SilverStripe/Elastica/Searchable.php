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
	private static $progressInterval = null;

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
		'SS_Datetime' => 'date',
		'Text'        => 'string',
		'Varchar'     => 'string',
		'Year'        => 'integer',
        'Date'        => 'date',
        'DBLocale'    => 'string',
	);

	/**
	 * Mapping of DataObject ClassName and whether it is in the SiteTree or not
	 * @var array $site_tree_classes;
	 */
	private static $site_tree_classes = array();


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

		foreach ($fields as $name => $params) {
			$type = null;
			$spec = array();

			$name = str_replace('()', '', $name);
			//echo "Checking $name \n";

			if (array_key_exists($name, $db)) {
				$class = $db[$name];

				if (($pos = strpos($class, '('))) {
					$class = substr($class, 0, $pos);
				}
				if (array_key_exists($class, self::$mappings)) {
					$spec['type'] = self::$mappings[$class];

					if ($spec['type'] === 'date') {
						if ($class == 'Date') {
							$spec['format'] = 'y-M-d';
						} elseif ($class == 'SS_Datetime') {
							$spec['format'] = 'y-M-d H:m:s';
						}
					}
					if ($class === 'HTMLText' || $class === 'HTMLVarchar') {
						array_push($this->html_fields, $name);
					}
				}
			} else {
				// field name is not in the db, it could be a method
				$has_lists = $this->getListRelationshipMethods();
				$has_ones = $this->owner->has_one();

				// check has_many and many_many relations
				if (isset($has_lists[$name])) {
					// FIX ME how to do nested mapping
					// See https://www.elastic.co/guide/en/elasticsearch/reference/current/mapping-array-type.html

					// the classes returned by the list method
					$resultType = $has_lists[$name];

					$resultTypeInstance = \Injector::inst()->create($resultType);

					// get the fields for the result type, but do not recurse
					if ($recurse) {
						$resultTypeMapping = $resultTypeInstance->getElasticaFields($storeMethodName, false);
					}

					$resultTypeMapping['ID'] = array('type' => 'integer');

					if ($storeMethodName) {
						$resultTypeMapping['__method'] = $name;
					}

					$spec = array('properties' => $resultTypeMapping);


					// we now change the name to the result type, not the method name
					$name = $resultType;
				} else if (isset($has_ones[$name])) {
					$resultType = $has_ones[$name];
					$resultTypeInstance = \Injector::inst()->create($resultType);

					// get the fields for the result type, but do not recurse
					// // FIXME avoid recursing
					if ($recurse) {
						$resultTypeMapping = $resultTypeInstance->getElasticaFields($storeMethodName, false);
					}

					$resultTypeMapping['ID'] = array('type' => 'integer');

					if ($storeMethodName) {
						$resultTypeMapping['__method'] = $name;
					}
					$spec = array('properties' => $resultTypeMapping);
					// we now change the name to the result type, not the method name
					$name = $resultType;
				}
				// otherwise fall back to string
				else {
	                $spec["type"] = "string";
				}
            }

            // if the type is string store stemmed and unstemmed
            /*
            curl -XPUT 'http://localhost:9200/my_index' -d '
{
    "settings": { "number_of_shards": 1 },
    "mappings": {
        "my_type": {
            "properties": {
                "title": {
                    "type":     "string",
                    "analyzer": "english",
                    "fields": {
                        "std":   {
                            "type":     "string",
                            "analyzer": "standard"
                        }
                    }
                }
            }
        }
    }
}
'

Title
Array
(
    [type] => string
)


             */
           // echo "BEFORE: ($name):\n========\n";
           // print_r($spec);

            // in the case of a relationship type will not be set
            if (isset($spec['type'])) {
            	if ($spec['type'] == 'string') {
	            	//echo "$name\n";
	            	$standard = array();
	            	$standard['type'] = "string";
	            	$standard['analyzer'] = "standard";
	            	$extraFields = array('standard' => $standard);
	            	$spec['fields'] = $extraFields;
	            	// FIXME - make index/locale specific, get from settings
					$spec['analyzer'] = 'stemmed';
	            }
            }

            //echo "AFTER: ($name):\n========\n";
            //print_r($spec);

			$result[$name] = $spec;
		}

		return $result;
	}


	/**
	 * Get the elasticsearch mapping for the current document/type
     *
     * @return \Elastica\Type\Mapping
	 */
	public function getElasticaMapping() {
		//echo "\n\n**********************************\n\n";
		$mapping = new Mapping();

        $fields = $this->getElasticaFields(false);

        if ($this->owner->hasField('Locale')) {
			$localeMapping['type'] = 'string';
			// we wish the locale to be stored as is
			$localeMapping['index'] = 'not_analyzed';
			$fields['Locale'] = $localeMapping;
        }

        // ADD CUSTOM FIELDS HERE THAT ARE INDEXED BY DEFAULT
        // add a mapping to flag whether or not class is in SiteTree
        $fields['IsSiteTree'] = array('type'=>'boolean');
        $fields['Link'] = array('type' => 'string');

		$mapping->setProperties($fields);

		//echo "\n\n--------\n".$this->owner->ClassName.'\n';
		//print_r($fields);

        if ($this->owner->hasMethod('updateElasticsearchMapping')) {
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

		if (self::$progressInterval === null) {
			if (isset($_GET['progress'])) {
				$progress = $_GET['progress'];
				self::$progressInterval = (int) $progress;
			} else {
				// flag value to ignore display
				self::$progressInterval = 0;
			}
		}
		if (self::$progressInterval > 0) {
			if (self::$index_ctr % self::$progressInterval === 0) {
				echo "Indexed ".self::$index_ctr."...\n";
			}
		}

		// Optionally update the document
        $document = new Document($this->owner->ID, $fields);
        if ($this->owner->hasMethod('updateElasticsearchDocument')) {
        	$document = $this->owner->updateElasticsearchDocument($document);
        }

        // Check if the current classname is part of the site tree or not
        // Results are cached to save reprocessing the same
		$classname = $this->owner->ClassName;
		$inSiteTree = $this->isInSiteTree($classname);

		$document->set('IsInSiteTree', $inSiteTree);

		if ($inSiteTree) {
			$document->set('Link', $this->owner->AbsoluteLink());
		}

        if (isset($this->owner->Locale)) {
			$document->set('Locale', $this->owner->Locale);
        }

		return $document;
	}


	public function getFieldValuesAsArray($recurse = true) {
		$fields = array();
		$has_ones = $this->owner->has_one();
		foreach ($this->getElasticaFields($recurse) as $field => $config) {
            if (null === $this->owner->$field && is_callable(get_class($this->owner) . "::" . $field)) {
            	// call a method to get a field value
            	if (in_array($field, $this->html_fields)) {
            		$fields[$field] = $this->owner->$field;
            		$html = ShortcodeParser::get_active()->parse($this->owner->$field());
                	$txt = strip_tags($html);
                	$fields[$field] = $txt;
            	} else {
            		$fields[$field] = $this->owner->$field();
            	}

            } else {

                if (in_array($field, $this->html_fields)) {
                	$fields[$field] = $this->owner->$field;;
                	if (gettype($this->owner->$field) !== 'NULL') {
                		$html = ShortcodeParser::get_active()->parse($this->owner->$field);
                		$txt = strip_tags($html);
                		$fields[$field] = $txt;
                	}
                } else {
                	if (isset($config['properties']['__method'])) {
                		$methodName = $config['properties']['__method'];
                		$data = $this->owner->$methodName();
                		$relArray = array();

                		// get the fields of a has_one relational object
                		if (isset($has_ones[$methodName])) {
                			if ($data->ID > 0) {
                				$item = $data->getFieldValuesAsArray(false);
                				array_push($relArray, $item);
                			}

                		// get the fields for a has_many or many_many relational list
                		} else {
                			foreach ($data->getIterator() as $item) {
	                			if ($recurse) {
	                				// populate the subitem but do not recurse any further if more relationships
	                				$itemDoc = $item->getFieldValuesAsArray(false);
	                				array_push($relArray, $itemDoc);
	                			}
	                		}
                		}
                		// save the relation as an array (for now)
                		$fields[$methodName] = $relArray;
                	} else {
                		$fields[$field] = $this->owner->$field;
                	}

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
        return !($this->owner->hasField('ShowInSearch') AND false == $this->owner->ShowInSearch);
    }


    /**
     * Delete the record from the search index if ShowInSearch is deactivated (non-SiteTree).
     */
    public function onBeforeWrite() {
        if (!($this->owner instanceof \SiteTree)) {
            if ($this->owner->hasField('ShowInSearch') AND
            	$this->isChanged('ShowInSearch', 2) AND false == $this->owner->ShowInSearch) {
                $this->doDeleteDocument();
            }
        }
    }


    /**
     * Delete the record from the search index if ShowInSearch is deactivated (SiteTree).
     */
    public function onBeforePublish() {
        if (false == $this->owner->ShowInSearch) {
            if ($this->owner->isPublished()) {
                $liveRecord = \Versioned::get_by_stage(get_class($this->owner), 'Live')->
                	byID($this->owner->ID);
                if ($liveRecord->ShowInSearch != $this->owner->ShowInSearch) {
                    $this->doDeleteDocument();
                }
            }
        }
    }


    /**
     * Updates the record in the search index (non-SiteTree).
     */
    public function onAfterWrite() {
        if (!($this->owner instanceof \SiteTree)) {
            $this->doIndexDocument();
        }
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
        if ($this->showRecordInSearch()) {
            $this->service->index($this->owner);
        }
    }


    /**
     * Removes the record from the search index (non-SiteTree).
     */
    public function onAfterDelete() {
        if (!($this->owner instanceof \SiteTree)) {
            $this->doDeleteDocumentIfInSearch();
        }
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
        if ($this->showRecordInSearch()) {
            $this->doDeleteDocument();
        }
    }


    /**
     * Removes the record from the search index.
     */
    protected function doDeleteDocument() {
        try{
            $this->service->remove($this->owner);
        }
        catch(NotFoundException $e) {
            trigger_error("Deleted document not found in search index.", E_USER_NOTICE);
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
			user_error('The field $searchable_fields must be set for the class '.$this->owner->ClassName);
			die;
        }

        // get the values of these fields
        $elasticaMapping = $this->fieldsToElasticaConfig($this->owner, $fields);

        if ($recurse) {
        	// now for the associated methods and their results
	        $methodDescs = \Config::inst()->get(get_class($this->owner), 'searchable_relationships');
	        $has_ones = $this->owner->has_one();
	        $has_lists = $this->getListRelationshipMethods();

	        if (isset($methodDescs)) {
	        	foreach ($methodDescs as $methodDesc) {
		        	// split before the brackets which can optionally list which fields to index
		        	$splits = explode('(', $methodDesc);
		        	$methodName = $splits[0];

		        	if (isset($has_lists[$methodName])) {

		        		$relClass = $has_lists[$methodName];
		        		$fields = \Config::inst()->get($relClass, 'searchable_fields');
		        		if(!$fields) {
		        			user_error('The field $searchable_fields must be set for class '.$relClass);
		        			die;
				        }
		        		$rewrite = $this->fieldsToElasticaConfig($relClass, $fields);

		        		// mark as a method, the resultant fields are correct
		        		$elasticaMapping[$methodName.'()'] = $rewrite;


		        	} else if (isset($has_ones[$methodName])) {
		        		$relClass = $has_ones[$methodName];
		        		$fields = \Config::inst()->get($relClass, 'searchable_fields');
		        		if(!$fields) {
		        			user_error('The field $searchable_fields must be set for class '.$relClass);
		        			die;
				        }
		        		$rewrite = $this->fieldsToElasticaConfig($relClass, $fields);
		        		$classname = $has_ones[$methodName];

		        		// mark as a method, the resultant fields are correct
		        		$elasticaMapping[$methodName.'()'] = $rewrite;
		        	} else {
		        		user_error($methodName.' not found in class '.$this->owner->ClassName.
		        				', please check configuration');
		        		die;
		        	}
		        }
	        }
        }


        //echo "---- ELASTICA MAPPING {$this->owner->ClassName}----\n";
        //print_r($elasticaMapping);
        //
        //
        return $elasticaMapping;
    }


    /*
    Evaluate each field, e.g. 'Title', 'Member.Name'
     */
    private function fieldsToElasticaConfig($objectInContext, $fields) {
    	// Copied from DataObject::searchableFields() as there is no separate accessible method
    	//echo "---- REWRITING THESE FIELDS ----";
    	//print_r($fields);
        // rewrite array, if it is using shorthand syntax
        $rewrite = array();
        foreach($fields as $name => $specOrName) {
            $identifer = (is_int($name)) ? $specOrName : $name;

            if(is_int($name)) {
                // Format: array('MyFieldName')
                $rewrite[$identifer] = array();
            } elseif(is_array($specOrName)) {
                // Format: array('MyFieldName' => array(
                //   'filter => 'ExactMatchFilter',
                //   'field' => 'NumericField', // optional
                //   'title' => 'My Title', // optiona.
                // ))
                $rewrite[$identifer] = array_merge(
                    array('filter' => $objectInContext->relObject($identifer)->
                    	stat('default_search_filter_class')),
                    (array)$specOrName
                );
            } else {
                // Format: array('MyFieldName' => 'ExactMatchFilter')
                $rewrite[$identifer] = array(
                    'filter' => $specOrName,
                );
            }
            if(!isset($rewrite[$identifer]['title'])) {
                $rewrite[$identifer]['title'] = (isset($labels[$identifer]))
                    ? $labels[$identifer] : \FormField::name_to_label($identifer);
            }
            if(!isset($rewrite[$identifer]['filter'])) {
                $rewrite[$identifer]['filter'] = 'PartialMatchFilter';
            }
        }

       // echo "---- FIELDS REWRITTEN ---\n";
		//print_r($rewrite);

        return $rewrite;
    }


    public function requireDefaultRecords() {
		parent::requireDefaultRecords();
		$searchableFields = $this->getElasticaFields(true,true);
		$doSC = \SearchableClass::get()->filter(array('Name' => $this->owner->ClassName))->first();
		if (!$doSC) {
			$doSC = new \SearchableClass();
			$doSC->Name = $this->owner->ClassName;

			$inSiteTree = $this->isInSiteTree($this->owner->ClassName);
			$doSC->InSiteTree = $inSiteTree;

			$doSC->write();
		}

		foreach ($searchableFields as $name => $searchableField) {
			// check for existence of methods and if they exist use that as the name
			if (isset($searchableField['type'])) {
			} else if (isset($searchableField['__method'])) {
				$name = $searchableField['__method'];
			} else {
				$name = $searchableField['properties']['__method'];
			}

			$filter = array('ClazzName' => $this->owner->ClassName, 'Name' => $name);
			$doSF = \SearchableField::get()->filter($filter)->first();


			if (!$doSF) {
				$doSF = new \SearchableField();
				$doSF->ClazzName = $this->owner->ClassName;
				$doSF->Name = $name;

				if (isset($searchableField['type'])) {
					$doSF->Type = $searchableField['type'];
				} else if (isset($searchableField['__method'])) {
					$doSF->Name = $searchableField['__method'];
					$doSF->Type = 'relationship';
				} else {
					$doSF->Name = $searchableField['properties']['__method'];
					$doSF->Type = 'relationship';
				}
				$doSF->SearchableClassID = $doSC->ID;


				$doSF->write();
				\DB::alteration_message("Created new searchable editable field ".$name,"changed");
			}

			// FIXME deal with deletions
		}
	}


    private function getListRelationshipMethods() {
    	$has_manys = $this->owner->has_many();
        $many_manys = $this->owner->many_many();

        // array of method name to retuned object ClassName for relationships returning lists
        $has_lists = $has_manys;
        foreach (array_keys($many_manys) as $key) {
        	$has_lists[$key] = $many_manys[$key];
        }

        return $has_lists;
    }


    private function isInSiteTree($classname) {
    	$inSiteTree = $classname === 'SiteTree' ? true : false;

		if (!$inSiteTree) {
			$class = new \ReflectionClass($this->owner->ClassName);
			while ($class = $class->getParentClass()) {
			    $parentClass = $class->getName();
			    if ($parentClass == 'SiteTree') {
			    	$inSiteTree = true;
			    	break;
			    }
			}
		}

		return $inSiteTree;
	}

}