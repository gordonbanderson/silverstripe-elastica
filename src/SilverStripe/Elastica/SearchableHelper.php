<?php

namespace SilverStripe\Elastica;

class SearchableHelper
{
    public static function addIndexedFields($name, &$spec, $ownerClassName)
    {
        // in the case of a relationship type will not be set
        if (isset($spec['type'])) {
            if ($spec['type'] == 'string') {
                $unstemmed = array();
                $unstemmed['type'] = 'string';
                $unstemmed['analyzer'] = 'unstemmed';
                $unstemmed['term_vector'] = 'yes';
                $extraFields = array('standard' => $unstemmed);

                $shingles = array();
                $shingles['type'] = 'string';
                $shingles['analyzer'] = 'shingles';
                $shingles['term_vector'] = 'yes';
                $extraFields['shingles'] = $shingles;

                //Add autocomplete field if so required
                $autocomplete = \Config::inst()->get($ownerClassName, 'searchable_autocomplete');

                if (isset($autocomplete) && in_array($name, $autocomplete)) {
                    $autocompleteField = array();
                    $autocompleteField['type'] = 'string';
                    $autocompleteField['index_analyzer'] = 'autocomplete_index_analyzer';
                    $autocompleteField['search_analyzer'] = 'autocomplete_search_analyzer';
                    $autocompleteField['term_vector'] = 'yes';
                    $extraFields['autocomplete'] = $autocompleteField;
                }

                $spec['fields'] = $extraFields;
                // FIXME - make index/locale specific, get from settings
                $spec['analyzer'] = 'stemmed';
                $spec['term_vector'] = 'yes';
            }
        }
    }

    /**
     * @param string &$name
     * @param bool   $storeMethodName
     * @param bool   $recurse
     */
    public static function assignSpecForRelationship(&$name, $resultType, &$spec, $storeMethodName, $recurse)
    {
        $resultTypeInstance = \Injector::inst()->create($resultType);
        $resultTypeMapping = array();
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
    }

    /**
     * @param string $name
     */
    public static function assignSpecForStandardFieldType($name, $class, &$spec, &$html_fields, &$mappings)
    {
        if (($pos = strpos($class, '('))) {
            // Valid in the case of Varchar(255)
            $class = substr($class, 0, $pos);
        }

        if (array_key_exists($class, $mappings)) {
            $spec['type'] = $mappings[$class];
            if ($spec['type'] === 'date') {
                $spec['format'] = self::getFormatForDate($class);
            }

            if ($class === 'HTMLText' || $class === 'HTMLVarchar') {
                array_push($html_fields, $name);
            }
        }
    }

    public static function getFormatForDate($class)
    {
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

    public static function getListRelationshipMethods($instance)
    {
        $has_manys = $instance->has_many();
        $many_manys = $instance->many_many();

        // array of method name to retuned object ClassName for relationships returning lists
        $has_lists = $has_manys;
        foreach (array_keys($many_manys) as $key) {
            $has_lists[$key] = $many_manys[$key];
        }

        return $has_lists;
    }

    public static function isInSiteTree($classname)
    {
        $inSiteTree = ($classname === 'SiteTree' ? true : false);
        if (!$inSiteTree) {
            $class = new \ReflectionClass($classname);
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

    public static function storeMethodTextValue($instance, $field, &$fields, $html_fields)
    {
        if (in_array($field, $html_fields)) {
            // Parse short codes in HTML, and then convert to text
            $fields[$field] = $instance->$field;
            $html = \ShortcodeParser::get_active()->parse($instance->$field());
            $txt = \Convert::html2raw($html);
            $fields[$field] = $txt;
        } else {
            // Plain text
            $fields[$field] = $instance->$field();
        }
    }

    public static function storeFieldHTMLValue($instance, $field, &$fields)
    {
        $fields[$field] = $instance->$field;
        if (gettype($instance->$field) !== 'NULL') {
            $html = \ShortcodeParser::get_active()->parse($instance->$field);
            $txt = \Convert::html2raw($html);
            $fields[$field] = $txt;
        }
    }

    public static function storeRelationshipValue($instance, $field, &$fields, $config, $recurse)
    {
        if (isset($config['properties']['__method'])) {
            $methodName = $config['properties']['__method'];
            $data = $instance->$methodName();
            $relArray = array();

            $has_ones = $instance->has_one();
            // get the fields of a has_one relational object
            if (isset($has_ones[$methodName])) {
                if ($data->ID > 0) {
                    $item = $data->getFieldValuesAsArray(false);
                    $relArray = $item;
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
            $fields[$field] = $instance->$field;
        }
    }

    public static function findOrCreateSearchableClass($classname)
    {
        $doSC = \SearchableClass::get()->filter(array('Name' => $classname))->first();
        if (!$doSC) {
            $doSC = new \SearchableClass();
            $doSC->Name = $classname;
            $inSiteTree = self::isInSiteTree($classname);
            $doSC->InSiteTree = $inSiteTree;
            $doSC->write();
        }

        return $doSC;
    }

    public static function findOrCreateSearchableField($className, $fieldName, $searchableField, $searchableClass)
    {
        $filter = array('ClazzName' => $className, 'Name' => $fieldName);
        $doSF = \SearchableField::get()->filter($filter)->first();

        if (!$doSF) {
            $doSF = new \SearchableField();
            $doSF->ClazzName = $className;
            $doSF->Name = $fieldName;

            if (isset($searchableField['type'])) {
                $doSF->Type = $searchableField['type'];
            } else {
                $doSF->Name = $searchableField['properties']['__method'];
                $doSF->Type = 'relationship';
            }
            $doSF->SearchableClassID = $searchableClass->ID;

            if (isset($searchableField['fields']['autocomplete'])) {
                $doSF->Autocomplete = true;
            }

            $doSF->write();
            \DB::alteration_message('Created new searchable editable field '.$fieldName, 'changed');
        }
    }

    /*
    Evaluate each field, e.g. 'Title', 'Member.Name'
     */
    public static function fieldsToElasticaConfig($fields)
    {
        // Copied from DataObject::searchableFields() as there is no separate accessible method
        $rewrite = array();
        foreach ($fields as $name => $specOrName) {
            $identifer = (is_int($name)) ? $specOrName : $name;
            $rewrite[$identifer] = array();
            if (!isset($rewrite[$identifer]['title'])) {
                $rewrite[$identifer]['title'] = (isset($labels[$identifer]))
                    ? $labels[$identifer] : \FormField::name_to_label($identifer);
            }
            if (!isset($rewrite[$identifer]['filter'])) {
                $rewrite[$identifer]['filter'] = 'PartialMatchFilter';
            }
        }

        return $rewrite;
    }
}
