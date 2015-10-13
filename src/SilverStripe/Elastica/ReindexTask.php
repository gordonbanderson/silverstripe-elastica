<?php

namespace SilverStripe\Elastica;

/**
 * Defines and refreshes the elastic search index.
 */
class ReindexTask extends \BuildTask {

	protected $title = 'Elastic Search Reindex';

	protected $description = 'Refreshes the elastic search index';

	/**
	 * @var ElasticaService
	 */
	private $service;

	public function __construct(ElasticaService $service) {
		$this->service = $service;
	}

	public function run($request) {
		$message = function ($content) {
			print(\Director::is_cli() ? "$content\n" : "<p>$content</p>");
		};

		$locales = array();
		if (!class_exists('Translatable')) {
			// if no translatable we only have the default locale
			array_push($locales, \i18n::default_locale());
		} else {
			foreach (\Translatable::get_existing_content_languages('SiteTree') as $code => $val) {
				array_push($locales, $code);
			}
		}

		// now iterate all the locales indexing each locale in turn using it's owner index settings
		foreach ($locales as $locale) {
			Searchable::$index_ctr = 0;
			$message('Indexing locale '.$locale);

			if (class_exists('Translatable')) {
				\Translatable::set_current_locale($locale);
			}

			$this->service->setLocale($locale);

			$this->service->startBulkIndex();

			$message('Defining the mappings');
			$this->service->define();

			// only measure index time
			$startTime = microtime(true);

			$message('Refreshing the index');
			$this->service->refresh();
			$this->service->endBulkIndex();
			// display indexing speed stats
			$endTime = microtime(true);
			$elapsed = $endTime-$startTime;
			$perSecond = Searchable::$index_ctr / $elapsed;
			$info = "\nReindexing $locale completed \n ".Searchable::$index_ctr." docs in ".round($elapsed,2)." seconds ";
			$info .= "at ".round($perSecond,2)." documents per second\n\n";
			$message($info);
		}


	}

}
