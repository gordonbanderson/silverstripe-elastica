#Command Line Tools
Elastic responds over an HTTP interface and as such you can do a quick check of whether or not your SilverStripe data has been saved using this approach.

The following is for UNIX based systems.

##Server Status
```bash
curl 'http://localhost:9200/?pretty'
```
##Show All Documents Indexed
```bash
curl -XGET 'http://localhost:9200/_search?pretty'
```
##Show All Documents Indexed Against a Particular Index
```bash
curl -XGET 'http://localhost:9200/YOUR_INDEX_NAME/_search?pretty'
```
##Search an Index for a Term
```bash
curl -XGET 'http://localhost:9200/YOUR_INDEX_NAME/_search?q=YOUR_SEARCH_TERM&pretty'
```
You can see the same search with highlighted results like this:
```
curl -XGET 'http://localhost:9200/YOUR_INDEX_NAME/_search?q=YOUR_SEARCH_TERM&highlighter=true&pretty'
```
##Checking Cluster Health
```bash
curl -XGET 'http://localhost:9200/_cluster/health?pretty'
```

