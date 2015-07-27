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
Adding fields of an object class to an index can be done in one of two ways, updating the static variable
$searchable_fields of an object.

Note that after every change to your data model you should execute the `SilverStripe-Elastica-ReindexTask`, see below.

###Purely PHP
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
###PHP and YML
Static variables in SilverStripe classes can be configured from configuration classes.  This is the preferred way to
configurable indexable fields on a class, as it means third party module classes can have fields added to Elastic
without altering any module code.
```php
	class YourPage extends Page
	{
		private static $db = array(
			"YourField1" => "Varchar(255)",
			"YourField2"  => "Varchar(255)"
		);
	}
```

Add the following to a suitable YML file in your configuration.
```yml
YourPage:
  searchable_fields:
	- YourField1
	- YourField2
```


##Tasks
### Notes
On UNIX based machines where the webserver is running as a different user than that using the shell, you will need to
prefix the command with a sudo to the relevant user.  For example in Debian, whose webservers run as www-data use the
following example as a guide:

```bash
	sudo -u www-data framework/sake dev/tasks/SilverStripe-Elastica-ReindexTask
```

###Reindex
Execute a reindex of all of the classes configured to be indexed.
```bash
	framework/sake dev/tasks/SilverStripe-Elastica-ReindexTask
```

###Delete Index
Delete the configured index.  Reindexing as above will restore the index as functional.
```bash
	framework/sake dev/tasks/SilverStripe-Elastica-DeleteIndexTask
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
