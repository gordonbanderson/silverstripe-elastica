<?php

interface ElasticaQueryManipulator {
	/**
	 * Alter the query or add to it, perhaps for example adding aggregation
	 * @param  Elastic\Query &$query query object from Elastica
	 * @return [type]         [description]
	 */
	public function augmentQuery(&$query);
}
