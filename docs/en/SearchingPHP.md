#Searching from PHP
SilverStripe content can now be searched using PHP code, allowing for the possiblity of using
Elasticsearch to search for models in the backend.

##Search the SiteTree
```php
//the query string, here from a request
$query = $request->getVar('q');

//Create an elastic searcher, here of page length 20
$es = new \ElasticSearcher();
$es->setStart(0);
$es->setPageLength(20);

//Only show SiteTree results
$es->addFilter('IsInSiteTree', true);

//Perform the actual search
$results = $es->search($query);

//Display the title and highlighted content, if applicable
//(the text match may be in the title only)
foreach ($results as $result) {
	$message($result->Title);
	if ($result->SearchHighlightsByField->Content) {
		foreach ($result->SearchHighlightsByField->Content as $highlight) {
			$message("- ".$highlight->Snippet);
		}
	}

	echo "\n\n";
}
```
##Search a List of Classes
This is almost identical to the above, except that
```php
$es->addFilter('IsInSiteTree', true);
```
is replaced by
```php
$es->setClasses('FlickrPhoto,BlogPost');
```
This means that only SilverStripe content types of class FlickrPhoto or BlogPost are searched.  Note that
one can mix and match SiteTree and non SiteTree classes here.

```php
//the query string, here from a request
$query = $request->getVar('q');

//Create an elastic searcher, here of page length 20
$es = new \ElasticSearcher();
$es->setStart(0);
$es->setPageLength(20);

$es->setClasses('FlickrPhoto,BlogPost');


//Perform the actual search
$results = $es->search($query);

//Display the title and highlighted content, if applicable
//(the text match may be in the title only)
foreach ($results as $result) {
	$message($result->Title);
	if ($result->SearchHighlightsByField->Content) {
		foreach ($result->SearchHighlightsByField->Content as $highlight) {
			$message("- ".$highlight->Snippet);
		}
	}

	echo "\n\n";
}
```
###Search Only Certain Fields, With Weighting
```php
//the query string, here from a request
$query = $request->getVar('q');

//Create an elastic searcher, here of page length 20
$es = new \ElasticSearcher();
$es->setStart(0);
$es->setPageLength(20);

//Only show SiteTree results
$es->addFilter('IsInSiteTree', true);

//List fields to search, weighting and Type
$fields = array(
	'Title' => array(
		'Weight' => 4,
		'Type' => 'string'
	),
	'Content' => array(
		'Weight' => 1,
		'Type' => 'string'
	)
);

$results = $es->search($query, $fields);

//Display the title and highlighted content, if applicable
//(the text match may be in the title only)
foreach ($results as $result) {
	$message($result->Title);
	if ($result->SearchHighlightsByField->Content) {
		foreach ($result->SearchHighlightsByField->Content as $highlight) {
			$message("- ".$highlight->Snippet);
		}
	}
	echo "\n\n";
}
```
