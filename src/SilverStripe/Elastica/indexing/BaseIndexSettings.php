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
	protected $stopWords = array();

	/**
	 * Synonyms for this index in form of CSV terms => actual term
	 * @var array
	 */
	private $synonyms = array();


	/*
	Filters added by the language specific settings
	 */
	private $filters = array();

	/*
	Analyzers added by the language specific settings
	 */
	private $analyzers = array();


	protected $stopWordFilter = null;


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


	/**
	 * Add a filter, expressed as an array
	 * @param string $name The name of the filter
	 * @param array $properties The filter modelled as an array
	 */
	public function addFilter($name, $properties) {
		$this->filters[$name] = $properties;
	}


	/**
	 * Add an analyzer, expressed as an array
	 * @param string $name       The name of the analyzer
	 * @param array $properties The analyzer modelled as an array
	 */
	public function addAnalyzer($name, $properties) {
		$this->analyzers[$name] = $properties;
	}


	/*
	Generate an Elasticsearch config representing the configurations previously set.
	 */
	public function generateConfig() {
		$settings = array();
		$settings['analysis'] = array();

		// create redefined filters in this array, e.g. tweaked stopwords

		$properties = array();
		$analyzerNotStemmed = array();
		$analyzerFolded = array();

		$analyzerNotStemmed['type'] = 'custom';

		$this->addFilter('no_single_chars', array(
			'type' => 'length',
			'min' => 2
		));


/*
		if (sizeof($this->stopWords) > 0) {
			$stopwordFilter = array();
			$stopwordFilter['type'] = 'stop';
			$stopwordFilter['stopwords'] = $this->stopWords;
			$this->filters['stopword_filter'] = $stopwordFilter;
		}
*/

		//$analyzerStemmed['char_filter'] = array('html_strip');
		$filterNames = array_keys($this->filters);

		//$analyzerNotStemmed['char_filter'] = array('html_strip');
		$analyzerNotStemmed['tokenizer'] = 'uax_url_email';
		array_push($filterNames, 'lowercase');
		$analyzerNotStemmed['filter'] = array('no_single_chars', 'lowercase', $this->stopWordFilter);

		//Autocomplete filter
		/*
		"autocomplete": {
            "type":      "custom",
            "tokenizer": "standard",
            "filter": [
                "lowercase",
                "autocomplete_filter"
            ]
        }
		 */
		$this->addFilter('autocomplete', array(
			'type' => 'nGram',
			'min_gram' => 2,
			'max_gram' => 20,
			'token_chars' => array('letter', 'digit','punctuation', 'symbol')
		));

		$this->addAnalyzer('autocomplete_index_analyzer',array(
			'type' => 'custom',
			'tokenizer' => 'whitespace',
			'filter' => array(
				'lowercase',
				'asciifolding',
				'autocomplete'
			)
		));

		$this->addAnalyzer('autocomplete_search_analyzer',array(
			'type' => 'custom',
			'tokenizer' => 'whitespace',
			'filter' => array(
				'lowercase',
				'asciifolding'
			)
		));

		//Folded analyzer
		$analyzerFolded['tokenizer'] = 'uax_url_email';
		$analyzerFolded['filters'] = array('lowercase', 'asciifolding');



		//HTML needs to have been removed for all indexes
		//stemmed is set by the specific language provider
		$this->analyzers['unstemmed'] = $analyzerNotStemmed;


		if ($this->foldedAscii) {
			$analyzers['folded'] = $analyzerFolded;
		}

        //Store bigrams in the index, namely pairs of words
		$this->addFilter('filter_shingle', array(
			'type' => 'shingle',
			'min_shingle_size' => 2,
			'max_shingle_size' => 2,
			'output_unigrams' => false
		));

		//See https://www.elastic.co/blog/searching-with-shingles?q=shingle for details
		$this->addAnalyzer('shingles', array(
			// Ensure URLs happily tokenized
			'tokenizer' => 'uax_url_email',
			'filter' => array("lowercase", "filter_shingle"),
			'type' => 'custom'
		));

		$settings['analysis']['analyzer'] = $this->analyzers;
		$settings['analysis']['filter'] = $this->filters;


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
		//

		print_r($properties);
		return $properties;
	}
}
