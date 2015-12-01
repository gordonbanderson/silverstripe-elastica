<?php

/**
 * @package comments
 */
class ElasticSearchPageTest extends ElasticsearchBaseTest {

	public static $fixture_file = 'elastica/tests/ElasticaTest.yml';


	public function testCMSFields() {
		$searchPage = $this->objFromFixture('ElasticSearchPage', 'search');

		$fields = $searchPage->getCMSFields();

		$mainTab = $this->checkTabExists($fields,'Main');
		$this->checkFieldExists($mainTab, 'Identifier');
		$this->checkFieldExists($mainTab, 'ContentForEmptySearch');

		$searchTab = $this->checkTabExists($fields,'Search.SearchFor');
		$fieldsTab = $this->checkTabExists($fields,'Search.Fields');
		$autoCompleteTab = $this->checkTabExists($fields,'Search.AutoComplete');
		$aggTab = $this->checkTabExists($fields,'Search.Aggregations');
		$simTab = $this->checkTabExists($fields,'Search.Similarity');

		$this->checkFieldExists($searchTab, 'InfoField');
		$this->checkFieldExists($searchTab, 'SiteTreeOnly');
		$this->checkFieldExists($searchTab, 'ClassesToSearch');
		$this->checkFieldExists($searchTab, 'InfoField');
		$this->checkFieldExists($searchTab, 'SiteTreeOnly');
		$this->checkFieldExists($searchTab, 'ResultsPerPage');

		$this->checkFieldExists($aggTab, 'SearchHelper');

		$this->checkFieldExists($fieldsTab, 'SearchInfo');
		$this->checkFieldExists($fieldsTab, 'ElasticaSearchableFields');

		$this->checkFieldExists($simTab, 'SimilarityNotes');
		$this->checkFieldExists($simTab, 'MinTermFreq');
		$this->checkFieldExists($simTab, 'MaxTermFreq');
		$this->checkFieldExists($simTab, 'MinDocFreq');
		$this->checkFieldExists($simTab, 'MaxDocFreq');
		$this->checkFieldExists($simTab, 'MinWordLength');
		$this->checkFieldExists($simTab, 'MaxWordLength');
		$this->checkFieldExists($simTab, 'MinShouldMatch');

		$form = new Form($this, 'Form', $fields, new FieldList());

		$tab = $fields->findOrMakeTab("Root.Search.Fields");
		$fieldsTab = $tab->Fields();

		foreach ($fieldsTab as $field) {
			echo "FIELD:{$field->getName()}\n";
		}

		$pickerField = $fieldsTab->fieldByName('ElasticaSearchableFields');
		$pickerConfig = $pickerField->getConfig();
		$gridDetailForm = $pickerConfig->getComponentByType('GridFieldDetailForm');

		echo "SEARCHABLE FIELDS\n";
		foreach ($searchPage->ElasticaSearchableFields()->getIterator() as $sf) {
			echo "SEARCHABLE FIELD {$sf->ClazzName},{$sf->Name}!\n";

		}
		$searchableField = $searchPage->ElasticaSearchableFields()->filter(
			array('ClazzName' => 'SiteTree', 'Name' => 'Title')
		)->first();

		$request = new GridFieldDetailForm_ItemRequest(
			GridField::create('ElasticaSearchableFields', 'ElasticaSearchableFields'),
			$gridDetailForm,
			$searchableField,
			new Controller(),
			'Form'
		);
		$form = $request->ItemEditForm();
		$field = $form->Fields()->fieldByName('ClazzName');
		$this->assertEquals('ClazzName', $field->getName());
		$this->assertEquals('SiteTree', $field->Value());

		$field = $form->Fields()->fieldByName('Autocomplete');
		$this->assertEquals('Autocomplete', $field->getName());
		$this->assertEquals(1, $field->Value());

		$field = $form->Fields()->fieldByName('ManyMany[Searchable]');
		$this->assertEquals('ManyMany[Searchable]', $field->getName());
		$this->assertEquals(0, $field->Value());

		$field = $form->Fields()->fieldByName('ManyMany[SimilarSearchable]');
		$this->assertEquals('ManyMany[SimilarSearchable]', $field->getName());
		$this->assertEquals(0, $field->Value());

		$field = $form->Fields()->fieldByName('ManyMany[Weight]');
		$this->assertEquals('ManyMany[Weight]', $field->getName());
		$this->assertEquals('', $field->Value());

		$field = $form->Fields()->fieldByName('ShowHighlights');
		$this->assertEquals('ShowHighlights', $field->getName());
		$this->assertEquals(0, $field->Value());

		$field = $form->Fields()->fieldByName('ManyMany[EnableAutocomplete]');
		$this->assertEquals('ManyMany[EnableAutocomplete]', $field->getName());
		$this->assertEquals(0, $field->Value());

		$this->assertNotNull($form->Fields()->fieldByName('SecurityID'));


		// now choose a field without autocopmlete
		$searchableField = $searchPage->ElasticaSearchableFields()->filter(
			array('ClazzName' => 'SiteTree', 'Name' => 'Content')
		)->first();

		$request = new GridFieldDetailForm_ItemRequest(
			GridField::create('ElasticaSearchableFields', 'ElasticaSearchableFields'),
			$gridDetailForm,
			$searchableField,
			new Controller(),
			'Form'
		);
		$form = $request->ItemEditForm();
		$field = $form->Fields()->fieldByName('Autocomplete');
		$this->assertEquals('Autocomplete', $field->getName());
		$this->assertEquals(0, $field->Value());

	}


	public function testCannotWriteBlankIdentifier() {
		$esp = new ElasticSearchPage();
		$esp->Title = 'test';
		try {
			$esp->write();
		} catch (ValidationException $e) {
			$this->assertEquals('The identifier cannot be blank', $e->getMessage());
		}
	}


	public function testCannotWriteDuplicateIdentifierDraft() {
		$esp = new ElasticSearchPage();
		$esp->Title = 'test';
		$otherIdentifier = ElasticSearchPage::get()->first()->Identifier;
		$esp->Identifier = $otherIdentifier;
		try {
			$esp->write();
		} catch (ValidationException $e) {
			$this->assertEquals('The identifier testsearchpage already exists', $e->getMessage());
		}
	}


	public function testCannotWriteDuplicateIdentifierLive() {
		$esp = new ElasticSearchPage();
		$esp->Title = 'unique-id';
		$otherIdentifier = ElasticSearchPage::get()->first()->Identifier;
		$esp->Identifier = $otherIdentifier;
		try {
			$esp->write();
			$esp->doPublish();
			$esp->Identifier = $otherIdentifier;
			$esp->write();
		} catch (ValidationException $e) {
			$this->assertEquals('The identifier testsearchpage already exists', $e->getMessage());
		}
	}




		/*
	Test the button override function
	 */
	public function testModelSearchFormAutocompleteAttributes() {
		$searchPageObj = $this->objFromFixture('ElasticSearchPage', 'search');

		$searchPageObj->ClassesToSearch = 'FlickrPhotoTO';
		$filter = array('ClazzName' => 'FlickrPhotoTO', 'Name' => 'Title');
		$sfid = SearchableField::get()->filter($filter)->first()->ID;
		$searchPageObj->AutoCompleteFieldID = $sfid;
		$pageLength = 10; // the default
		$searchPageObj->ResultsPerPage = $pageLength;
		$searchPageObj->write();
		$searchPageObj->publish('Stage','Live');

		$form = $searchPageObj->SearchForm();
		$fields = $form->Fields();
		$q = $fields->fieldByName('q');
		$attributes = $q->getAttributes();
		$expected = array(
			'type' => 'text',
			'name' => 'q',
			'value' => '',
			'class' => 'text autocomplete nolabel',
			'id' => 'ElasticSearchForm_SearchForm_q',
			'disabled' => '',
			'readonly' => '',
			'data-autocomplete' => 'true',
			'data-autocomplete-field' => 'Title',
			'data-autocomplete-classes' => 'FlickrPhotoTO',
			'data-autocomplete-sitetree' => '1',
			'data-autocomplete-source' => '/search/',
			'data-autocomplete-function' => '',
			);
		$this->assertEquals($attributes, $attributes);

	}



	/*
	Test the button override function
	 */
	public function testModelSearchFormButtonOverride() {
		$override = 'My Button Override Text';
		$searchPageObj = $this->objFromFixture('ElasticSearchPage', 'search');
		$form = $searchPageObj->SearchForm($override);
		$actions = $form->Actions();
		$button = $actions->fieldByName('action_submit');
		$this->assertEquals($override, $button->Title());

		// no override, use default
		$form = $searchPageObj->SearchForm();
		$actions = $form->Actions();
		$button = $actions->fieldByName('action_submit');
		$this->assertEquals('', $button->Value());
	}



	public function testCannotWriteInvalidClassname() {
		$searchPage = $this->objFromFixture('ElasticSearchPage', 'search');
		$searchPage->ClassesToSearch = 'ThisClassDoesNotExist';
		$searchPage->SiteTreeOnly = false;

		try {
			$searchPage->write();
			$this->fail('Page should not be writeable');
		} catch (ValidationException $e) {
			$this->assertEquals('The class ThisClassDoesNotExist does not exist', $e->getMessage());
		}
	}

	public function testCannotWriteNoSiteTreeOnlyNoClassNames() {
		$searchPage = $this->objFromFixture('ElasticSearchPage', 'search');
		$searchPage->ClassesToSearch = '';
		$searchPage->SiteTreeOnly = false;

		try {
			$searchPage->write();
			$this->fail('Page should not be writeable');
		} catch (ValidationException $e) {
			$this->assertEquals('At least one searchable class must be available, or SiteTreeOnly flag set', $e->getMessage());
		}
	}


	public function testEmptySearchableClass() {
		$searchPage = $this->objFromFixture('ElasticSearchPage', 'search');

		// This does not implement searchable
		$searchPage->ClassesToSearch = '';
		$searchPage->SiteTreeOnly = false;

		try {
			$searchPage->write();
			$this->fail('Page should not be writeable');
		} catch (ValidationException $e) {
			$this->assertEquals(
				'At least one searchable class must be available, or SiteTreeOnly flag set',
				$e->getMessage()
			);
		}
	}


	public function testNonSearchableClass() {
		$searchPage = $this->objFromFixture('ElasticSearchPage', 'search');
		$searchPage->SiteTreeOnly = false;

		// This does not implement searchable
		$searchPage->ClassesToSearch = 'Member';

		try {
			$searchPage->write();
			$this->fail('Page should not be writeable');
		} catch (ValidationException $e) {
			$this->assertEquals('The class Member must have the Searchable extension', $e->getMessage());
		}

		$this->assertEquals(8, $searchPage->ElasticaSearchableFields()->count());

		//Because the write should fail, this will still be the original value of 8
		$this->assertEquals(8, $searchPage->ElasticaSearchableFields()->filter('Active', true)->count());

	}


	/*
	Test setting up a search page for data objects as if editing the CMS directly
	 */
	public function testSearchPageForDataObjects() {
		echo "========================== TEST STARTS NOW ==========================\n";
		//$this->devBuild();
		$searchPage = $this->objFromFixture('ElasticSearchPage', 'search');

		$searchPage->ClassesToSearch = 'FlickrPhotoTO';
		$searchPage->SiteTreeOnly = 0;
		$searchPage->Title = '**** Flickr Photo Search ****';
		$searchPage->write();
		//$searchPage->publish('Stage', 'Live');

		$filter = array('ClazzName' => 'FlickrPhotoTO', 'Name' => 'Title');

		//Check fieldnames as expected
		$searchableFields = $searchPage->ElasticaSearchableFields()->filter('Active',1);
		$sfs = $searchableFields->map('Name')->toArray();
		sort($sfs);
		$expected = array('Aperture','AspectRatio','Description','FirstViewed','FlickrID',
			'FlickrSetTOs','FlickrTagTOs','FocalLength35mm','ISO','Photographer','ShutterSpeed',
			'TakenAt', 'TakenAtDT', 'TestMethod', 'TestMethodHTML', 'Title');
		$this->assertEquals($expected, $sfs);


		$searchPage->ClassesToSearch = '';
		$searchPage->SiteTreeOnly = 1;
		$searchPage->Title = '**** SiteTree Search ****';
		$searchPage->write();

		$searchableFields = $searchPage->ElasticaSearchableFields()->filter('Active',1);
		$sfs = $searchableFields->map('Name')->toArray();
		sort($sfs);
		$expected = array('Content', 'Country', 'PageDate', 'Title');
		$this->assertEquals($expected, $sfs);
	}


	/*
	Test that during the build process, requireDefaultRecords creates records for
	each unique field name declared in searchable_fields
	 */
	public function testSearchableFieldsCreatedAtBuildTime() {

		$searchableTestPage = $this->objFromFixture('SearchableTestPage', 'first');
		$searchPage = $this->objFromFixture('ElasticSearchPage', 'search');

		// expected mapping of searchable classes to searchable fields that will be
		// stored in the database as SearchableClass and SearchableField
		$expected = array(
			'Page' => array('Title','Content'),
			'SiteTree' => array('Title','Content'),
			'SearchableTestPage' => array('Title','Content','Country','PageDate'),
			'FlickrTagTO' => array('RawValue'),
			'FlickrAuthorTO' => array('PathAlias','DisplayName','FlickrPhotoTOs'),
			'FlickrPhotoTO' => array('Title','FlickrID','Description','TakenAt', 'TakenAtDT', 'Aperture',
				'ShutterSpeed','FocalLength35mm','ISO','Photographer','FlickrTagTOs','FlickrSetTOs',
				'FirstViewed','AspectRatio', 'TestMethod', 'TestMethodHTML'),
			'FlickrSetTO' => array('Title','FlickrID','Description')
		);

		// check the expected classes
		$expectedClasses = array_keys($expected);
		$nSearchableClasses = SearchableClass::get()->count();
		$this->assertEquals(sizeof($expectedClasses), $nSearchableClasses);


 		$searchPage->SiteTreeOnly = true;
		$searchPage->Content = 'some random string';
		$searchPage->write();
		$scs = SearchableClass::get();

		$sfs = $searchPage->SearchableFields();



		// check the names expected to appear

		$fieldCtr = 0;
		foreach ($expectedClasses as $expectedClass) {
			$expectedFields = array();
			$sc = SearchableClass::get()->filter('Name', $expectedClass)->first();
			$this->assertEquals($expectedClass,$sc->Name);

			$inSiteTree = 1;
			$start = substr($expectedClass, 0,6);
			if ($start == 'Flickr') {
				$inSiteTree = 0;
			};
			$this->assertEquals($inSiteTree,$sc->InSiteTree);

			$expectedNames = $expected[$expectedClass];
			foreach ($expectedNames as $expectedName) {
				$filter = array('Name' => $expectedName, 'SearchableClassID' => $sc->ID );
				$sf = SearchableField::get()->filter($filter)->first();
				$this->assertEquals($expectedName, $sf->Name);
				$fieldCtr++;
				array_push($expectedFields, $expectedName);
			}

		}
		$nSearchableFields = SearchableField::get()->count();

		$this->assertEquals($fieldCtr, $nSearchableFields);
	}



	public function testGetCMSValidator() {
		$searchPage = $this->objFromFixture('ElasticSearchPage', 'search');
		$validator = $searchPage->getCMSValidator();
		$this->assertEquals('ElasticSearchPage_Validator', get_class($validator));
	}


	public function testValidateClassesToSearchNonExistent() {
		$searchPage = $this->objFromFixture('ElasticSearchPage', 'search');
		$searchPage->ClassesToSearch = 'WibbleWobble'; // does not exist
		$searchPage->SiteTreeOnly = false;
		try {
			$searchPage->write();
			$this->fail('Test should have failed as WibbleWobble is not a valid class');
		} catch (ValidationException $e) {
			$this->assertEquals('The class WibbleWobble does not exist', $e->getMessage());
		}
	}


	public function testValidateClassesToSearchNotSearchable() {
		$searchPage = $this->objFromFixture('ElasticSearchPage', 'search');
		$searchPage->ClassesToSearch = 'member'; // does not implement Searchable
		$searchPage->SiteTreeOnly = false;
		try {
			$searchPage->write();
			$this->fail('Test should have failed as WibbleWobble is not a valid class');
		} catch (ValidationException $e) {
			$this->assertEquals('The class member must have the Searchable extension', $e->getMessage());
		}
	}
}






