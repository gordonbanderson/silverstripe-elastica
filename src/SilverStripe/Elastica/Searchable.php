<?php

namespace SilverStripe\Elastica;

use Elastica\Document;
use Elastica\Type\Mapping;
use ShortcodeParser;

/**
 * Adds elastic search integration to a data object.
 */
class Searchable extends \DataExtension {

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
	public function getElasticaFields() {
        $db = $this->owner->db();
		$fields = $this->getAllSearchableFields();
		$result = array();

		foreach ($fields as $name => $params) {
			$type = null;
			$spec = array();

			if (array_key_exists($name, $db)) {
				$class = $db[$name];

				if (($pos = strpos($class, '('))) {
					$class = substr($class, 0, $pos);
				}
				if (array_key_exists($class, self::$mappings)) {
					$spec['type'] = self::$mappings[$class];
					if ($class === 'HTMLText' || $class === 'HTMLVarchar') {
						array_push($this->html_fields, $name);
					}
				}
			} else {
                // TODO: Generalize to the mapping types by allowing the type to be specified in $searchable_fields
                $spec["type"] = "string";
            }

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
		$mapping = new Mapping();

        $fields = $this->getElasticaFields();

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

        $callable = get_class($this->owner).'::updateElasticsearchMapping';
        if(is_callable($callable))
        {
            $mapping = call_user_func($callable, $mapping);
        }

		return $mapping;
	}

    /**
     * Get an elasticsearch document
     *
     * @return \Elastica\Document
     */
	public function getElasticaDocument() {
		$fields = array();

		foreach ($this->getElasticaFields() as $field => $config) {
            if (null === $this->owner->$field && is_callable(get_class($this->owner) . "::" . $field)) {
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
                	$fields[$field] = $this->owner->$field;
                }

            }
		}

        $document = new Document($this->owner->ID, $fields);

        $callable = get_class($this->owner).'::updateElasticsearchDocument';
        if(is_callable($callable)) {
            $document = $this->owner->updateElasticsearchDocument($document);
        }

        // Check if the current classname is part of the site tree or not
        // Results are cached to save reprocessing the same
		$classname = $this->owner->ClassName;
		$inSiteTree = false;
		if (isset($site_tree_classes[$classname])) {
			$inSiteTree = $site_tree_classes[$classname];
		} else {
			$class = new \ReflectionClass($this->owner);
			while ($class = $class->getParentClass()) {
			    $parentClass = $class->getName();
			    if ($parentClass == 'SiteTree') {
			    	$inSiteTree = true;
			    	break;
			    }
			}
			$site_tree_classes[$classname] = $inSiteTree;
		}

		$document->set('IsInSiteTree', $inSiteTree);

		if ($inSiteTree) {
			$document->set('Link', $this->owner->AbsoluteLink());
		}

        if (isset($this->owner->Locale)) {
			$document->set('Locale', $this->owner->Locale);
        }

		return $document;
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
     * @return array searchable fields
     */
    public function getAllSearchableFields() {
        $fields = \Config::inst()->get(get_class($this->owner), 'searchable_fields');
        $labels = $this->owner->fieldLabels();

        // fallback to default method
        if(!$fields) {
            return $this->owner->searchableFields();
        }

        // Copied from DataObject::searchableFields() as there is no separate accessible method

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
                    array('filter' => $this->owner->relObject($identifer)->
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

        return $rewrite;
    }

}
