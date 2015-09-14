<?php

/**
*
*/
class GermanIndexSettings  extends BaseIndexSettings {

	public function __construct() {
		$this->setStopWords('_german_');
		$this->setAsciiFolding(true);
		$this->setAnalyzerType('german');
	}

}
