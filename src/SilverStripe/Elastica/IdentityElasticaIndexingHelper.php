<?php

namespace SilverStripe\Elastica;

class IdentityElasticaIndexingHelper extends Extension implements ElasticaIndexingHelperInterface
{
    public function updateElasticsearchMapping(\Elastica\Type\Mapping $mapping)
    {
        return $mapping;
    }

    public function updateElasticsearchDocument(\Elastica\Document $document)
    {
        return $document;
    }

    public function updateElasticHTMLFields(array $htmlFields)
    {
        return $htmlFields;
    }
}
