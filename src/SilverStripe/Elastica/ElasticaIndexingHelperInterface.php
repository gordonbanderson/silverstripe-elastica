<?php
interface ElasticaIndexingHelperInterface {

		public static function updateElasticsearchMapping(\Elastica\Type\Mapping $mapping);


		public function updateElasticsearchDocument(\Elastica\Document $document);

}
