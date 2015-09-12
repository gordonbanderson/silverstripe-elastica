<?php

class SearchableField extends DataObject {
	private static $db = array(
		'Name' => 'Varchar', // the name of the field, e.g. Title
		'ClazzName' => 'Varchar', // the ClassName this field belongs to
		'Weight' => 'Float', // the weighting for this field, default 1
		'Type' => 'Varchar', // the elasticsearch indexing type
		'IsSearched' => 'Boolean' // allows the option of turning off a single field for searching
	);

	private static $defaults = array(
		'IsSearched' => true,
		'Weight' => 1
	);
}
