<?php

namespace SilverStripe\Elastica;

/**
 * Defines and refreshes the elastic search index.
 */
class DeleteIndexTask extends \BuildTask
{
    protected $title = 'Elastic Search Index Deletion';

    protected $description = 'Deletes the configured elastic search index';

    /**
     * @var ElasticaService
     */
    private $service;

    public function __construct(ElasticaService $service)
    {
        $this->service = $service;
    }

    /**
     * Execute the task to delete the currently configured index.
     */
    public function run($request)
    {
        $message = ElasticaUtil::getPrinter();

        $this->service->define();
        $this->service->reset();
        $message('Successfully deleted configured index');
    }
}
