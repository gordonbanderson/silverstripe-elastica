<?php

namespace SilverStripe\Elastica;

use Elastica\Document;
use Elastica\Type\Mapping;

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
	 * @var ElasticaService associated elastica search service
	 */
	protected $service;

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
				}
			} else {
				// TODO: Generalize to the mapping types by allowing the type to be specified in $searchable_fields
				$spec["type"] = "string";
			}

			if ($name == 'location') {
				$spec["type"] = "geo_point";
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

		$mapping->setProperties($fields);

		$callable = get_class($this->owner).'::updateElasticsearchMapping';
		if(is_callable($callable))
		{
			$mapping = call_user_func($callable, $mapping);
		}

		$properties = $mapping->getProperties();

		if (isset($properties['location'])) {
			$properties['location'] = array('type' => 'geo_point');
			$mapping->setProperties($properties);
		}


		echo "\n+++++++++ MAPPING +++++++++\n";
		print_r($mapping);

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
				$fields[$field] = $this->owner->$field();
			} else {
				$fields[$field] = $this->owner->$field;
			}
		}

		$document = new Document($this->owner->ID, $fields);

		$callable = get_class($this->owner).'::updateElasticsearchDocument';
		if(is_callable($callable)) {
			$document = call_user_func($callable, $document);
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
		//echo "\n\\\\\\\\\\\\\\\\\n";
		//print_r($fields);

		/*
		Fields is like this
		Array
(
    [0] => Title
    [1] => Content
)


		 */
		foreach($fields as $name => $specOrName) {
			$isField = true; // set to false if the mapping is not a field, e.g. weighting, location

			$identifer = (is_int($name)) ? $specOrName : $name;
			$identifer = $name;
			echo "\nType of spec or name:".gettype($specOrName);
			if (is_int($name)) {
				echo "\nT1\n";


				if (is_array($specOrName)) {
					echo "\nT2\n";
					$identifer = array_keys($specOrName)[0];
					echo "\nT2a Set identifer to ".$identifer;
				} else {
					echo "\nT3\n";
					$identifer = $specOrName;
				}
			}

			echo "\n\n--------\nCheck field $name";
			echo "\nName:\n";
			print_r($name);
			echo "\identifer:\n"; // should be a string
			print_r($identifer);
			echo "\n".gettype($name);
			echo "\nSpec or name\n";
			//print_r($specOrName);

			if ($identifer{0} !== strtoupper($identifer{0})) {
				$isField = false;
			}


			if(is_int($name)) {

				if (is_array($specOrName)) {
					echo "\nREL IS ".$this->owner->relObject($identifer);
					if ($isField) {
						$rewrite[$identifer] = array_merge(
							array('filter' => $this->owner->relObject($identifer)->
								stat('default_search_filter_class')),
							(array)$specOrName
							);
					} else {
						switch ($identifer) {
							// see http://elastica.io/getting-started/storing-and-indexing-documents.html for an example
							case 'location':
								# code...
								#Array is keyed location => blank, 0 => latitude, 1 => longitude
								$latitudeFieldName = $specOrName[0]['latitude'];
								$longitudeFieldName = $specOrName[1]['longitude'];

								$latitude = $this->owner->getField($latitudeFieldName);
								$longitude = $this->owner->getField($longitudeFieldName);
								echo "\n LOCATION:$latitude, $longitude\n";
								$location['lat'] = $latitude;
								$location['lon'] = $longitude;
								unset($location['latitude']);
								unset($location['longitude']);

								$rewrite['location'] = "7.2, 41.8";

								break;

							default:
								# code...
								break;
						}
					}

				} else {
					// Format: array('MyFieldName')
					$rewrite[$identifer] = array();
				}

			} elseif(is_array($specOrName)) {
				// FIXME how to invoke this code from YML configuration?
				// Format: array('MyFieldName' => array(
				//   'filter => 'ExactMatchFilter',
				//   'field' => 'NumericField', // optional
				//   'title' => 'My Title', // optiona.
				// ))

				if ($isField) {
					$rewrite[$identifer] = array_merge(
						array('filter' => $this->owner->relObject($identifer)->
							stat('default_search_filter_class')),
						(array)$specOrName
					);
				}

			} else {
				// Format: array('MyFieldName' => 'ExactMatchFilter')
				$rewrite[$identifer] = array(
					'filter' => $specOrName,
				);
			}

			if ($isField) {
				if(!isset($rewrite[$identifer]['title'])) {
					$rewrite[$identifer]['title'] = (isset($labels[$identifer]))
						? $labels[$identifer] : \FormField::name_to_label($identifer);
				}
				if(!isset($rewrite[$identifer]['filter'])) {
					$rewrite[$identifer]['filter'] = 'PartialMatchFilter';
				}
			}

		}

		/*

		Rewrite becomes like this:

		Array
(
    [Title] => Array
        (
            [title] => Page name
            [filter] => PartialMatchFilter
        )

    [Content] => Array
        (
            [title] => Content
            [filter] => PartialMatchFilter
        )

)

		 */

		print_r($rewrite);
		return $rewrite;
	}

}
