<?php

/**
 * Teste the functionality of the Searchable extension
 * @package elastica
 */
class SearchableFieldTest extends ElasticsearchBaseTest {

	public function testCMSFields() {
		$sf = new SearchableField();
		$sf->Name = 'TestField';
		$sf->ClazzName = 'TestClazz';
		$sf->write();

		$fields = $sf->getCMSFields();

		//Check tabs existence
		$tab = $fields->findOrMakeTab('Root.Main');
		$this->assertEquals('Main', $tab->getName());
		$this->assertEquals('Root_Main', $tab->id());

		//Check fields
		$nf = $tab->fieldByName('Name');
		$this->assertTrue($nf != null);
		$this->assertTrue($nf->isDisabled());
	}

}
