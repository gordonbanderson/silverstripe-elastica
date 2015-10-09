<?php
namespace SilverStripe\Elastica;

class RangedAggregation {

	private static $ranged_aggregations = array();


	public function __construct($title, $field) {
		$this->Title = $title;
		$this->Range = new \Elastica\Aggregation\Range($title);
		$this->Range->setField($field);
		self::$ranged_aggregations[$title] = $this;;
	}


	public function addRange($from, $to, $name) {
		$this->Range->addRange($from,$to,$name);
	}


	public function getRangeAgg() {
		return $this->Range;
	}


	public function getFilter($chosenName) {
		$rangeArray = $this->Range->toArray()['range']['ranges'];
		foreach ($rangeArray as $range) {
			if ($range['key'] === $chosenName) {
				$from = null;
				$to = null;
				if (isset($range['from'])) {
					$from = $range['from'];
				}
				if (isset($range['to'])) {
					$to = $range['to'];
				}
				$rangeFilter = array('gte' => (string)$from, 'lt' => (string)$to);
		        $filter = new \Elastica\Filter\Range('AspectRatio', $rangeFilter);
		        return $filter;
			}
		}
	}


	public static function getByTitle($title) {
		return self::$ranged_aggregations[$title];
	}


	public static function getTitles() {
		return array_keys(self::$ranged_aggregations);
	}
}
