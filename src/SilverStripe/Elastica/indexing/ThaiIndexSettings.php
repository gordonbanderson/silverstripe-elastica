<?php

/**
*
*/
class ThaiIndexSettings extends BaseIndexSettings {

	public function __construct() {
		$this->setStopWords('_thai_');
		$this->setAsciiFolding(false);
		//$this->setAnalyzerType('thai');
	}
}
