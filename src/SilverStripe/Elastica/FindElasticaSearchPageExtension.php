<?php

class FindElasticaSearchPageExtension extends Extension {

	public static $fixture_file = 'elastica/tests/ElasticaTest.yml';

	private $_CachedLastEdited = null;


	public function SearchPageURI($identifier) {
		$result = '';

		$searchPage = $this->getSearchPage($identifier);

		if ($searchPage) {
			$result = $searchPage->AbsoluteLink();
		}
		return $result;
	}


	public function SearchPageForm($identifier, $buttonTextOverride = null) {
		$result = null;

		$searchPage = $this->getSearchPage($identifier);

		if ($searchPage) {
			$result = $searchPage->SearchForm($buttonTextOverride);
		}
		return $result;
	}


	public function getSearchPage($identifier) {
		if (!isset($this->_CachedLastEdited)) {
			$this->_CachedLastEdited = ElasticSearchPage::get()->max('LastEdited');
		}
		$ck = $this->_CachedLastEdited;
		$ck = str_replace(' ', '_', $ck);
		$ck = str_replace(':', '_', $ck);
		$ck = str_replace('-', '_', $ck);

		$cache = SS_Cache::factory('searchpagecache');
		$searchPage = null;
		$cachekeyname = 'searchpageuri'.$identifier.$this->owner->Locale.$ck;

		if(!($searchPage = unserialize($cache->load($cachekeyname)))) {
			$searchPage = ElasticSearchPage::get()->filter('Identifier',$identifier)->first();
			$cache->save(serialize($searchPage), $cachekeyname);
		}
		return $searchPage;
	}
}
