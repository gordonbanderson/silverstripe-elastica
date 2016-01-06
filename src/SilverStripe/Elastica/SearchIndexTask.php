<?php

namespace SilverStripe\Elastica;

/**
 * Defines and refreshes the elastic search index.
 */
class SearchIndexTask extends \BuildTask
{
    protected $title = 'Elastic Search Reindex';

    protected $description = 'Searches the elastic search index';

    /**
     * @var ElasticaService
     */
    private $service;

    public function __construct(ElasticaService $service)
    {
        $this->service = $service;
    }

    public function run($request)
    {
        $message = ElasticaUtil::getPrinter();

        $locales = array();

        if ($this->locale == null) {
            if (class_exists('Translatable') && \SiteTree::has_extension('Translatable')) {
                $this->locale = \Translatable::get_current_locale();
            } else {
                foreach (\Translatable::get_existing_content_languages('SiteTree') as $code => $val) {
                    array_push($locales, $code);
                }
            }
        }

        // search SiteTree showing highlights
        $query = $request->getVar('q');
        $es = new \ElasticaSearcher();
        $es->setStart(0);
        $es->setPageLength(20);
        $es->addFilter('IsInSiteTree', true);
        $results = $es->search($query);
        foreach ($results as $result) {
            $title = '['.$result->ClassName.', '.$result->ID.']  ';
            $title .= $result->Title;
            $message($title);
            if ($result->SearchHighlightsByField->Content) {
                foreach ($result->SearchHighlightsByField->Content as $highlight) {
                    $message('- '.$highlight->Snippet);
                }
            }

            echo "\n\n";
        }
    }
}
