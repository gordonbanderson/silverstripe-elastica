#Indexing - Manipulation of Mapping and Content at Indexing Time

##Manipulation of Mapping and Document
Sometimes you might want to alter document content prior to being sent to elasticsearch,
or alter the mapping of a field.  For that purpose add the following methods to the class whose
mapping or document you wish to manipulate.  Note that with third party module one should use an
extension otherwise these edits may possibly be lost after a composer update.
###Basic Format

```php
class YourPage extends Page implements ElasticaIndexingHelperInterface {
```
Update the mapping for a document.  One may for example alter an Aperture value
to be a string instead of a float.
```php
	public function updateElasticsearchMapping(\Elastica\Type\Mapping $mapping) {
		return $mapping;
	}

```
Manipulate existing values or add new ones at indexing time.  A suitable
example of this would be indexing the aspect ratio of an image taking into
account the width and the height, or adding a geographic location from
latitude and longitude fields.
```php
	public function updateElasticsearchDocument(\Elastica\Document $document) {
		return $document;
	}

```
Update which fields are HTML fields - these are processed using the ShortCode
parse prior to indexing, and also have HTML tags removed (Elasticsearch does
not provide this facility).
```php
	public function updateElasticHTMLFields(array $htmlFields) {
		return $htmlFields;
	}
}
```

It is often the case that only one of these needs changed, if that is the case
then extend the extension `SilverStripe\Elastica\IdentityElasticaIndexingHelper`
and apply it to the DataObject in question.

###Worked Example - Geographic Coordinates
The _Mappable_ module allows any DataObject to have geographical coordinates
assigned to it, these are held in fields called Lat and Lon.  They need to
paired together as a geographical coordinate prior to being stored in
ElasticSearch.  This allows one to make use of of geographical searching.

####Mapping
A field arbitrarily called location will be created as a geographical
coordinate.  In ElasticSearch this is known as a 'geo_point'.

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
The location needs to be added to the document in the format required for a
geo_point.  The set() method of \Elastica\Document is used to alter or add extra
fields prior to indexing.
```php
public function updateElasticsearchDocument(\Elastica\Document $document) {
	$coors = array('lat' => $this->owner->Lat, 'lon' => $this->owner->Lon);
	$document->set('location',$coors);
    return $document;
}
```
