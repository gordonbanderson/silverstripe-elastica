<?php
use SilverStripe\Elastica\QueryGenerator;

class ElasticSearchPageSearchField extends DataObject {
	private static $db = array(
		'Name' => 'Varchar', // the name of the field, e.g. Title
		'Weight' => 'Float', // the weighting for this field, default 1
		'ClazzName' => 'Varchar(255)', // SilverStripe classname, needed for autocomplete checking
		'Type' => 'Varchar', // the elasticsearch indexing type
		'Searchable' => 'Boolean', // allows the option of turning off a single field for searching
		'SimilarSearchable' => 'Boolean', // allows field to be used in more like this queries.
		'Active' => 'Boolean', // preserve previous edits of weighting when classes changed
		'EnableAutocomplete' => 'Boolean' // whether or not to show autocomplete search for this field
	);

	private static $defaults = array(
		'Searchable' => true,
		//More like this works best with only a handful of fields, so default is off
		'SearchableMoreLikeThis' => false,
		'Weight' => 1
	);

	private static $has_one = array('ElasticSearchPage' => 'ElasticSearchPage');

	private static $display_fields = array('Name','Weight','Searchable', 'SimilarSearchable', 'EnableAutocomplete');


	function getCMSFields() {
		$fields = new FieldList();
		$fields->push( new TabSet( "Root", $mainTab = new Tab( "Main" ) ) );
		$mainTab->setTitle( _t( 'SiteTree.TABMAIN', "Main" ) );
		$fields->addFieldToTab( 'Root.Main',  $nf = new TextField( 'Name', 'Name') );
		$nf->setReadOnly(true);
		$nf->setDisabled(true);
		$fields->addFieldToTab( 'Root.Main',  new NumericField( 'Weight', 'Weight') );
		$fields->addFieldToTab( 'Root.Main',  new CheckboxField( 'Searchable', 'Search this field?') );
		$fields->addFieldToTab( 'Root.Main',  new CheckboxField( 'SimilarSearchable', 'Use this field for similar search?') );

		// Need to check if this field is autocompletable or not, most will be not
		$quotedNames = QueryGenerator::convertToQuotedCSV($this->ElasticSearchPage()->ClassesToSearch);
		$searchableFields = \SearchableField::get()->where('ClazzName in ('.$quotedNames.')');

		$fields->addFieldToTab( 'Root.Main',  $acf = new CheckboxField( 'EnableAutocomplete',
					'Whether or not use to use autocomplete (if available)') );

		foreach ($searchableFields as $searchableField) {
			err-rLog($searchableField);
		}

		return $fields;
	}


	public function HumanReadableSearchable() {
		return $this->Searchable ? 'Yes':'No';
	}

	public function HumanReadableSimilarSearchable() {
		return $this->SimilarSearchable ? 'Yes':'No';
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
