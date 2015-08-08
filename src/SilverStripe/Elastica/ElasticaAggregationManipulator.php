<?php

interface ElasticaAggregationManipulator {

	/**
	 * Manipulate the array of aggregations prior to rendering them
	 * in a template.
	 * @param  [type] &$aggs [description]
	 * @return [type]        [description]
	 */
	public function manipulateAggregation(&$aggs);
}
