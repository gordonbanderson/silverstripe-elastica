<?php

class ElasticSearchPage_Validator_Test extends ElasticsearchFunctionalTestBase {

	public static $fixture_file = 'elastica/tests/ElasticaTest.yml';

	public function setup() {
		parent::setup();
		$this->ElasticSearchPage = $this->objFromFixture('ElasticSearchPage', 'search');

		$this->ElasticSearchPage->SimilarityStopWords =  'a,the,which';
		$this->ElasticSearchPage->write();

		$this->Validator = $this->ElasticSearchPage->getCMSValidator();
		$fields = $this->ElasticSearchPage->getCMSFields();

	}

	private function getGoodData() {
		return array(
			'Title' => 'Name',
			'URLSegment' => 'name',
			'MenuTitle' => 'Name',
			'Identifier' => 'fredfred',
			'Content' =>' <p>content</p>',
			'MetaDescription' => null,
			'ExtraMeta' => null,
			'ContentForEmptySearch' => '<p>asdfsad</p>',
			'NewTransLang' => 'de_DE',
			'AlternativeTemplate' => null,
			'SiteTreeOnly' => 1,
			'ClassesToSearch'=> '',
			'ResultsPerPage' => 10,
			'AutoCompleteFieldID' => null,
			'AutoCompleteFunctionID' => null,
			'SearchHelper' => null,
			'SimilarityStopWords' => array('a', 'an', 'the', ),
			'MinTermFreq' => 2,
			'MaxTermFreq' => 25,
			'MinDocFreq' => 2,
			'MaxDocFreq' => 0,
			'MinWordLength' => 0,
			'MaxWordLength' => 0,
			'MinShouldMatch' =>  0,
			'Locale' => 'en_US',
			'ClassName' => 'ElasticSearchPage',
			'ParentID' => 2738,
			'ID' => 2739
		);
	}


	public function testGoodData() {
		$fields = $this->ElasticSearchPage->getCMSFields();
		$form = new Form($this, 'Form', $fields, new FieldList());
		//$this->assertTrue($form->validate(), "Saved object should already be validatable at the form level");


		echo "Testing live....\n";
				$this->ElasticSearchPage->publish('Stage', 'Live');

		$fields = $this->ElasticSearchPage->getCMSFields();
			$fields->push(new HiddenField('ID'));
		$form = new Form($this, 'Form', $fields, new FieldList());
		$form->loadDataFrom($this->ElasticSearchPage);
		$form->setValidator($this->ElasticSearchPage->getCMSValidator());
		$this->assertTrue($form->validate(), "Saved object should already be validatable at the form level");

		$origMode = Versioned::get_reading_mode();
		echo "CURRENT MODE:$origMode\n";
		Versioned::set_reading_mode('Stage');
		echo "CURRENT MODE:".Versioned::get_reading_mode()."\n";
		Versioned::set_reading_mode($origMode); // reset current mode
	}


	public function testEmptyIdentifier() {
		$this->checkForError('Identifier', '',
			'"Identifier to allow this page to be found in form templates" is required');
			$this->checkForError('Identifier', null,
			'"Identifier to allow this page to be found in form templates" is required');
	}


	public function testEmptyResultsPerPage() {
		$this->checkForError('ResultsPerPage', '',
			"'' is not a number, only numbers can be accepted for this field");
		$this->checkForError('ResultsPerPage', '',
			"Results per page must be >=1");
		$this->checkForError('ResultsPerPage', 0,
			"Results per page must be >=1");
	}


	public function testEmptyClassesToSearchWithoutSiteTree() {
		$this->ElasticSearchPage->SiteTreeOnly = false;
		$this->checkForError('ClassesToSearch', '',
			"Please provide at least one class to search, or select 'Site Tree Only'");
	}


	public function testNonSearchableClassesToSearch() {
		$this->ElasticSearchPage->SiteTreeOnly = false;
		$this->checkForError('ClassesToSearch', 'Page,Member',
			"The class Member must have the Searchable extension");
	}


	public function testNonExistentClassesToSearch() {
		$this->ElasticSearchPage->SiteTreeOnly = false;
		$this->checkForError('ClassesToSearch', 'Page,THISCLASSDOESNOTEXIST',
			"The class THISCLASSDOESNOTEXIST does not exist");
	}


	public function testDuplicateIdentifier() {
		$esp = new ElasticSearchPage();
		$esp->Title = "Test with duplicate identifier";
		$esp->Identifier = 'THISWILLBECOPIED';
		$esp->write();
		$this->checkForError('Identifier', 'THISWILLBECOPIED',
			'The identifier THISWILLBECOPIED already exists' );
	}


	private function checkForError($fieldName, $badValue, $message) {
		$this->ElasticSearchPage->$fieldName = $badValue;

		$fields = $this->ElasticSearchPage->getCMSFields();
		$fields->push(new HiddenField('ID'));
		$form = new Form($this, 'Form', $fields, new FieldList());
		$form->loadDataFrom($this->ElasticSearchPage);

		foreach ($form->Fields() as $field) {
			echo "************** {$field->getName()} -> {$field->Value()}\n";
		}
		$form->setValidator($this->ElasticSearchPage->getCMSValidator());
		$this->assertFalse($form->validate(), "The alternations to the record are expected to fail");

		$errors = Session::get("FormInfo.{$form->FormName()}.errors");
		$found = false;
		print_r($errors);

		foreach ($errors as $error) {
			echo "Checking error\n";
			echo " - " . $error['fieldName'].' => '.$error['message']."\n";
			echo "\tT1:".($error['message'] == $message);
			echo "\n\tT2:".($error['fieldName'] == $fieldName);

			if ($error['message'] == $message && $error['fieldName'] == $fieldName) {
				$found = true;
				break;
			}
		}
		$this->assertTrue($found, "Message '$message' found");
	}


}
