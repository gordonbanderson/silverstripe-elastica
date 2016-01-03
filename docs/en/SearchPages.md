#Search Pages
##Introduction
It is now possible to have multiple search pages on your site, each with their own separate
restrictions as to which class or classes are searched.  Asides the traditional 'search all of the
SiteTree' this module provides more flexibility.  An administrator can do the following:

* Create a search page that searches just blog posts
* Create a search page that searches your Flickr photos, represented as DataObjects
* Fields within the search can be weighted, e.g. make the Title twice as important as the Content
* Vary the number of search results from the default of 10
* Add classes to manipulate the search and the results, allowing for aggregated searches

Note that the above changes are instant, there is no need to reindex the data, it is the query that
is altered.  The content administrator can make these changes within the CMS.

* Override the template of search results, necessary when dealing with results not having a Title
or Link.

##Adding a Search Page to Your Site
Using the standard mechanism for adding a page in the CMS, add a page of type _ElasticSearchPage_
at an appropriate location for the search intended, e.g. /search, /blog/search, /photos/search.

##Configuring a Search Page
###Search Site Tree Only
Select the 'Site Tree Only' checkbox, and then save the page.  This will simulate the standard
SilverStripe search, namely all pages in the SiteTree, but using Elastic Search.
![Search the SiteTree Only]
(https://raw.githubusercontent.com/gordonbanderson/silverstripe-elastica/screenshots/screenshots/elastica001-site-tree-only.png
"Search the SiteTree Only")

###Selected List of ClassNames
It is possible to restrict the classes that are searched, useful for example if searching a
particular subset of your site likes of a blog.  In the example below only pages of type Blog or
BlogPost are returned from the search.
![Search Blogs Only]
(https://raw.githubusercontent.com/gordonbanderson/silverstripe-elastica/screenshots/screenshots/elastica002-blogpost.png
"Search Blogs Only")
Note that a list of the available classes is shown just below for reference.  This can be copied and
pasted into the TagField of available classes.
![List of Available Classes]
(https://raw.githubusercontent.com/gordonbanderson/silverstripe-elastica/screenshots/screenshots/elastica003.png
"List of Available Classes")

###Number of Results
The default number of results is 10, this can be changed to any number as required.  In the
screenshot below, the number of results has been changed to 20
![Change Number of Search Results]
(https://raw.githubusercontent.com/gordonbanderson/silverstripe-elastica/screenshots/screenshots/elastica006-number-of-results.png
"Change Number of Search Results")

###Field Editing
After saving an Elastic Search Page, the fields available will be shown in the Search/Fields tab.
The fields available from the list of selected classes will be shown and are editable.  Note
that a field weight <= 0 is invalid and the page cannot be saved.

![List of Searchable Fields]
(https://raw.githubusercontent.com/gordonbanderson/silverstripe-elastica/screenshots/screenshots/elastica005-fields-weighting.png
"List of Searchable Fields")
* Weighting - adjust the weight of that field, making it more or less important
* Use for Search? - true to use the field in the search, false not to
* Use for Similar Search? - true to use the field when doing a simiilar search, false not to
* Show Search Highlights - show query terms highlight in the search results for the field in
question


![Editing Weighting]
(https://raw.githubusercontent.com/gordonbanderson/silverstripe-elastica/screenshots/screenshots/elastica004-alter-weighting.png
"Editing Weighting")

If a field searched for is not present in all the objects being searched, the search does not fail.
An example of this would be a BlogPost having BlogTags associated with it, whereas of course a
standard Page does not have this field.  Page and BlogPost however have Title and Content fields in
common.

##Autocompletion
Autocompletion should only be indexed for relatively short fields as it is extremely verbose in
the number of terms indexed.  Good example of suitable fields are the title of a page or the full
name of a Member.

![Autocomplete]
(https://raw.githubusercontent.com/gordonbanderson/silverstripe-elastica/screenshots/screenshots/elastica-autocomplete.png
"Editing Autocompletion")

To enable autocomplete click on the Search/AutoComplete tab.  A drop down list of autocompleteable
fields is shown, of which only one can be selected.  There are 3 options for what can happen when
autocompleted text is selected.

* GoToRecord - go directly to a page showing the record whose field was autocompleted
* Similar - find objects similar to that represented by the selected autocomplete text
* Search - search using the terms of the title of the selected autocomplete text

##Aggregations
Aggregations are sufficiently complex they are on a [separate page](./Aggregations.md).

##Similarity Searching
A similarity search, or in Elasticsearch parlance a 'more like this' query finds documents similar
to the one used as the basis for the search.  It has several configurable parameters and some
experimentation may be required depending on your site's content.

* Stop Words - terms to ignore when querying
* Minimum term frequency - minimum number of times a term can appear in a field to be used
* Maximum terms selected - the maximum number of terms selected to be used for similarity search
* Minimum document frequency - the minimum number of documents a term appears in
* Maximum document frequency - the maximum number of documents a term appears in.  Can be used to
filter stop words
* Minimum word length - the minimum length of a term for it to be considered
* Maximum word length - the maximum length of a term for it to be considered
* Number of %age of matching terms - see
https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-minimum-should-match.html
for a detailed explanation.

Note that the default values are as per the Elasticsearch documentation.  Clicking on the button
'Restore Defaults' restores these values (after a Save).

![Similarity Searching]
(https://raw.githubusercontent.com/gordonbanderson/silverstripe-elastica/screenshots/screenshots/elastica-similarity.png
"Editing similarity search parameters")

##Overriding the SearchPage Template
It is most likely necessary to override the default template when rendering serach results for
DataObjects, as they may not have the methods _Title_ and _AbsoluteLink_ necessary to render them.
This requires another module that can alter the template for a given page.

###Installation of Template Override Module
####Composer
```bash
composer require weboftalent/template-override 3.1.x-dev
```
####Git
```bash
git clone https://github.com/gordonbanderson/template-override.git
cd template-override
git checkout 3.1
```
###Using Template Override
Simple navigate to the tab 'Template', or i18n equivalent, and enter the name of the template
in the text field provided.  If the value is left empty, the normal template will be used.
![Setting Templates to BlogSearchResults.ss]
(https://raw.githubusercontent.com/gordonbanderson/silverstripe-elastica/screenshots/screenshots/elastica007-blog-search-results-template.png
"Setting Templates to BlogSearchResults.ss")
