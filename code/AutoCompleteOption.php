<?php
class AutoCompleteOption extends DataObject {
	private static $db = array(
		'Name' => 'Varchar',
		'Slug' => 'Varchar',
		'Description' => 'Text',
		'Locale' => 'DBLocale'
	);

	private static $belongs_to = array(
		'ElasticSearchPage' => 'ElasticSearchPage'
	);

	public function can_create($member = null) {
		return false;
	}

	public function can_edit($member = null) {
		return false;
	}

	public function can_delete($member = null) {
		return false;
	}

	public function requireDefaultRecords() {
		parent::requireDefaultRecords();

		$similar = AutoCompleteOption::get()->filter('Name','Similar')->first();
		if (!$similar) {
			$similar = new AutoCompleteOption();
			$similar->Name = 'Similar';
			$similar->Slug = 'SIMILAR';
			$similar->Description = 'Find records similar to the selected item';
			$similar->Locale = i18n::default_locale();
			$similar->write();
		}

		$search = AutoCompleteOption::get()->filter('Name','Search')->first();
		if (!$search) {
			$search = new AutoCompleteOption();
			$search->Name = 'Search';
			$search->Description = 'Find records similar to the selected item';
			$search->Slug = 'SEARCH';
			$search->Locale = i18n::default_locale();
			$search->write();
		}

		$goto = AutoCompleteOption::get()->filter('Name','GoToRecord')->first();
		if (!$goto) {
			$goto = new AutoCompleteOption();
			$goto->Name = 'GoToRecord';
			$goto->Description = 'Go to the page of the selected item, found by the Link() method';
			$goto->Locale = i18n::default_locale();
			$goto->Slug = 'GOTO';
			$goto->write();
		}
	}
}
