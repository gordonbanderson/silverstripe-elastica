<?php

interface ElasticaSearchHelperInterface {
	/**
	 * Alter the query or add to it, perhaps for example adding aggregation
	 * @param  Elastic\Query &$query query object from Elastica
	 * @return [type]         [description]
	 */
	public function augmentQuery(&$query);

	/**
	 * Update filters, perhaps remaps them, prior to performing a search.
	 * This allows for aggregation values to be updated prior to rendering.
	 * @param  array &$filters array of key/value pairs for query filtering
	 */
	public function updateFilters(&$filters);

	/**
	 * Manipulate the array of aggregations post search butprior to rendering
	 * them in a template.
	 * @param  [type] &$aggs [description]
	 * @return [type]        [description]
	 */
	public function updateAggregation(&$aggs);
}
