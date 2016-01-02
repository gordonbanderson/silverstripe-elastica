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

Note that if a field is missing it will be ignored, the search does not fail.  An example of this
would be a BlogPost having BlogTags associated with it, whereas of course a standard Page does
not have this field.  Page and BlogPost however have Title and Content fields in common.

##Autocompletion
TODO

##Similarity Searching
TODO

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
