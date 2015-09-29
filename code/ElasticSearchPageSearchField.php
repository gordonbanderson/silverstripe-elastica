<?php

class ElasticSearchPageSearchField extends DataObject {
	private static $db = array(
		'Name' => 'Varchar', // the name of the field, e.g. Title
		'Weight' => 'Float', // the weighting for this field, default 1
		'Type' => 'Varchar', // the elasticsearch indexing type
		'Searchable' => 'Boolean', // allows the option of turning off a single field for searching
		'Active' => 'Boolean' // preserve previous edits of weighting when classes changed
	);

	private static $defaults = array(
		'Searchable' => true,
		'Weight' => 1
	);

	private static $has_one = array('ElasticSearchPage' => 'ElasticSearchPage');

	private static $display_fields = array('Name','Weight','Searchable');


	function getCMSFields() {
		$fields = new FieldList();
		$fields->push( new TabSet( "Root", $mainTab = new Tab( "Main" ) ) );
		$mainTab->setTitle( _t( 'SiteTree.TABMAIN', "Main" ) );
		$fields->addFieldToTab( 'Root.Main',  $nf = new TextField( 'Name', 'Name') );
		$nf->setReadOnly(true);
		$nf->setDisabled(true);
		$fields->addFieldToTab( 'Root.Main',  new NumericField( 'Weight', 'Weight') );
		$fields->addFieldToTab( 'Root.Main',  new CheckboxField( 'Searchable', 'Search this field?') );
		return $fields;
	}


	public function HumanReadableSearchable() {
		return $this->Searchable ? 'Yes':'No';
	}


	/**
	 * Check for weighting > 0
	 * @return DataObject result with or without error
	 */
	public function validate() {
		$result = parent::validate();
		if ($this->Weight <= 0) {
			$result->error('Weight must be more than zero');
		}
		return $result;
	}
}
