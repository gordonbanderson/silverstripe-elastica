<?php
namespace SilverStripe\Elastica;

class ClearElasticFieldCacheExtension extends \Extension {
	public function onAfterInit() {
		$cache = \SS_Cache::factory('elasticsearch');
		$cache->clean();
	}
}
