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
combination.

![Opening an Aggregation]
(https://raw.githubusercontent.com/gordonbanderson/silverstripe-elastica/screenshots/screenshots/001-elastica-aggregation-open-filter.png
"Opening an Aggregation")

![PIC]
(https://raw.githubusercontent.com/gordonbanderson/silverstripe-elastica/screenshots/screenshots/002-elastica-facets-selected-filter.png
"PIC")

![PIC]
(https://raw.githubusercontent.com/gordonbanderson/silverstripe-elastica/screenshots/screenshots/003-elastica-facetsx1.5-selected.png
"PIC")

![PIC]
(https://raw.githubusercontent.com/gordonbanderson/silverstripe-elastica/screenshots/screenshots/005-elastica-facets-2-selected.png
"PIC")

![PIC]
(https://raw.githubusercontent.com/gordonbanderson/silverstripe-elastica/screenshots/screenshots/006-elastica-facets-2-selected-and-search.png
"PIC")
