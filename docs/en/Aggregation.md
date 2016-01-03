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
