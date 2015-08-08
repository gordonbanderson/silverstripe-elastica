<?php

namespace SilverStripe\Elastica;

use Elastica\Index;
use Elastica\Query;

/**
 * A list wrapper around the results from a query. Note that not all operations are implemented.
 */
class ResultList extends \ViewableData implements \SS_Limitable, \SS_List {

    /**
     * @var \Elastica\Index
     */
    private $index;

    /**
     * @var \Elastica\Query
     */
	private $query;

	/**
	 * List of types to search for, default (blank) returns all
	 * @var string
	 */

	private $types = '';

	/**
	 * An array list of aggregations from this search
	 * @var ArrayList
	 */
	private $aggregations;


	public function __construct(Index $index, Query $query) {
		$this->index = $index;
		$this->query = $query;
	}

	public function __clone() {
		$this->query = clone $this->query;
	}

	/**
	 * @return \Elastica\Index
	 */
	public function getIndex() {
		return $this->index;
	}

	/**
	 * Set a new list of types (SilverStripe classes) to search for
	 * @param string $newTypes comma separated list of types to search for
	 */
	public function setTypes($newTypes) {
		$this->types = $newTypes;
	}

	/**
	 * @return \Elastica\Query
	 */
	public function getQuery() {
		return $this->query;
	}


	/**
	 * Get the aggregation results for this query.  Should only be called
	 * after $this->getResults() has been executed.
	 * Note this will be an empty array list if there is no aggregation
	 *
	 * @return ArrayList ArrayList of the aggregated results for this query
	 */
	public function getAggregations() {
		return $this->aggregations;
	}

	/**
	 * @return array
	 */
	public function getResults() {
		if (!$this->_cachedResults) {
			// get the ElasticaResultSet initally to obtain details
			// 'index' is actually elastica service, bad naming of vars
			$ers = $this->index->search($this->query,$this->types);
			$this->TotalItems = $ers->getTotalHits();
			$this->TotalTime = $ers->getTotalTime();
			$this->_cachedResults = $ers->getResults();

			// make the aggregations available to the templating, title casing
			// to be consistent with normal templating conventions
			$aggs = $ers->getAggregations();

			// store aggregations already selected
			$selectedAggregations = array();

			// optionally remap keys and store chosen aggregations from get params
			if (isset($this->AggregationManipulator)) {
				$manipulator = \Injector::inst()->create($this->AggregationManipulator);
				$manipulator->manipulateAggregation($aggs);

				$keys = array_keys($aggs);
				foreach ($keys as $key) {
					if(isset($_GET[$key])) {
						$selectedAggregations[$key] = $_GET[$key];
					}
				}
			}

			$aggsTemplate = new \ArrayList();

			// Convert the buckets into a form suitable for SilverStripe templates
			$q = isset($_GET['q']) ? $_GET['q'] : '';
			$start = isset($_GET['start']) ? (int)$_GET['start'] : 0;

			// get the base URL for the current facets selected
			$baseURL = \Controller::curr()->Link().'?';
			$prefixAmp = false;
			if ($q !== '') {
				$baseURL .= 'q='.urlencode($q);
				$prefixAmp = true;
			}

			// now add the selected facets
			foreach ($selectedAggregations as $key => $value) {
				if ($prefixAmp) {
					$baseURL .= '&';
				} else {
					$prefixAmp = true;
				}
				$baseURL .= $key.'='.urlencode($value);
			}

			foreach (array_keys($aggs) as $key) {
				$aggDO = new \DataObject();
				//FIXME - Camel case separate here
				$aggDO->Name = $key;
				// now the buckets
				if (isset($aggs[$key]['buckets'])) {
					$bucketsAL = new \ArrayList();
					foreach ($aggs[$key]['buckets'] as $value) {
						$ct = new \DataObject();
						$ct->Key = $value['key'];
						$ct->DocumentCount = $value['doc_count'];
						$query[$key] = $value;
						if ($prefixAmp) {
							$url = $baseURL.'&';
						} else {
							$url = $baseURL;
							$prefixAmp = true;
						}
						$url .= $key .'='.urlencode($value['key']);
						$ct->URL = $url;

						// check if currently selected
						if (isset($selectedAggregations[$key])) {
							if ($selectedAggregations[$key] === (string)$value['key']) {
								$ct->IsSelected = true;
							}
						}

						$bucketsAL->push($ct);
					}
					$aggDO->Buckets = $bucketsAL;
				}
				$aggsTemplate->push($aggDO);
			}
			$this->aggregations = $aggsTemplate;
		}
		return $this->_cachedResults;
	}


	public function getTotalItems() {
		$this->getResults();
		return $this->TotalItems;
	}


	public function getTotalTime() {
		return $this->TotalTime;
	}

	public function getIterator() {
		//return new \ArrayIterator($this->toArray());
		return $this->toArrayList()->getIterator();
	}

	public function limit($limit, $offset = 0) {
		$list = clone $this;

		$list->getQuery()->setSize($limit);
		$list->getQuery()->setFrom($offset);

		return $list;
	}

	/**
	 * Converts results of type {@link \Elastica\Result}
	 * into their respective {@link DataObject} counterparts.
	 *
	 * @return array DataObject[]
	 */
	public function toArray() {
		$result = array();

		/** @var $found \Elastica\Result[] */
		$found = $this->getResults();
		$needed = array();
		$retrieved = array();

		foreach ($found as $item) {
			$type = $item->getType();

			if (!array_key_exists($type, $needed)) {
				$needed[$type] = array($item->getId());
				$retrieved[$type] = array();
			} else {
				$needed[$type][] = $item->getId();
			}
		}

		foreach ($needed as $class => $ids) {
			foreach ($class::get()->byIDs($ids) as $record) {
				$retrieved[$class][$record->ID] = $record;
			}
		}

		// Title and Link are special cases
		$ignore = array('Title', 'Link');


		foreach ($found as $item) {
			// Safeguards against indexed items which might no longer be in the DB
			if(array_key_exists($item->getId(), $retrieved[$item->getType()])) {

                $data_object = $retrieved[$item->getType()][$item->getId()];
                $data_object->setElasticaResult($item);
                $highlights = $item->getHighlights();
                $snippets = new \ArrayList();
                $namedSnippets = new \ArrayList();
                foreach (array_keys($highlights) as $fieldName) {
                	$fieldSnippets = new \ArrayList();

                	foreach ($highlights[$fieldName] as $snippet) {
                		$do = new \DataObject();
                		$do->Snippet = $snippet;

                		// skip title and link in the summary of highlights
                		if (!in_array($fieldName, $ignore)) {
                			$snippets->push($do);
                		}

                		$fieldSnippets->push($do);
                	}

                	if ($fieldSnippets->count() > 0) {
                		$namedSnippets->$fieldName = $fieldSnippets;
                	}
                }
                $data_object->SearchHighlights = $snippets;
                $data_object->SearchHighlightsByField = $namedSnippets;
				$result[] = $data_object;

			}
		}


		return $result;
	}

	public function toArrayList() {
		return new \ArrayList($this->toArray());
	}

	public function toNestedArray() {
		$result = array();

		foreach ($this as $record) {
			$result[] = $record->toMap();
		}

		return $result;
	}

	public function first() {
		// TODO
	}

	public function last() {
		// TODO: Implement last() method.
	}

	public function map($key = 'ID', $title = 'Title') {
		return $this->toArrayList()->map($key, $title);
	}

	public function column($col = 'ID') {
		if($col == 'ID') {
			$ids = array();

			foreach ($this->getResults() as $result) {
				$ids[] = $result->getId();
			}

			return $ids;
		} else {
			return $this->toArrayList()->column($col);
		}
	}

	public function each($callback) {
		return $this->toArrayList()->each($callback);
	}

	public function count() {
		return count($this->toArray());
	}

	/**
	 * @ignore
	 */
	public function offsetExists($offset) {
		throw new \Exception();
	}

	/**
	 * @ignore
	 */
	public function offsetGet($offset) {
		throw new \Exception();
	}

	/**
	 * @ignore
	 */
	public function offsetSet($offset, $value) {
		throw new \Exception();
	}

	/**
	 * @ignore
	 */
	public function offsetUnset($offset) {
		throw new \Exception();
	}

	/**
	 * @ignore
	 */
	public function add($item) {
		throw new \Exception();
	}

	/**
	 * @ignore
	 */
	public function remove($item) {
		throw new \Exception();
	}

	/**
	 * @ignore
	 */
	public function find($key, $value) {
		throw new \Exception();
	}

}
