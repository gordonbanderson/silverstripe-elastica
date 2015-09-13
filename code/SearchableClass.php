<?php

/**
* Represents a searchable class in Elastica for editing purposes
*/
class SearchableClass extends DataObject {

	private static $db = array('Name' => 'Varchar');

	private static $has_many = array('SearchableFields' => 'SearchableField');

	private static $display_fields = array('Name');

	function getCMSFields() {

		$fields = new FieldList();

		$fields->push( new TabSet( "Root", $mainTab = new Tab( "Main" ) ) );
		$mainTab->setTitle( _t( 'SiteTree.TABMAIN', "Main" ) );

		$fields->addFieldToTab( 'Root.Main',  $nf = new TextField( 'Name', 'Name') );
		$nf->setReadOnly(true);
		$nf->setDisabled(true);

		$config = GridFieldConfig_RecordEditor::create();

		// remove add button
		$config->removeComponent($config->getComponentByType('GridFieldAddNewButton'));
		$config->removeComponent($config->getComponentByType('GridFieldDeleteAction'));

		$config->getComponentByType('GridFieldDataColumns')->setDisplayFields(array(
            'Name' => 'Name',
            'Weight' => 'Weighting',
            'HumanReadableIsSearched' => 'Search this field?'
        ));


        $gridField = new GridField(
            'SearchableField', // Field name
            'Field Name', // Field title
            SearchableField::get()->filter('SearchableClassID', $this->ID)->sort('Name'),
            $config
        );

        $fields->addFieldToTab('Root.Main', $gridField);

		/*
	    $fields = new FieldList();
	    $fields->addFieldToTab('Root.Main', new TextField('Name', 'Name'));


        */
	    return $fields;
	}


	/*
		$searchTabName = 'Root.'._t('SiteConfig.ELASTICA', 'Search');
		$fields->addFieldToTab($searchTabName, $h1=new LiteralField('SearchInfo',
			_t('SiteConfig.ELASTICA_SEARCH_INFO', "Select a class to edit the search fields of that class")));

		$config = GridFieldConfig_RelationEditor::create();
		$config->getComponentByType('GridFieldDataColumns')->setDisplayFields(array(
            'Name' => 'Name'
        ));

        $gridField = new GridField(
            'SearchableClass', // Field name
            'Class Name', // Field title
            SearchableClass::get()->sort('Name'), // List of all related students
            $config
        );

        $fields->addFieldToTab($searchTabName, $gridField);
	 */
}
