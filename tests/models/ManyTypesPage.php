<?php

/**
 * @package elastica
 * @subpackage tests
 */
class ManyTypesPage extends Page {
	private static $searchable_fields = array('BooleanField', 'BooleanField', 'BooleanField',
		'CurrencyField', 'DateField', 'DecimalField', 'EnumField', 'HTMLTextField',
		'HTMLVarcharField', 'IntField', 'PercentageField', 'SS_DatetimeField', 'TextField',
		'TimeField');

	private static $db = array(
		'BooleanField' => 'Boolean',
		'CurrencyField' => 'Currency',
		'DateField' => 'Date',
		'DecimalField' => 'Decimal',
		'HTMLTextField' => 'HTMLText',
		'HTMLVarcharField' => 'HTMLVarchar',
		'IntField' => 'Int',
		'PercentageField' => 'Percentage',
		'SS_DatetimeField' => 'SS_Datetime',
		'TextField' => 'Text',
		'TimeField' => 'Time'
	);

}

/**
 * @package elastica
 * @subpackage tests
 */
class ManyTypesPage_Controller extends Controller implements TestOnly {
}

