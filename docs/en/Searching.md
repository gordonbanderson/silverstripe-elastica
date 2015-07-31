#Searching
##Highlights
###Command Line
You can test if highlighting is working against your index as follows.  Change YOUR_INDEX_NAME and
YOUR_QUERY_TERM as appropriate.
```bash
curl -XGET 'http://localhost:9200/YOUR_INDEX_NAME/_search?pretty' -d '
{
    "query": {
        "query_string": {
            "query": "YOUR_QUERY_TERM"
        }
    },
    "highlight" : {
        "fields" : {
            "*" : {}
        }
    }
}
'
```
If highlighting is working, i.e. the fields are stored appropriately, output will contain sections
like this (the search term was 'canal':

```
highlight" : {
    "Content" : [ " alongside a <em>canal</em>, offer safe passage for cyclists. The landscape varies between shacks and solid", " the pattern of houses similar to those densely located by the <em>canal</em> in the map above.\nAs can be seen", " of <em>canal</em> trail or indeed entrances to slums. Often a section is named and has a reasonably grand" ]
  }
```
##Boosting Fields
https://www.elastic.co/guide/en/elasticsearch/guide/current/query-time-boosting.html - need to repeat all fields. Maybe try with a '*'?
