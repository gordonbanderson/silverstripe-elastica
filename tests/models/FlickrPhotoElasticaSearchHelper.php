<?php

use Elastica\Aggregation\Terms;
use Elastica\Query;
use Elastica\Aggregation\Range;
use SilverStripe\Elastica\RangedAggregation;

class FlickrPhotoTOElasticaSearchHelper implements ElasticaSearchHelperInterface,TestOnly
{
    public function __construct()
    {
        $aspectAgg = new RangedAggregation('Aspect', 'AspectRatio');
        $aspectAgg->addRange(0.0000001, 0.3, 'Panoramic');
        $aspectAgg->addRange(0.3, 0.9, 'Horizontal');
        $aspectAgg->addRange(0.9, 1.2, 'Square');
        $aspectAgg->addRange(1.2, 1.79, 'Vertical');
        $aspectAgg->addRange(1.79, 1e7, 'Tallest');
    }

    private static $titleFieldMapping = array(
        'ShutterSpeed' => 'Shutter Speed',
        'FocalLength35mm' => 'Focal Length',
    );

    /**
     * Add a number of facets to the FlickrPhotoTO query.
     *
     * @param \Elastica\Query &$query the existing query object to be augmented.
     */
    public function augmentQuery(&$query)
    {
        //FIXME probably move this back as default behaviour
        // set the order to be taken at in reverse if query is blank other than aggs
        $params = $query->getParams();

        $wildcard = array(
            'query_string' => array('query' => '*'),
        );

        if ($query->OriginalQueryText == '') {
            $query->setSort(array('TakenAt' => 'desc'));
        }

        // add Aperture aggregate
        $agg1 = new Terms('Aperture');
        $agg1->setField('Aperture');
        $agg1->setSize(0);
        $agg1->setOrder('_term', 'asc');
        $query->addAggregation($agg1);

        // add shutter speed aggregate
        $agg2 = new Terms('ShutterSpeed');
        $agg2->setField('ShutterSpeed');
        $agg2->setSize(0);
        $agg2->setOrder('_term', 'asc');
        $query->addAggregation($agg2);

        // this currently needs to be same as the field name
        // needs fixed
        // Add focal length aggregate, may range this
        $agg3 = new Terms('FocalLength35mm');
        $agg3->setField('FocalLength35mm');
        $agg3->setSize(0);
        $agg3->setOrder('_term', 'asc');
        $query->addAggregation($agg3);

        // add film speed
        $agg4 = new Terms('ISO');
        $agg4->setField('ISO');
        $agg4->setSize(0);
        $agg4->setOrder('_term', 'asc');
        $query->addAggregation($agg4);

        $aspectRangedAgg = RangedAggregation::getByTitle('Aspect');
        $query->addAggregation($aspectRangedAgg->getRangeAgg());

        // remove NearestTo from the request so it does not get used as a term filter
        unset(Controller::curr()->request['NearestTo']);
    }

    /**
     * Update filters, perhaps remaps them, prior to performing a search.
     * This allows for aggregation values to be updated prior to rendering.
     *
     * @param array &$filters array of key/value pairs for query filtering
     */
    public function updateFilters(&$filters)
    {

        // shutter speed is stored as decimal to 6 decimal places, then a
        // vertical bar followed by the displayed speed as a fraction or a
        // whole number.  This puts the decimal back for matching purposes
        if (isset($filters['ShutterSpeed'])) {
            $sortable = $filters['ShutterSpeed'];

            $sortable = explode('/', $sortable);
            if (sizeof($sortable) == 1) {
                $sortable = trim($sortable[0]);

                if ($sortable === '1') {
                    $sortable = '1.000000|1';
                }
            } elseif (sizeof($sortable) == 2) {
                if ($sortable[0] === '' || $sortable[1] === '') {
                    $sortable = '';
                } else {
                    $sortable = floatval($sortable[0]) / intval($sortable[1]);
                    $sortable = round($sortable, 6);
                    $sortable = $sortable.'|'.$filters['ShutterSpeed'];
                }
            }

            $filters['ShutterSpeed'] = $sortable;
        }
    }

    /**
     * Manipulate the results, e.g. fixing up values if issues with ordering in Elastica.
     *
     * @param array &$aggs Aggregates from an Elastica search to be tweaked
     */
    public function updateAggregation(&$aggs)
    {
        // the shutter speeds are of the form decimal number | fraction, keep the latter half
        $shutterSpeeds = $aggs['ShutterSpeed']['buckets'];
        $ctr = 0;
        foreach ($shutterSpeeds as $bucket) {
            $key = $bucket['key'];
            $splits = explode('|', $key);
            $shutterSpeeds[$ctr]['key'] = end($splits);
            ++$ctr;
        }
        $aggs['ShutterSpeed']['buckets'] = $shutterSpeeds;
    }

    /*
    In the event of aggregates being used and no query provided, sort by this (<field> => <order>)
     */
    public function getDefaultSort()
    {
        return array('TakenAt' => 'desc');
    }

    public function getIndexFieldTitleMapping()
    {
        return self::$titleFieldMapping;
    }
}
