<?php
use Elastica\Aggregation\Terms;
use Elastica\Query;
use Elastica\Aggregation\TopHits;
use Elastica\Aggregation\Range;

class FlickrPhotoElasticaSearchHelper implements ElasticaSearchHelperInterface,TestOnly {

	public function __construct() {
		$aspectAgg = new RangedAggregation('Aspect', 'AspectRatio');
        $aspectAgg->addRange(0.0000001, 0.3, 'Panoramic');
        $aspectAgg->addRange(0.3, 0.9, 'Horizontal');
        $aspectAgg->addRange(0.9, 1.2, 'Square');
        $aspectAgg->addRange(1.2, 1.79, 'Vertical');
        $aspectAgg->addRange(1.79, 1e7, 'Tallest');
	}

	private static $titleFieldMapping = array(
		'ShutterSpeed' => 'Shutter Speed',
		'FocalLength35mm' => 'Focal Length'
	);

	/**
	 * Manipulate the results, e.g. fixing up values if issues with ordering in Elastica
	 * @param  array &$aggs Aggregates from an Elastica search to be tweaked
	 */
	public function updateAggregation(&$aggs) {
		// the shutter speeds are of the form decimal number | fraction, keep the latter half
		$shutterSpeeds = $aggs['ShutterSpeed']['buckets'];
		$ctr = 0;
		foreach ($shutterSpeeds as $bucket) {
			$key = $bucket['key'];
			$splits = explode('|', $key);
			$shutterSpeeds[$ctr]['key'] = end($splits);
			$ctr++;
		}
		$aggs['ShutterSpeed']['buckets'] = $shutterSpeeds;

		// Note that instead of storing as arrays, nesting might be a better option
		// Or at least have than an option
		// See http://coderify.com/aggregates-array-field-and-autocomplete-funcionality-in-elasticsearch/

		/*

		$querystring = Controller::curr()->request->getVar('Tags');

		if ($querystring != '') {
			$buckets = $aggs['Tags']['buckets'];
			$ctr = 0;
			foreach ($buckets as $bucket) {
				$key = $bucket['key'];
				if ($key !== $querystring) {
					unset($buckets[$ctr]);
				}

				$ctr++;
			}
			$aggs['Tags']['buckets'] = $buckets;
		}
		*/
	}


	/**
	 * Update filters, perhaps remaps them, prior to performing a search.
	 * This allows for aggregation values to be updated prior to rendering.
	 * @param  array &$filters array of key/value pairs for query filtering
	 */
	public function updateFilters(&$filters) {
		// shutter speed is stored as decimal to 6 decimal places, then a
		// vertical bar followed by the displayed speed as a fraction or a
		// whole number.  This puts the decimal back for matching purposes
		if (isset($filters['ShutterSpeed'])) {
			$sortable = $filters['ShutterSpeed'];
			$sortable = explode('/', $sortable);
			if (sizeof($sortable) == 1) {
				$sortable = trim($sortable[0]);

				if ($this->owner->ShutterSpeed == null) {
					$sortable = null;
				}

				if ($sortable === '1') {
					$sortable = '1.000000';
				}

			} else if (sizeof($sortable) == 2) {
				$sortable = floatval($sortable[0])/intval($sortable[1]);
				$sortable = round($sortable,6);
			}
			$sortable = $sortable . '|' . $filters['ShutterSpeed'];
			$filters['ShutterSpeed'] = $sortable;
		}

		/*
		Remap the name of the URL parameter to the Elastica field name
		 */
		if (isset($filters['Tags'])) {
			$v = $filters['Tags'];
			unset($filters['Tags']);
			$filters['FlickrTags.RawValue'] = $v;
		}

		/*
		if (isset($filters['Aspect'])) {
			$range = \RangedAggregation::getByTitle('Aspect');
			$filter = $range->getFilter('Panoramic');
			$filters['Aspect'] = $filter;
		}
		*/

	}


	/**
	 * Add a number of facets to the FlickrPhoto query
	 * @param  \Elastica\Query &$query the existing query object to be augmented.
	 */
	public function augmentQuery(&$query) {

		//FIXME probably move this back as default behaviour
		// set the order to be taken at in reverse if query is blank other than aggs
		$params = $query->getParams();

		//need to check for nested query here, filter and text
		if (is_subclass_of($params['query'], 'Elastica\Query\AbstractQuery')) {
			echo "**** QUERY PARAM INSTANCE OF Query ****\n";
			$params2 = $params['query']->getParams();
			if (!isset($params2['query']['filtered']['query']['query_string'])) {
				$query->setSort(array('TakenAt'=> 'desc'));
			}
		} else {
			if (!isset($params['query']['filtered']['query']['query_string'])) {
				$params['query']->setSort(array('TakenAt'=> 'desc'));
			}
		}

		// add Aperture aggregate
		$agg1 = new Terms("Aperture");
		$agg1->setField("Aperture");
		$agg1->setSize(0);
		$agg1->setOrder('_term', 'asc');
		$query->addAggregation($agg1);

		// add shutter speed aggregate
		$agg2 = new Terms("ShutterSpeed");
		$agg2->setField("ShutterSpeed");
		$agg2->setSize(0);
		$agg2->setOrder('_term', 'asc');
		$query->addAggregation($agg2);

		// this currently needs to be same as the field name
		// needs fixed
		// Add focal length aggregate, may range this
		$agg3 = new Terms("FocalLength35mm");
		$agg3->setField("FocalLength35mm");
		$agg3->setSize(0);
		$agg3->setOrder('_term', 'asc');
		$query->addAggregation($agg3);

		// add film speed
		$agg4 = new Terms("ISO");
		$agg4->setField("ISO");
		$agg4->setSize(0);
		$agg4->setOrder('_term', 'asc');
		$query->addAggregation($agg4);

		$aspectRangedAgg = \RangedAggregation::getByTitle('Aspect');
        $query->addAggregation($aspectRangedAgg->getRangeAgg());

		// leave this out for the moment as way too many terms being returned slowing things down
		/*
		$agg5 = new Terms("Tags");
		$agg5->setField("FlickrTags.RawValue");
		$agg5->setSize(10);
		$agg5->setOrder('_term', 'asc');

		$agg6 = new TopHits('Top Hits');
		$agg6->setSize(20);
		$agg5->addAggregation($agg6);

		$query->addAggregation($agg5);
		*/

		// remove NearestTo from the request so it does not get used as a term filter
		unset(Controller::curr()->request['NearestTo']);
	}


	public function getIndexFieldTitleMapping() {
		return self::$titleFieldMapping;
	}



}
