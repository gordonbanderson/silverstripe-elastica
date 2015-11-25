<?php
interface ElasticaIndexingHelperInterface {

		public function updateElasticsearchMapping(\Elastica\Type\Mapping $mapping);


		public function updateElasticsearchDocument(\Elastica\Document $document);


		public function updateElasticHTMLFields(array $htmlFields);
}
