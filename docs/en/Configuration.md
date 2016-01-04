#Configuration

##Initial YML Configuration
The first step is to enable the Elastic Search service. To do this, the
standard configuration system is used. The simplest default configuration (e.g.
in `mysite/_config/elastica.yml`) is:

```yml
	Injector:
	  SilverStripe\Elastica\ElasticaService:
		constructor:
		  - %$Elastica\Client
		  - YOUR_INDEX_NAME
```
Change `YOUR_INDEX_NAME` to a more meaningful name.


For any SilverStripe DataObject, be it SiteTree related or not, it can be made
searchable by adding the extension `SilverStripe\Elastica\Searchable` and
configuring which fields are actually searchable.

The following configuration allows all pages within the CMS to be searchable, as
well as Members.  Edit a config file such as
`mysite/_config/searchable-elastica.yml` and run `dev/build` as normal:

```yml
	SiteTree:
	  extensions:
		- 'SilverStripe\Elastica\Searchable'
	Member:
	  extensions:
		- 'SilverStripe\Elastica\Searchable'
```
Elasticsearch can then be interacted with by using the
`SilverStripe\Elastica\ElasticService` class.


##PHP Level Configuration
###Searchable Fields
Adding searchable fields to an object simply involved add a static variable
`$searchable_fields` containing an array of the fields that are to be
searchable.  Elasticsearch will attempt to determine their types, but if this
needs changed it can be done so via an extension.

###Searchable Relationships
Similar to the above, relationships of types `has_one`, `one_to_many`, and
`many_to_many` can have their searchable fields stored as a flat array in the
document being indexed.  These are indicated using a static variable
`$searchable_relationships`, with optionally a pair of brackets '()' appended to
the method name, just to make clearer in the configuration that a method is
being used.  Note that relationships are only followed one level deep, this is
to avoid a situation where infinite recursion occurs, and so as also not to
bloat the elasticsearch document size excessively.

###Autocomplete Fields
Autocomplete, or 'find as you type', fields attempt to find search results
whilst text is entered into a search field.  In order for the search to be fast
the indexing of terms is verbose, as such only enable this option for short
fields such as a person's name or the title of a page.


After every change in the searchable fields for a Dataobject the task
`SilverStripe-Elastica-ReindexTask` should be executed.  See below.

###Purely PHP
```php
class YourPage extends Page
{
	private static $db = array(
		"YourField1" => "Varchar(255)",
		"YourField2"  => "Varchar(255)"
	);

	private static $many_many = array('Tags' => 'Tag');

	private static $searchable_fields = array(
		"YourField1",
		"YourField2"
	);

	private static $searchable_autocomplete = array('Title');

	// example where this content type has related tags
	private static $searchable_relationships = array(
		'Tags()'
	);
}
```

###PHP and YML
Static variables in SilverStripe classes can be configured in YML files.  This
is the preferred way to configure indexable fields on a class, as it means
third-party module classes can have fields made searchable without altering any
module code.

```php
class YourPage extends Page
{
	private static $db = array(
		"YourField1" => "Varchar(255)",
		"YourField2"  => "Varchar(255)"
	);
}
```

Add the following to a suitable YML file using standard configuration.
```yml
YourPage:
  searchable_fields:
	- YourField1
	- YourField2
	- Title
  searchable_relationships:
    - Tags()
  searchable_autocomplete:
    - Title
```

##Index Invalidation
In the case of an item indexed through a relationship, if that item changes the
original items needs to be invalidated.  This is a TODO, currently - the only
current workaround is to reindex.
