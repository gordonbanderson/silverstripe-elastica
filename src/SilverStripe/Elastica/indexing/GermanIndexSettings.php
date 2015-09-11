<?php

/**
*
*/
class GermanIndexSettings  extends AbstractIndexSettings {

	public function __construct() {
		$this->setStopWords('_german_');
		$this->setAsciiFolding(true);
		$this->setAnalyzerType('german');
	}

}
