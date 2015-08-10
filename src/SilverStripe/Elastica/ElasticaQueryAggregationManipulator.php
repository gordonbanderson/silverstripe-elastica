<?php

interface ElasticaQueryAggregationManipulator {
	/**
	 * Alter the query or add to it, perhaps for example adding aggregation
	 * @param  Elastic\Query &$query query object from Elastica
	 * @return [type]         [description]
	 */
	public function augmentQuery(&$query);

	/**
	 * Manipulate the array of aggregations prior to rendering them
	 * in a template.
	 * @param  [type] &$aggs [description]
	 * @return [type]        [description]
	 */
	public function manipulateAggregation(&$aggs);
}
