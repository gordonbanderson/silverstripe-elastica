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


  private function getSearchPage($identifier) {
    $ck = $this->owner->CacheKey('searchpage2', 'SearchPage');
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
