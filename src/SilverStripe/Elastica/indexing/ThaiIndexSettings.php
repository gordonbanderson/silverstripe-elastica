<?php

/**
*
*/
class ThaiIndexSettings extends AbstractIndexSettings {

	public function __construct() {
		$this->setStopWords('_thai_');
		$this->setAsciiFolding(true);
		$this->setAnalyzerType('thai');
	}
}
