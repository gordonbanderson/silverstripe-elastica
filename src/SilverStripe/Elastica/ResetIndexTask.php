<?php

namespace SilverStripe\Elastica;

/**
 * Defines and refreshes the elastic search index.
 */
class ResetIndexTask extends \BuildTask {

	protected $title = 'Elastic Search Reset Index';

	protected $description = 'Resets/clears the configured elastic search index';

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

		$message('Defining the mappings');
		$this->service->define();

		$message('Resetting the index');
		$this->service->reset();
	}

}
