#Command Line Tools
Elastic responds over an HTTP interface and as such you can do a quick check of whether or not your SilverStripe data has been saved using this approach.

##Using Curl to Check Indexed Data
The following is for UNIX based systems.

###Checking Cluster Health
```bash
curl -XGET 'http://localhost:9200/_cluster/health?pretty'
```
###Show Indexes
```bash
curl 'localhost:9200/_cat/indices?v'
```

Example output, showing three indexes, one each for English, German and Thai
```
health status index            pri rep docs.count docs.deleted store.size pri.store.size 
yellow open   searchtest_en_us   5   1        731            0      6.3mb          6.3mb 
yellow open   searchtest_th_th   5   1          1            0     16.5kb         16.5kb 
yellow open   searchtest_de_de   5   1        342            0      3.5mb          3.5mb 
```

###Server Status
```bash
curl 'http://localhost:9200/?pretty'
```
Example ouput in JSON format:
```
{
  "status" : 200,
  "name" : "Aralune",
  "cluster_name" : "elasticsearch",
  "version" : {
    "number" : "1.7.0",
    "build_hash" : "929b9739cae115e73c346cb5f9a6f24ba735a743",
    "build_timestamp" : "2015-07-16T14:31:07Z",
    "build_snapshot" : false,
    "lucene_version" : "4.10.4"
  },
  "tagline" : "You Know, for Search"
}
```

###Show Document Mapping
The following command shows, in JSON format, the format of a SilverStripe content type from
an elastic search perspective.
```bash
curl -XGET 'http://localhost:9200/_mapping?pretty'
```
The output is verbose, this is a sample showing the mapping for a BlogEntry.

```json
"BlogEntry" : {
        "properties" : {
          "BlogCategory" : {
            "properties" : {
              "ID" : {
                "type" : "integer"
              },
              "Title" : {
                "type" : "string",
                "analyzer" : "stemmed",
                "fields" : {
                  "standard" : {
                    "type" : "string",
                    "analyzer" : "standard"
                  }
                }
              }
            }
          },
          "Content" : {
            "type" : "string",
            "analyzer" : "stemmed",
            "fields" : {
              "standard" : {
                "type" : "string",
                "analyzer" : "standard"
              }
            }
          },
          "IsSiteTree" : {
            "type" : "boolean"
          },
          "Link" : {
            "type" : "string"
          },
          "Locale" : {
            "type" : "string",
            "index" : "not_analyzed"
          },
          "Tags" : {
            "type" : "string",
            "analyzer" : "stemmed",
            "fields" : {
              "standard" : {
                "type" : "string",
                "analyzer" : "standard"
              }
            }
          },
          "Title" : {
            "type" : "string",
            "analyzer" : "stemmed",
            "fields" : {
              "standard" : {
                "type" : "string",
                "analyzer" : "standard"
              }
            }
          }
        }
      }
```

###Show Some Documents Indexed
```bash
curl -XGET 'http://localhost:9200/_search?pretty'
```
You can vary the number by adding a size parameter, which is the number of results returned.

###Show Some Documents Indexed Against a Particular Index
```bash
curl -XGET 'http://localhost:9200/YOUR_INDEX_NAME/_search?pretty'
```
You can vary the number by adding a size parameter, which is the number of results returned.

###Search an Index for a Term
```bash
curl -XGET 'http://localhost:9200/YOUR_INDEX_NAME/_search?q=YOUR_SEARCH_TERM&pretty'
```
You can see the same search with highlighted results like this:
```
curl -XGET 'http://localhost:9200/YOUR_INDEX_NAME/_search?q=YOUR_SEARCH_TERM&highlighter=true&pretty'
```

##Aliases
An easier way on UNIX to use these commands is aliasing.

Add the following to the file ~/.bash_alias on the account you are developing with.

```bash
alias elasticstatus="curl 'http://localhost:9200/?pretty'"
alias elasticclusterhealth="curl -XGET 'http://localhost:9200/_cluster/health?pretty'"
alias elasticindexes="curl 'localhost:9200/_cat/indices?v'"
alias elasticmappings="curl -XGET 'http://localhost:9200/_mapping?pretty'"
alias elasticmanydocs="curl -XGET 'http://localhost:9200/_search?size=1000&pretty'"
searchelastica() { curl -XGET "http://localhost:9200/$1/_search?q=$2&pretty" ;}
```
A suitable workflow for checking if you SilverStripe data has been indexed as expected
is this:

```bash

>$ elasticindexes 
health status index            pri rep docs.count docs.deleted store.size pri.store.size 
yellow open   searchtest_en_us   5   1        731            0      6.3mb          6.3mb 
yellow open   searchtest_th_th   5   1          1            0     16.5kb         16.5kb 
yellow open   searchtest_de_de   5   1        342            0      3.5mb          3.5mb 

>$searchelastica searchtest_en_us bicycle
searchtest_en_us bicycle
{
  "took" : 20,
  "timed_out" : false,
  "_shards" : {
    "total" : 5,
    "successful" : 5,
    "failed" : 0
  },
  "hits" : {
    "total" : 43,
    "max_score" : 1.0058261,
    "hits" : [ {
      "_index" : "searchtest_en_us",
      "_type" : "BlogPost",
      "_id" : "1993",
      "_score" : 1.0058261,
      "_source":{"Title":"Across Asia on a Bicycle 26","Content":"\n[Illustration: RIDING BEFORE THE GOVERNOR AT MESHED.]","Categories":[],"Tags":[],"IsInSiteTree":true,"Link":"http://moduletest.silverstripe/gutenberg/across-asia-on-a-bicycle-26/","Locale":"en_US"}
    }, {
      "_index" : "searchtest_en_us",
      "_type" : "BlogPost",
      "_id" : "1969",
      "_score" : 0.97788817,
      "_source":{"Title":"Across Asia on a Bicycle 2","Content":"\n_All rights reserved._\n\nTHE DEVINNE PRESS.\n\nTO","Categories":[],"Tags":[],"IsInSiteTree":true,"Link":"http://moduletest.silverstripe/gutenberg/across-asia-on-a-bicycle-2/","Locale":"en_US"}
    }, {
      "_index" : "searchtest_en_us",
      "_type" : "BlogPost",
      "_id" : "1968",
      "_score" : 0.80522346,
      "_source":{"Title":"Across Asia on a Bicycle 1","Content":"\nACROSS ASIA ON A BICYCLE\n\n[Illustration: THROUGH WESTERN CHINA IN LIGHT MARCHING ORDER.]\n\nACROSS ASIA ON A\nBICYCLE\n\nTHE JOURNEY OF TWO AMERICAN STUDENTS\nFROM CONSTANTINOPLE TO PEKING\n\nBY\nTHOMAS GASKELL ALLEN, JR.\nAND\nWILLIAM LEWIS SACHTLEBEN\n\nNEW YORK\nTHE CENTURY CO.\n1894\n\nCopyright, 1894, by\nTHE CENTURY CO.","Categories":[],"Tags":[],"IsInSiteTree":true,"Link":"http://moduletest.silverstripe/gutenberg/across-asia-on-a-bicycle-1/","Locale":"en_US"}
    }, {
      "_index" : "searchtest_en_us",
      "_type" : "BlogPost",
      "_id" : "2010",
      "_score" : 0.6507188,
      "_source":{"Title":"Across Asia on a Bicycle 43","Content":"\nTRANSCRIBER’S NOTE\n\nThe list of illustrations has been added in the electronic text.\n\nThe following typographical errors have been corrected:\n\npage 82, period changed to comma (after “was”)\npage 140, “Siberan” changed to “Siberian”","Categories":[],"Tags":[],"IsInSiteTree":true,"Link":"http://moduletest.silverstripe/gutenberg/across-asia-on-a-bicycle-43/","Locale":"en_US"}
    }, {
      "_index" : "searchtest_en_us",
      "_type" : "BlogPost",
      "_id" : "1986",
      "_score" : 0.41909423,
      "_source":{"Title":"Across Asia on a Bicycle 19","Content":"\n[Illustration: YARD OF CARAVANSARY AT TABREEZ.]\n\n[Illustration: LUMBER-YARD AT TABREEZ.]\n\n_Tabreez_ (fever-dispelling) was a misnomer in our case. Our sojourn here\nwas prolonged for more than a month by a slight attack of typhoid fever,\nwhich this time seized Sachtleben, and again the kind nursing of the\nmissionary ladies hastened recovery. Our mail, in the mean time, having\nbeen ordered to Teheran, we were granted the privilege of intercepting it.\nFor this purpose we were permitted to overhaul the various piles of\nletters strewn over the dirty floor of the distributing-office. Both the\nTurkish and Persian mail is carried in saddle-bags on the backs of\nreinless horses driven at a rapid gallop before the mounted mail-carrier\nor herdsman. Owing to the carelessness of the postal officials, legations\nand consulates employ special couriers.","Categories":[],"Tags":[],"IsInSiteTree":true,"Link":"http://moduletest.silverstripe/gutenberg/across-asia-on-a-bicycle-19/","Locale":"en_US"}
    }, {
      "_index" : "searchtest_en_us",
      "_type" : "BlogPost",
      "_id" : "2009",
      "_score" : 0.33527538,
      "_source":{"Title":"Across Asia on a Bicycle 42","Content":"\nFOOTNOTE\n\n1 Eight years before the first recorded ascent of Ararat by Dr. Parrot\n(1829), there appeared the following from “Travels in Georgia,\nPersia, Armenia, and Ancient Babylonia,” by Sir Robert Ker Porter,\nwho, in his time, was an authority on southwestern Asia: “These\ninaccessible heights [of Mount Ararat] have never been trod by the\nfoot of man since the days of Noah, if even then; for my idea is\nthat the Ark rested in the space between the two heads (Great and\nLittle Ararat), and not on the top of either. Various attempts have\nbeen made in different ages to ascend these tremendous mountain\npyramids, but in vain. Their forms, snows, and glaciers are\ninsurmountable obstacles: the distance being so great from the\ncommencement of the icy region to the highest points, cold alone\nwould be the destruction of any one who had the hardihood to\npersevere.”","Categories":[],"Tags":[],"IsInSiteTree":true,"Link":"http://moduletest.silverstripe/gutenberg/across-asia-on-a-bicycle-42/","Locale":"en_US"}
<SNIP>
```
