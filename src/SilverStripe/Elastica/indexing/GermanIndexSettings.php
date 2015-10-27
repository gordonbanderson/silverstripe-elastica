<?php

/**
*
*/
class GermanIndexSettings  extends BaseIndexSettings {

	public function __construct() {
		$this->setStopWords('_german_');
		$this->setAsciiFolding(true);

		$this->addFilter('german_stop', array(
			'type' => 'stop',
			'stopwords' => $this->stopWords
		));

		//Use either of the following stemmers
		$this->addFilter('german_stemmer', array(
			'type' => 'stemmer',
			'keywords' => "english"
		));

		$this->addFilter('german_snowball', array(
			'type' => 'snowball',
			'language' => 'German'
		));

		$germanWithURLs = array(
			'tokenizer' => 'uax_url_email',
			//'filter' => array('english_possessive_stemmer', 'lowercase', 'english_stop', /*'english_keywords',*/ 'english_stemmer' ),
			'filter' => array('no_single_chars', 'german_snowball', 'lowercase', 'german_stop' ),
			'type' => 'custom'
		);


		$this->addAnalyzer('stemmed', $germanWithURLs);

	}

}
