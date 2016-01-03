<?php

class AutoCompleteOptionTest extends ElasticsearchBaseTest {

	public function setUp() {
		$this->AutoCompleteOption = new AutoCompleteOption();
		parent::setUp();
	}

	public function testCanCreate() {
		$this->assertFalse($this->AutoCompleteOption->can_create(), 'CMS users should not be able to
				create this');
	}

	public function testCanDelete() {
		$this->assertFalse($this->AutoCompleteOption->can_delete(), 'CMS users should not be able to
				delete this');
	}

	public function testCanEdit() {
		$this->assertFalse($this->AutoCompleteOption->can_edit(), 'CMS users should not be able to
				edit this');
	}


	/*
	Check that requireDefaultRecords only creates 3 options
	- similar
	- search
	- go to record
	 */
	public function testRequireDefaultRecords() {
		$this->AutoCompleteOption->requireDefaultRecords();

		$similar = AutoCompleteOption::get()->filter('Name','Similar')->first();
		$this->assertEquals(1, $similar->ID);

		$search = AutoCompleteOption::get()->filter('Name','Search')->first();
		$this->assertEquals(2, $search->ID);

		$goto = AutoCompleteOption::get()->filter('Name','GoToRecord')->first();
		$this->assertEquals(3, $goto->ID);

	}


}
