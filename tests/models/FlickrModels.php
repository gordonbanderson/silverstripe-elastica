<?php


/**
 * @package elastica
 * @subpackage tests
 */
class FlickrPhoto extends DataObject implements TestOnly {
	private static $searchable_fields = array('Title','FlickrID','Description','TakenAt', 'FirstViewed',
		'Aperture','ShutterSpeed','FocalLength35mm','ISO','AspectRatio');

	private static $searchable_relationships = array('Photographer', 'FlickrTags', 'FlickrSets');

	// this needs to be declared here and not added by add_extension, as it does not extend DataExtension
	private static $extensions = array('FlickrPhotoTestIndexingExtension');

	private static $db = array(
		'Title' => 'Varchar(255)',
		'FlickrID' => 'Varchar',
		'Description' => 'HTMLText',
		// test Date and SS_Datetime
		'TakenAt' => 'SS_Datetime',
		'FirstViewed' => 'Date',
		'Aperture' => 'Float',
		'ShutterSpeed' => 'Varchar',
		'FocalLength35mm' => 'Int',
		'ISO' => 'Int',
		'OriginalHeight' => 'Int',
		'OriginalWidth' => 'Int',
		'AspectRatio' => 'Double',
		'Lat' => 'Decimal(18,15)',
		'Lon' => 'Decimal(18,15)',
		'ZoomLevel' => 'Int'
	);

	static $belongs_many_many = array(
		'FlickrSets' => 'FlickrSet'
	);

	//1 to many
	static $has_one = array(
		'Photographer' => 'FlickrAuthor'
	);

	//many to many
	static $many_many = array(
		'FlickrTags' => 'FlickrTag'
	);

}


/**
 * @package elastica
 * @subpackage tests
 */
class FlickrTag extends DataObject implements TestOnly {
	private static $db = array(
		'Value' => 'Varchar',
		'FlickrID' => 'Varchar',
		'RawValue' => 'HTMLText'
	);

	//many to many
	private static $belongs_many_many = array(
		'FlickrPhotos' => 'FlickrPhoto'
	);

	private static $searchable_fields = array('RawValue');
}


/**
 * @package elastica
 * @subpackage tests
 */
class FlickrSet extends DataObject implements TestOnly {
	private static $searchable_fields = array('Title','FlickrID','Description');

	private static $db = array(
		'Title' => 'Varchar(255)',
		'FlickrID' => 'Varchar',
		'Description' => 'HTMLText'
	);

	private static $many_many = array(
		'FlickrPhotos' => 'FlickrPhoto'
	);
}



/**
 * @package elastica
 * @subpackage tests
 */
class FlickrAuthor extends DataObject implements TestOnly {
		private static $db = array(
			'PathAlias' => 'Varchar',
			'DisplayName' => 'Varchar'
		);

		//1 to many
		private static $has_many = array('FlickrPhotos' => 'FlickrPhoto');

		private static $searchable_fields = array('PathAlias', 'DisplayName');

		/**
		 * NOTE: You would not normally want to do this as this means that all of
		 * each user's FlickrPhotos would be indexed against FlickrAuthor, so if
		 * the user has 10,000 pics then the text of those 10,000 pics would
		 * be indexed also.  This is purely for test purposes with a small and
		 * controlled dataset
		 *
		 * @var array
		 */
		private static $searchable_relationships = array('FlickrPhotos');
}




class FlickrPhotoTestIndexingExtension extends Extension implements ElasticaIndexingHelperInterface,TestOnly {

	/**
	 * Add a mapping for the location of the photograph
	 */
	public static function updateElasticsearchMapping(\Elastica\Type\Mapping $mapping)
    {
    	// get the properties of the individual fields as an array
    	$properties = $mapping->getProperties();

    	// add a location with geo point
    	$precision1cm = array('format' => 'compressed', 'precision' => '1cm');
    	$properties['location'] =  array(
    		'type' => 'geo_point',
    		'fielddata' => $precision1cm,
    	);

    	$properties['ShutterSpeed'] = array(
    		'type' => 'string',
    		'index' => 'not_analyzed'
		);

    	$properties['Aperture'] = array(
    		// do not use float as the rounding makes facets impossible
    		'type' => 'double'
    	);

    	$properties['FlickrID'] = array('type' => 'integer');

    	// by default casted as a string, we want a date 2015-07-25 18:15:33 y-M-d H:m:s
     	//$properties['TakenAt'] = array('type' => 'date', 'format' => 'y-M-d H:m:s');

    	// set the new properties on the mapping
    	$mapping->setProperties($properties);

        return $mapping;
    }


	/**
	 * Populate elastica with the location of the photograph
	 * @param  \Elastica\Document $document Representation of an Elastic Search document
	 * @return \Elastica\Document modified version of the document
	 */
	public function updateElasticsearchDocument(\Elastica\Document $document)
	{
	//	self::$ctr++;

		if ($this->owner->Lat != null && $this->owner->Lon != null) {
			$coors = array('lat' => $this->owner->Lat, 'lon' => $this->owner->Lon);
			$document->set('location',$coors);
		}

		$sortable = $this->owner->ShutterSpeed;
		$sortable = explode('/', $sortable);
		if (sizeof($sortable) == 1) {
			$sortable = trim($sortable[0]);

			if ($this->owner->ShutterSpeed == null) {
				$sortable = null;
			}

			if ($sortable === 1) {
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
