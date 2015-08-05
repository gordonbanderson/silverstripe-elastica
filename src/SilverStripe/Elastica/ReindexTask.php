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
		$startTime = microtime(true);
		$message = function ($content) {
			print(\Director::is_cli() ? "$content\n" : "<p>$content</p>");
		};

		$message('Defining the mappings');
		$this->service->define();

		$message('Refreshing the index');
		$this->service->refresh();

		// display indexing speed stats
		$endTime = microtime(true);
		$elapsed = $endTime-$startTime;
		$perSecond = Searchable::$index_ctr / $elapsed;
		$info = "\nReindexing completed in ".round($elapsed,2)." seconds ";
		$info .= "at ".round($perSecond,2)." documents per second";
		$message($info);
	}

}
