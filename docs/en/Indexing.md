#Indexing

##Performing the Indexing
To perform the indexing execute the ReIndex task, see [Tasks](./Tasks.md) - this deletes the index
and rebuilds it from scratch.

##Manipulation of Mapping and Document
Sometimes you might want to change documents or mappings (eg. for special boosting settings) before
they are sent to elasticsearch.  For that purpose add the following methods to the class whose 
mapping or document you wish to manipulate.  Note that with third party module one should use an
extension otherwise these edits may possibly be lost after a composer update.
###Basic Format

```
	class YourPage extends Page
	{
		public static function updateElasticsearchMapping(\Elastica\Type\Mapping $mapping)
		{
			return $mapping;
		}

		public function updateElasticsearchDocument(\Elastica\Document $document)
		{
			return $document;
		}
	}
```

###Worked Example - Geographic Coordinates
The Mappable module allows any DataObject to have geographical coordinates assigned to it, these
are held in fields called Lat and Lon.  They need to paired together as a geographical coordinate
prior to being stored in Elastic.  This allows one to take advantage of geographical searching.

####Mapping
A field arbitrarily called location will be created as a geographical coordinate.  In Elastic this
is known as a 'geo_point'.

```php
public static function updateElasticsearchMapping(\Elastica\Type\Mapping $mapping) {
	// get the properties of the individual fields as an array
	$properties = $mapping->getProperties();

	// add a location with geo point
	$precision1cm = array('format' => 'compressed', 'precision' => '1cm');
	$properties['location'] =  array(
		'type' => 'geo_point',
		'fielddata' => $precision1cm
	);

	// set the new properties on the mapping
	$mapping->setProperties($properties);
    return $mapping;
}
```
###Document
The location needs to be added to the document in the format required for a geo_point.  The set()
method of document is used to alter or add extra fields prior to indexing.
```php
public function updateElasticsearchDocument(\Elastica\Document $document) {
	$coors = array('lat' => $this->owner->Lat, 'lon' => $this->owner->Lon);
	$document->set('location',$coors);
    return $document;
}
```
