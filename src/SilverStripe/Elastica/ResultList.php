<?php

namespace SilverStripe\Elastica;

use Elastica\Index;
use Elastica\Query;
use Elastica\Filter\GeoDistance;


/**
 * A list wrapper around the results from a query. Note that not all operations are implemented.
 */
class ResultList extends \ViewableData implements \SS_Limitable, \SS_List {

    /**
     * @var \Elastica\Index
     */
    private $service;

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
	 * Filters, i.e. selected aggregations, to apply to the search
	 */
	private $filters = array();

	/**
	 * An array list of aggregations from this search
	 * @var ArrayList
	 */
	private $aggregations;


	public function __construct(ElasticaService $service, Query $query, $q, $filters = array()) {
		$this->service = $service;
		$this->query = $query;
		$this->originalQueryText = $q;
		$this->filters = $filters;
	}

	public function __clone() {
		$this->query = clone $this->query;
	}

	/**
	 * @return \Elastica\Index
	 */
	public function getService() {
		return $this->service;
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
		if (!isset($this->_cachedResults)) {
			$ers = $this->service->search($this->query,$this->types);

			if (isset($ers->MoreLikeThisTerms)) {
				$this->MoreLikeThisTerms = $ers->MoreLikeThisTerms;
			}

			//query-term-suggestions is arbitrary name used
			if (isset($ers->getSuggests()['query-phrase-suggestions'])) {
				$suggest = $ers->getSuggests()['query-phrase-suggestions'];
				$suggestedPhraseAndHL = ElasticaUtil::getPhraseSuggestion($suggest);
				if ($suggestedPhraseAndHL) {
					$this->SuggestedQuery = $suggestedPhraseAndHL['suggestedQuery'];
					$this->SuggestedQueryHighlighted = $suggestedPhraseAndHL['suggestedQueryHighlighted'];
				}
			}

			$this->TotalItems = $ers->getTotalHits();
			$this->TotalTime = $ers->getTotalTime();
			$this->_cachedResults = $ers->getResults();
			// make the aggregations available to the templating, title casing
			// to be consistent with normal templating conventions
			$aggs = $ers->getAggregations();

			// array of index field name to human readable title
			$indexedFieldTitleMapping = array();

			// optionally remap keys and store chosen aggregations from get params
			if (isset($this->SearchHelper)) {
				$manipulator = \Injector::inst()->create($this->SearchHelper);
				$manipulator->query = $this->query;
				$manipulator->updateAggregation($aggs);

				$indexedFieldTitleMapping = $manipulator->getIndexFieldTitleMapping();
			}
			$aggsTemplate = new \ArrayList();

			// Convert the buckets into a form suitable for SilverStripe templates
			$q = $this->originalQueryText;

			// if not search term remove it and aggregate with a blank query
			if ($q == '' && sizeof($aggs) > 0) {
				$params = $this->query->getParams();
				unset($params['query']);
				$this->query->setParams($params);
				$q = '';
			}

			// get the base URL for the current facets selected
			$baseURL = \Controller::curr()->Link().'?';
			$prefixAmp = false;
			if ($q !== '') {
				$baseURL .= 'q='.urlencode($q);
				$prefixAmp = true;
			}

			// now add the selected facets
			foreach ($this->filters as $key => $value) {
				if ($prefixAmp) {
					$baseURL .= '&';
				} else {
					$prefixAmp = true;
				}
				$baseURL .= $key.'='.urlencode($value);
			}

			foreach (array_keys($aggs) as $key) {
				//echo "MARKING SELECTED AGGS FOR $key \n";
				$aggDO = new \DataObject();
				//FIXME - Camel case separate here
				if (isset($indexedFieldTitleMapping[$key])) {
					$aggDO->Name = $indexedFieldTitleMapping[$key];
				} else {
					$aggDO->Name = $key;
				}

				// now the buckets
				if (isset($aggs[$key]['buckets'])) {
					//echo "Buckets found for $key \n";
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

						// check if currently selected
						if (isset($this->filters[$key])) {

							if ($$this->filters[$key] === (string)$value['key']) {
								//echo "     - Marked as selected \n";
								$ct->IsSelected = true;
								// mark this facet as having been selected, so optional toggling
								// of the display of the facet can be done via the template.
								$aggDO->IsSelected = true;

								$urlParam = $key.'='.urlencode($$this->filters[$key]);

								//echo "    - URL PARAM : $urlParam \n";

								// possible ampersand combos to remove
								$v2 = '&'.$urlParam;
								$v3 = $urlParam.'&';
								$url = str_replace($v2, '', $url);
								$url = str_replace($v3, '', $url);
								$url = str_replace($urlParam, '', $url);
								$ct->URL = $url;
							}
						} else {
							$url .= $key .'='.urlencode($value['key']);
							$prefixAmp = true;
						}

						$url = rtrim($url,'&');

						$ct->URL = $url;
						$bucketsAL->push($ct);
					}

					// in the case of range queries we wish to remove the non selected ones
					if ($aggDO->IsSelected) {
						$newList = new \ArrayList();
						foreach ($bucketsAL->getIterator() as $bucket) {
							if ($bucket->IsSelected) {
								$newList->push($bucket);
								break;
							}
						}

						$bucketsAL = $newList;
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
		$ignore = array('Title', 'Link', 'Title.standard', 'Link.standard');

		foreach ($found as $item) {
			// Safeguards against indexed items which might no longer be in the DB
			if(array_key_exists($item->getId(), $retrieved[$item->getType()])) {

                $data_object = $retrieved[$item->getType()][$item->getId()];
                $data_object->setElasticaResult($item);
                $highlights = $item->getHighlights();

                //$snippets will contain the highlights shown in the body of the search result
                //$namedSnippets will be used to add highlights to the Link and Title
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
                		//Fields may have a dot in their name, e.g. Title.standard - take this into account
                		//As dots are an issue with template syntax, store as Title_standard
                		$splits = explode('.', $fieldName);
                		if (sizeof($splits) == 1) {
                			$namedSnippets->$fieldName = $fieldSnippets;
                		} else {
                			// The Title.standard case, for example
                			$splits = explode('.', $fieldName);
                			$compositeFielddName = $splits[0].'_'.$splits[1];
                			$namedSnippets->$compositeFielddName = $fieldSnippets;
                		}

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
		throw new \Exception('Not implemented');
	}

	public function last() {
		// TODO: Implement last() method
		throw new \Exception('Not implemented');
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
		throw new \Exception('Not implemented');
	}

	/**
	 * @ignore
	 */
	public function offsetGet($offset) {
		throw new \Exception('Not implemented');
	}

	/**
	 * @ignore
	 */
	public function offsetSet($offset, $value) {
		throw new \Exception('Not implemented');
	}

	/**
	 * @ignore
	 */
	public function offsetUnset($offset) {
		throw new \Exception('Not implemented');
	}

	/**
	 * @ignore
	 */
	public function add($item) {
		throw new \Exception('Not implemented');
	}

	/**
	 * @ignore
	 */
	public function remove($item) {
		throw new \Exception('Not implemented');
	}

	/**
	 * @ignore
	 */
	public function find($key, $value) {
		throw new \Exception('Not implemented');
	}

}
