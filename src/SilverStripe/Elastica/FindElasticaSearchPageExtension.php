<?php

class FindElasticaSearchPageExtension extends Extension {

	function SearchPageURI($identifier) {
		$result = '';

		$searchPage = $this->getSearchPage($identifier);

		if ($searchPage) {
			$result = $searchPage->AbsoluteLink();
		}
		return $result;
	}


	function SearchPageForm($identifier, $buttonTextOverride = null) {
		$result = null;

		$searchPage = $this->getSearchPage($identifier);

		if ($searchPage) {
			$result = $searchPage->SearchForm($buttonTextOverride);
		}
		return $result;
	}


	private function getSearchPage($identifier) {
		if (!isset($this->_CachedLastEdited)) {
			$this->_CachedLastEdited = ElasticSearchPage::get()->max('LastEdited');
		}
		$ck = $this->_CachedLastEdited;
		$ck = str_replace(' ', '_', $ck);
		$ck = str_replace(':', '_', $ck);
		$ck = str_replace('-', '_', $ck);

		$cache = SS_Cache::factory('searchpagecache');
		$searchPage = null;
		$cachekeyname = 'searchpageuri'.$this->owner->Locale.$ck;

		if(!($searchPage = unserialize($cache->load($cachekeyname)))) {
			$searchPage = ElasticSearchPage::get()->filter('Identifier',$identifier)->first();
			$cache->save(serialize($searchPage), $cachekeyname);
		}
		return $searchPage;
	}
}
