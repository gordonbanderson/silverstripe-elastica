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

		$tab = $this->checkTabExists($fields,'Main');

		//Check fields
		$nf = $this->checkFieldExists($tab, 'Name');
		$this->assertTrue($nf->isDisabled());
	}

}
