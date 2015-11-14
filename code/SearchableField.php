<?php

use SilverStripe\Elastica\ElasticaUtil;

class SearchableField extends DataObject {
	private static $db = array(
		'Name' => 'Varchar', // the name of the field, e.g. Title
		'ClazzName' => 'Varchar', // the ClassName this field belongs to
		'Type' => 'Varchar', // the elasticsearch indexing type,
		'ShowHighlights' => 'Boolean', // calculate highlights in Elasticsearch
		'Autocomplete' => 'Boolean', // Use this to check for autocomplete fields,
		'IsSiteTree' => 'Boolean' // Set to true if this field originates from a SiteTree object
	);


	private static $belongs_many_many = array(
		'ElasticSearchPages' => 'ElasticSearchPage'
	);

	private static $has_one = array('SearchableClass' => 'SearchableClass');

	private static $display_fields = array('Name');


	function getCMSFields() {
		$fields = new FieldList();
		$fields->push( new TabSet( "Root", $mainTab = new Tab( "Main" ) ) );
		$mainTab->setTitle( _t( 'SiteTree.TABMAIN', "Main" ) );
		$fields->addFieldToTab( 'Root.Main',  $cf = new TextField( 'ClazzName', 'Class sourced from') );
		$fields->addFieldToTab( 'Root.Main',  $nf = new TextField( 'Name', 'Name') );
		$cf->setDisabled(true);
		$nf->setDisabled(true);
		$cf->setReadOnly(true);
		$nf->setReadOnly(true);

		return $fields;
	}

	/*
	Deletion/inactivity is handled by scripts.  Not end user deletable
	 */
	public function canDelete($member = null) {
		return false;
	}

	/*
	Creation is handled by scripts at build time, not end user creatable
	 */
	public function canCreate($member = null) {
		return false;
	}


	public function HumanReadableSearchable() {
		echo $this->Searchable;
		return ElasticaUtil::showBooleanHumanReadable($this->Searchable);
	}

}
