#Usage

##Initial YML Configuration
The first step is to configure the Elastic Search service. To do this, the configuration system
is used. The simplest default configuration (i.e. for `mysite/_config/elastica.yml`) is:

```yml
	Injector:
	  SilverStripe\Elastica\ElasticaService:
		constructor:
		  - %$Elastica\Client
		  - index-name-to-use
```

You can then use the `SilverStripe\Elastica\Searchable` extension to add search functionality
to either the SiteTree (so that pages show in a search) and also DataObjects that you wish to be
searchable.

The following configuration allows all pages within the CMS to be searchable, edit e.g. `mysite/_config/elastica.yml`:
```yml
	SiteTree:
	  extensions:
		- 'SilverStripe\Elastica\Searchable'
```
Elasticsearch can then be interacted with by using the `SilverStripe\Elastica\ElasticService` class.  DataObjects can be
added using the extension mechanism also.

##PHP Level Configuration
###Searchable Fields
To add special fields to the index, just update $searchable_fields of an object:
```php
	class YourPage extends Page
	{
		private static $db = array(
			"YourField1" => "Varchar(255)",
			"YourField2"  => "Varchar(255)"
		);
		private static $searchable_fields = array(
			"YourField1",
			"YourField2"
		);
	}
```
After every change to your data model you should execute the `SilverStripe-Elastica-ReindexTask`:

```bash
	framework/sake dev/tasks/SilverStripe-Elastica-ReindexTask
```

###Further Tweaking
Sometimes you might want to change documents or mappings (eg. for special boosting settings) before they are sent to elasticsearch.
For that purpose just add some methods to your Classes:

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
