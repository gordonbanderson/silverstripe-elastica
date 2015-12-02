<?php
namespace SilverStripe\Elastica;

class RangedAggregation {

	private static $ranged_aggregations = array();


	/**
	 * @param string $title
	 * @param string $field
	 */
	public function __construct($title, $field) {
		$this->Title = $title;
		$this->Range = new \Elastica\Aggregation\Range($title);
		$this->Range->setField($field);
		self::$ranged_aggregations[$title] = $this;;
	}


	/**
	 * @param double $from
	 * @param double $to
	 * @param string $name
	 */
	public function addRange($from, $to, $name) {
		$this->Range->addRange($from,$to,$name);
	}


	public function getRangeAgg() {
		return $this->Range;
	}


	public function getFilter($chosenName) {
		$rangeArray = $this->Range->toArray()['range']['ranges'];
		$result = null;
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
		        $result = $filter;
			}
		}

		return $result;
	}


	public static function getByTitle($title) {
		return self::$ranged_aggregations[$title];
	}


	public static function getTitles() {
		return array_keys(self::$ranged_aggregations);
	}
}
