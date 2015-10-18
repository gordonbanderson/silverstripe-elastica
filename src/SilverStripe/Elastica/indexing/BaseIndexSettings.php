<?php

/**
*
* Synonyms
* https://www.elastic.co/guide/en/elasticsearch/reference/current/analysis-synonym-tokenfilter.html
*
* ASCII folding
* https://www.elastic.co/guide/en/elasticsearch/guide/current/asciifolding-token-filter.html
*
* Snowball
* https://www.elastic.co/guide/en/elasticsearch/reference/current/analysis-snowball-analyzer.html
*
* Thai tokenizer
* https://www.elastic.co/guide/en/elasticsearch/reference/current/analysis-thai-tokenizer.html
*
* Reverser
* https://www.elastic.co/guide/en/elasticsearch/reference/current/analysis-reverse-tokenfilter.html
*
* Elisions, possibly suitable for French
* https://www.elastic.co/guide/en/elasticsearch/reference/current/analysis-elision-tokenfilter.html
* Common grams
* https://www.elastic.co/guide/en/elasticsearch/reference/current/analysis-common-grams-tokenfilter.html
*
* This page has a long list
* https://www.elastic.co/guide/en/elasticsearch/reference/current/analysis-lang-analyzer.html#german-analyzer
*
* Boost weight and mix of stem/unstemmed
* https://www.elastic.co/guide/en/elasticsearch/guide/current/most-fields.html
*
* Extend this class to create your own index settings
*
*/
class BaseIndexSettings {

	/**
	 * If true add a field called folded with likes of está converted to esta
	 * @var boolean
	 */
	private $foldedAscii = false;

	/*
	Stopwords for this index
	 */
	private $stopWords = array();

	/**
	 * Synonyms for this index in form of CSV terms => actual term
	 * @var array
	 */
	private $synonyms = array();


	/**
	 * The base type of the analyzer, e.g. german, french etc
	 * @var string
	 */
	private $analyzerType = 'english';

	/**
	 * Set to true to add an extra field containing a folded version of terms,
	 * i.e. not accents on the letters
	 * @param boolean $newFolding true for an extra field with no accents
	 */
	public function setAsciiFolding($newFolding) {
		$this->foldedAscii = $newFolding;
	}


	public function getAsciiFolding() {
		return $this->foldedAscii;
	}


	/**
	 * NOTE: Test with _german_ or _english_
	 * Set the stopwords for this index
	 * @param array or string $newStopWords An array of stopwords or a CSV string of stopwords
	 */
	public function setStopwords($newStopWords) {
		if (is_array($newStopWords)) {
			$this->stopWords = $newStopWords;
		} else if (is_string($newStopWords)) {
			$this->stopWords = explode(',', $newStopWords);
		} else {
			throw new Exception("ERROR: Stopwords must be a string or an array");
		}
	}


	/*
	Accessor for stopwords
	 */
	public function getStopwords() {
		return $this->stopWords;
	}

	/*
	Valid values are arabic, armenian, basque, brazilian, bulgarian, catalan, chinese, cjk, czech,
	 danish, dutch, english, finnish, french, galician, german, greek, hindi, hungarian,
	 indonesian, irish, italian, latvian, norwegian, persian, portuguese, romanian, russian,
	 sorani, spanish, swedish, turkish, thai.

	 */
	public function setAnalyzerType($newAnalayzerType) {
		$this->analyzerType = $newAnalayzerType;
	}


	/*
	Generate an Elasticsearch config representing the configurations previously set.
	 */
	public function generateConfig() {
		$settings = array();
		$settings['analysis'] = array();

		// create redefined filters in this array, e.g. tweaked stopwords
		$filters = array();

		$properties = array();
		$analyzers = array();
		$analyzerStemmed = array();
		$analyzerNotStemmed = array();
		$analyzerFolded = array();

		$analyzerStemmed['type'] = $this->analyzerType;
		$analyzerNotStemmed['type'] = 'custom';



		if (sizeof($this->stopWords) > 0) {
			$stopwordFilter = array();
			$stopwordFilter['type'] = 'stop';
			$stopwordFilter['stopwords'] = $this->stopWords;
			$filters['stopword_filter'] = $stopwordFilter;
		}

		//$analyzerStemmed['char_filter'] = array('html_strip');
		$analyzerStemmed['tokenizer'] = array('standard');
		$filterNames = array_keys($filters);

		$analyzerStemmed['filter'] =  $filterNames;

		//$analyzerNotStemmed['char_filter'] = array('html_strip');
		$analyzerNotStemmed['tokenizer'] = 'standard';
		array_push($filterNames, 'lowercase');
		$analyzerNotStemmed['filter'] = $filterNames;

		//Folded analyzer
		$analyzerFolded['tokenizer'] = 'standard';
		$analyzerFolded['filters'] = array('lowercase', 'asciifolding');



		//HTML needs removed for all indexes
		$analyzers['stemmed'] = $analyzerStemmed;
		$analyzers['unstemmed'] = $analyzerNotStemmed;
		if ($this->foldedAscii) {
			$analyzers['folded'] = $analyzerFolded;
		}

		$settings['analysis']['analyzer'] = $analyzers;
		$settings['analysis']['filter'] = $filters;


		$properties['index'] = $settings;

		/*

		if ($this->foldedAscii) {
			$foldingFilter = array('my_ascii_folding' => array(
				"type" => "asciifolding",
				"preserve_original" => 'true'
			));
			array_push($filters, $foldingFilter);
		}
		*/


/*
		$json = '{
		  "settings": {
		    "analysis": {
		      "analyzer": {
		        "stemmed": {
		          "type": "english",
		          "stem_exclusion": [ "organization", "organizations" ],
		          "stopwords": [
		            "a", "an", "and", "are", "as", "at", "be", "but", "by", "for",
		            "if", "in", "into", "is", "it", "of", "on", "or", "such", "that",
		            "the", "their", "then", "there", "these", "they", "this", "to",
		            "was", "will", "with"
		          ]
		        }
		      }
		    }
		  }
		}';
		*/
		//$this->extend('alterIndexingProperties', $properties);
		//
		return $properties;
	}
}
