#Aggregation
Aggregation is a powerful way to get an overview of one's data as well as
provide filters for searching.  Other than the common paradigm of faceted
searching it is possible to calculate statistics.  These can be nested
arbitrarily.  Note however if 'on the fly' calculations are used then the
resulting search can be slow.

##Overview

###What is a Aggregated Search?
The following screenshots were taken from a live demo available at
http://elastica.weboftalent.asia/search-examples/flickr-image-results
- the username/password for Basic Auth is search/search.

####Landing Page
No filters are selected, results showing are displayed in a predefined arbitrary
order such as newest.

![Landing Page for an Aggregated Search]
(https://raw.githubusercontent.com/gordonbanderson/silverstripe-elastica/screenshots/screenshots/000-elastica-facets-landing.png
"Landing Page for an Aggregated Search")

####Opening an Aggregation
Click on the red arrow to expand the aggregation showing the filters available.
It should be noted that in this example many of the images have no photographic
data due to their age - only images free of copyright were used.

Select the filter 'ISO' with value '200' - there are 8 results for this
combination.  This is what the number in brackets infers.

![Opening an Aggregation]
(https://raw.githubusercontent.com/gordonbanderson/silverstripe-elastica/screenshots/screenshots/001-elastica-aggregation-open-filter.png
"Opening an Aggregation")

####Aggregation With Filter Selected
The filter for 'ISO' with value '200' has been selected.  The filter can be
cancelled by clicking on the red 'x' icon marked by the arrow.  Images shown on
the right hand side all have an ISO value of 200.

![Aggregation With Filter Selected]
(https://raw.githubusercontent.com/gordonbanderson/silverstripe-elastica/screenshots/screenshots/002-elastica-facets-selected-filter.png
"Aggregation With Filter Selected")

####Opening a Second Filter
This is known as drilling down.  Having already selected a filter for ISO with
value 200, select a second filter for a Shutter Speed of 1/125.  This
combination has a total of 3 results, namely there are 3 FlickrPhotos with ISO
200 and a Shutter Speed value of 1/125 (1/125th of a second).
![Opening a Second Filter]
(https://raw.githubusercontent.com/gordonbanderson/silverstripe-elastica/screenshots/screenshots/003-elastica-facetsx1.5-selected.png
"Opening a Second Filter")

####Two Filters Selected
With both of the above filters selected, only 3 results now show in the search.
![Two Filters Selected]
(https://raw.githubusercontent.com/gordonbanderson/silverstripe-elastica/screenshots/screenshots/005-elastica-facets-2-selected.png
"Two Filters Selected")

####Free Text Searching Within Selected Filters
It is also possible to search via text whilst selecting filters.  Here the one
results for lighthouse is shown within the context of the selected filters.

![Searching Within Selected Filters]
(https://raw.githubusercontent.com/gordonbanderson/silverstripe-elastica/screenshots/screenshots/006-elastica-facets-2-selected-and-search.png
"Searching Within Selected Filters")

##Implementation
###Indexing
It is best to do manipulation at indexing time if possible as opposed to
querying time as calculating results on the fly is expensive and can use up a
lot of memory.

The class `IdentityElasticaIndexingHelper` implements
`ElasticaIndexingHelperInterface`, this is added to the FlickrPhoto DataObject
via an extension.  It manipulates the indexed data from the defaults provided
by Elasticsearch and this module into a form suitable for aggregation.

There are two steps to be taken:
* Optionally modify the mapping of the document
* Optionally modify the actual data stored

```php
<?php
class FlickrPhotoElasticaIndexingExtension extends IdentityElasticaIndexingHelper {

	/**
	 * Add a mapping for the location of the photograph
	 */
	public function updateElasticsearchMapping(\Elastica\Type\Mapping $mapping) {
    	// get the properties of the individual fields as an array
    	$properties = $mapping->getProperties();
```
Add a geographic location to Elasticsearch.  This is not actually used by the
aggregations, but is provided an example of adding geograhical data.
```php
    	// add a location with geo point
    	$precision1cm = array('format' => 'compressed', 'precision' => '1cm');
    	$properties['location'] =  array(
    		'type' => 'geo_point',
    		'fielddata' => $precision1cm,
    	);
```
Shutter speed has values of the form 1/10, 1/250, 2.5 and has to be altered
in order to sort it numerically.  This is done by converting the value to six
decimal places and and then placing the real value afterwards separated by a
vertical bar.  Thus a value of 1/250 would be stored as a token 0.004000 | 1/250
, this allows for the shutter speeds to be sorted but still rendered in their
fractional form.
```php
    	$properties['ShutterSpeed'] = array(
    		'type' => 'string',
    		'index' => 'not_analyzed'
		);

```
Convert the field Aperture to a double as opposed to a float.  Otherwise values
appear containing small rounding errors such as 0.399999 making sensible
aggregation impossible.
```php
    	$properties['Aperture'] = array(
    		// do not use float as the rounding makes facets impossible
    		'type' => 'double'
    	);
```
Set the altered properties for returning from the method.
```php
    	// set the new properties on the mapping
    	$mapping->setProperties($properties);

        return $mapping;
    }

```
Modify data indexed in Elasticsearch from those values stored in SilverStripe
```php
	/**
	 * Populate elastica with the location of the photograph.  Modify values for
	 * aggregation purposes
	 * @param  \Elastica\Document $document Representation of an Elastic Search document
	 * @return \Elastica\Document modified version of the document
	 */
	public function updateElasticsearchDocument(\Elastica\Document $document) {
```
Store the location using the Lat and Lon fields provided by the Mappable module
```php
		$coors = array('lat' => $this->owner->Lat, 'lon' => $this->owner->Lon);
		$document->set('location',$coors);

```
Modify the shutter speed as explained above
```php
		$sortable = $this->owner->ShutterSpeed;
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
		$sortable = $sortable . '|' . $this->owner->ShutterSpeed;
		$document->set('ShutterSpeed', $sortable);
	    return $document;
	}

}
```

##Searching
```php
<?php
use Elastica\Aggregation\Terms;
use Elastica\Query;
use Elastica\Aggregation\TopHits;
use SilverStripe\Elastica\RangedAggregation;

class FlickrPhotoElasticaSearchHelper implements ElasticaSearchHelperInterface {

```
Use what is known as a ranged aggregation to decide on whether an image is
horizontal, vertical or square.  It uses the ratio of height divided by width.
Here Aspect is the title to show in the aggregation menu, AspectRatio is the
name of the field in SilverStripe to use.
```php
	public function __construct() {
		$aspectAgg = new RangedAggregation('Aspect', 'AspectRatio');
        $aspectAgg->addRange(0.0000001, 0.3, 'Panoramic');
        $aspectAgg->addRange(0.3, 0.9, 'Horizontal');
        $aspectAgg->addRange(0.9, 1.2, 'Square');
        $aspectAgg->addRange(1.2, 1.79, 'Vertical');
        $aspectAgg->addRange(1.79, 1e7, 'Tallest');
	}
```
The value of shutter speed are indexed as their value in decimal, and then after
a vertical bar their original fractional representation.  This method replaces
the decimals with the fractional version for the purposes of displaying in the
aggregation menu.  They will be in the correct numerical order.
```php
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
	}
```
In the URL the value of the parameter ShutterSpeed will be likes of 1/250,
however it is stored in the index as 0.004000 | 1/250 - this method facilitates
this mapping.
```php
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
	}
```
This method defines the aggregations that are shown in the aggregation menu for
an Elastic Search Page.
```php
	/**
	 * Add a number of facets to the FlickrPhoto query
	 * @param  \Elastica\Query &$query the existing query object to be augmented.
	 */
	public function augmentQuery(&$query) {

		// set the order to be taken at in reverse if query is blank other than aggs
		$params = $query->getParams();

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

		$aspectRangedAgg = RangedAggregation::getByTitle('Aspect');
        $query->addAggregation($aspectRangedAgg->getRangeAgg());

		// remove NearestTo from the request so it does not get used as a term filter
		unset(Controller::curr()->request['NearestTo']);
	}
```
In the event of no filters being selected a default sort order is required.
```php

	/*
	In the event of aggregates being used and no query provided, sort by this (<field> => <order>)
	 */
	public function getDefaultSort() {
		return array('Title' => 'desc');
	}
```
This maps the name of a field in the Elasticsearch index to a more presentable
human readable name in the aggregation menus.
```php
	private static $titleFieldMapping = array(
		'ShutterSpeed' => 'Shutter Speed',
		'FocalLength35mm' => 'Focal Length'
	);

	public function getIndexFieldTitleMapping() {
		return self::$titleFieldMapping;
	}
}
```
