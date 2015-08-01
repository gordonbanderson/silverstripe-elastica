<?php
/**
 * Standard basic search form which conducts a fulltext search on all {@link SiteTree}
 * objects.
 *
 * If multilingual content is enabled through the {@link Translatable} extension,
 * only pages the currently set language on the holder for this searchform are found.
 * The language is set through a hidden field in the form, which is prepoluated
 * with {@link Translatable::get_current_locale()} when then form is constructed.
 *
 * @see Use ModelController and SearchContext for a more generic search implementation based around DataObject
 * @package cms
 * @subpackage search
 */

use Elastica\Document;
use Elastica\Query;
use \SilverStripe\Elastica\ResultList;
use Elastica\Query\QueryString;

class ElasticSearchForm extends Form {

	/**
	 * @var int $pageLength How many results are shown per page.
	 * Relies on pagination being implemented in the search results template.
	 */
	protected $pageLength = 10;


	/**
	 * List of types to search for, default (blank) returns all
	 * @var string
	 */
	private $types = '';


	/**
	 * Set a new list of types (SilverStripe classes) to search for
	 * @param string $newTypes comma separated list of types to search for
	 */
	public function setTypes($newTypes) {
		$this->types = $newTypes;
	}


	/**
	 *
	 * @param Controller $controller
	 * @param string $name The name of the form (used in URL addressing)
	 * @param FieldList $fields Optional, defaults to a single field named "Search". Search logic needs to be customized
	 *  if fields are added to the form.
	 * @param FieldList $actions Optional, defaults to a single field named "Go".
	 */
	public function __construct($controller, $name, $fields = null, $actions = null) {
		if(!$fields) {
			$fields = new FieldList(
				new TextField('Search', _t('SearchForm.SEARCH', 'SearchPPPP')
			));
		}

		if(class_exists('Translatable') && singleton('SiteTree')->hasExtension('Translatable')) {
			$fields->push(new HiddenField('searchlocale', 'searchlocale', Translatable::get_current_locale()));
		}

		if(!$actions) {
			$actions = new FieldList(
				new FormAction("getResults", _t('SearchForm.GO', 'Go'))
			);
		}

		parent::__construct($controller, $name, $fields, $actions);

		$this->setFormMethod('get');

		$this->disableSecurityToken();
	}


	/**
	 * Return a rendered version of this form.
	 *
	 * This is returned when you access a form as $FormObject rather
	 * than <% with FormObject %>
	 */
	public function forTemplate() {
		$return = $this->renderWith(array_merge(
			(array)$this->getTemplate(),
			array('ElasticaSearchForm', 'Form')
		));

		// Now that we're rendered, clear message
		$this->clearMessage();

		return $return;
	}


	/**
	 * Return dataObjectSet of the results using $_REQUEST to get info from form.
	 * Wraps around {@link searchEngine()}.
	 *
	 * @param int $pageLength DEPRECATED 2.3 Use SearchForm->pageLength
	 * @param array $data Request data as an associative array. Should contain at least a key 'Search' with all searched keywords.
	 * @return SS_List
	 */
	public function getResults($pageLength = null, $data = null){
		$start = isset($_GET['start']) ? (int)$_GET['start'] : 0;
		$query = new Query($_GET['Search']);


		$queryString = new QueryString($_GET['Search']);
		$query = new Query($queryString);



		$index = Injector::inst()->create('SilverStripe\Elastica\ElasticaService');
		$results = new ResultList($index, $query);
		$results->setTypes($this->types);

		$results->query->setLimit($this->pageLength);
		$results->query->setFrom($start);

		return $results;
	}


	protected function addStarsToKeywords($keywords) {
		if(!trim($keywords)) return "";
		// Add * to each keyword
		$splitWords = preg_split("/ +/" , trim($keywords));
		while(list($i,$word) = each($splitWords)) {
			if($word[0] == '"') {
				while(list($i,$subword) = each($splitWords)) {
					$word .= ' ' . $subword;
					if(substr($subword,-1) == '"') break;
				}
			} else {
				$word .= '*';
			}
			$newWords[] = $word;
		}
		return implode(" ", $newWords);
	}

	/**
	 * Get the search query for display in a "You searched for ..." sentence.
	 *
	 * @param array $data
	 * @return string
	 */
	public function getSearchQuery($data = null) {
		// legacy usage: $data was defaulting to $_REQUEST, parameter not passed in doc.silverstripe.org tutorials
		if(!isset($data)) $data = $_REQUEST;

		// The form could be rendered without the search being done, so check for that.
		if (isset($data['Search'])) return Convert::raw2xml($data['Search']);
	}

	/**
	 * Set the maximum number of records shown on each page.
	 *
	 * @param int $length
	 */
	public function setPageLength($length) {
		$this->pageLength = $length;
	}

	/**
	 * @return int
	 */
	public function getPageLength() {
		return $this->pageLength;
	}

}


