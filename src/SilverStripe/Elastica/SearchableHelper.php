<?php
namespace SilverStripe\Elastica;

class SearchableHelper {


	public static function addIndexedFields($name, &$spec, $ownerClassName) {
		// in the case of a relationship type will not be set
		if(isset($spec['type'])) {
			if($spec['type'] == 'string') {
				$unstemmed = array();
				$unstemmed['type'] = "string";
				$unstemmed['analyzer'] = "unstemmed";
				$unstemmed['term_vector'] = "yes";
				$extraFields = array('standard' => $unstemmed);

				$shingles = array();
				$shingles['type'] = "string";
				$shingles['analyzer'] = "shingles";
				$shingles['term_vector'] = "yes";
				$extraFields['shingles'] = $shingles;

				//Add autocomplete field if so required
				$autocomplete = \Config::inst()->get($ownerClassName, 'searchable_autocomplete');

				if(isset($autocomplete) && in_array($name, $autocomplete)) {
					$autocompleteField = array();
					$autocompleteField['type'] = "string";
					$autocompleteField['index_analyzer'] = "autocomplete_index_analyzer";
					$autocompleteField['search_analyzer'] = "autocomplete_search_analyzer";
					$autocompleteField['term_vector'] = "yes";
					$extraFields['autocomplete'] = $autocompleteField;
				}

				$spec['fields'] = $extraFields;
				// FIXME - make index/locale specific, get from settings
				$spec['analyzer'] = 'stemmed';
				$spec['term_vector'] = "yes";
			}
		}
	}


	/**
	 * @param string &$name
	 * @param boolean $storeMethodName
	 * @param boolean $recurse
	 */
	public static function assignSpecForRelationship(&$name, $resultType, &$spec, $storeMethodName, $recurse) {
		$resultTypeInstance = \Injector::inst()->create($resultType);
		$resultTypeMapping = array();
		// get the fields for the result type, but do not recurse
		if($recurse) {
			$resultTypeMapping = $resultTypeInstance->getElasticaFields($storeMethodName, false);
		}
		$resultTypeMapping['ID'] = array('type' => 'integer');
		if($storeMethodName) {
			$resultTypeMapping['__method'] = $name;
		}
		$spec = array('properties' => $resultTypeMapping);
		// we now change the name to the result type, not the method name
		$name = $resultType;
	}


	/**
	 * @param string $name
	 */
	public static function assignSpecForStandardFieldType($name, $class, &$spec, &$html_fields, &$mappings) {
		if(($pos = strpos($class, '('))) {
			// Valid in the case of Varchar(255)
			$class = substr($class, 0, $pos);
		}

		if(array_key_exists($class, $mappings)) {
			$spec['type'] = $mappings[$class];
			if($spec['type'] === 'date') {
				$spec['format'] = SearchableHelper::getFormatForDate($class);
			}

			if($class === 'HTMLText' || $class === 'HTMLVarchar') {
				array_push($html_fields, $name);
			}
		}
	}


	public static function getFormatForDate($class) {
		$format = 'y-M-d'; // default
		switch ($class) {
			case 'Date':
				$format = 'y-M-d';
				break;
			case 'SS_Datetime':
				$format = 'y-M-d H:m:s';
				break;
			case 'Datetime':
				$format = 'y-M-d H:m:s';
				break;
			case 'Time':
				$format = 'H:m:s';
				break;
		}

		return $format;
	}


}
