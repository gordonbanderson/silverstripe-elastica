<?php
class ElasticaSiteConfig extends DataExtension {
	static $display_fields = array(
		'Name'
	);

	function updateCMSFields(FieldList $fields){
		$searchTabName = 'Root.'._t('SiteConfig.ELASTICA', 'Search');
		$fields->addFieldToTab($searchTabName, $h1=new LiteralField('SearchInfo',
			_t('SiteConfig.ELASTICA_SEARCH_INFO', "Select a class to edit the search fields of that class")));

		$config = GridFieldConfig_RecordEditor::create();
		$config->getComponentByType('GridFieldDataColumns')->setDisplayFields(array(
            'Name' => 'Name','IsSiteTreeHumanReadable' => 'In SiteTree?'
        ));

        $config->removeComponent($config->getComponentByType('GridFieldAddNewButton'));
		$config->removeComponent($config->getComponentByType('GridFieldDeleteAction'));

        $gridField = new GridField(
            'SearchableClass', // Field name
            'Class Name', // Field title
            SearchableClass::get()->sort('Name'), // List of all related students
            $config
        );

        $fields->addFieldToTab($searchTabName, $gridField);
	}
}
