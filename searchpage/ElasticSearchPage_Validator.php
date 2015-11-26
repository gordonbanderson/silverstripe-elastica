<?php

class ElasticSearchPage_Validator extends RequiredFields {

	protected $customRequired = array('Name');

	/**
	 * Constructor
	 */
	public function __construct() {
		$required = array('ResultsPerPage', 'Identifier');

		parent::__construct($required);
	}

	function php($data) {
		parent::php($data);
		$valid = true;
		//Debug::message("Validating data: " . print_r($data, true));
		$valid = parent::php($data);
		//Debug::message("Returning false, just to check");
		//return false;
		//

		if ($data['ClassesToSearch'] == array()) {
			$data['ClassesToSearch'] = '';
		}

		$debug = $data['SiteTreeOnly'];
		echo "STDATA:$debug\n";

		// Check if any classes to search if site tree only is not ticked
		if (!$data['SiteTreeOnly']) {
			if (!$data['ClassesToSearch']) {
				$valid = false;
				$this->validationError("ClassesToSearch",
					"Please provide at least one class to search, or select 'Site Tree Only'",
					'error'
				);
			} else {
				$toSearch = $data['ClassesToSearch'];
				foreach ($toSearch as $clazz) {
					try {
						$instance = Injector::inst()->create($clazz);
						if (!$instance->hasExtension('SilverStripe\Elastica\Searchable')) {
							$this->validationError('ClassesToSearch', 'The class '.$clazz.' must have the Searchable extension');
						}
					} catch (ReflectionException $e) {
						$this->validationError("ClassesToSearch",
							'The class '.$clazz.' does not exist',
							'error'
						);
					}
				}
			}
		}


		// Check the identifier is unique
		$mode = Versioned::get_reading_mode();
		$suffix =  '';
		if ($mode == 'Stage.Live') {
			$suffix = '_Live';
		}
		$where = 'ElasticSearchPage'.$suffix.'.ID != '.$data['ID']." AND `Identifier` = '".$data['Identifier']."'";
		$existing = ElasticSearchPage::get()->where($where)->count();
		if ($existing > 0) {
			$valid = false;
			$this->validationError('Identifier',
					'The identifier '.$data['Identifier'].' already exists',
					'error'
			);
		}


		// Check number of results per page >= 1
		if ($data['ResultsPerPage'] <= 0) {
			$valid = false;
			$this->validationError('ResultsPerPage',
				'Results per page must be >=1'
				,'error'
			);
		}
		return $valid;
	}
}
