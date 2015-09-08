<?php

/**
*
*/
class GermanIndexSettings  extends AbstractIndexSettings {

	function getIndexSettings() {
		/*
		See the following URLS:
		- https://www.elastic.co/guide/en/elasticsearch/guide/current/configuring-language-analyzers.html

		we want stemmed, non stemmed
		 */
		$json = '{
		  "settings": {
		    "analysis": {
		      "analyzer": {
		        "my_english": {
		          "type": "german",
		          "stopwords": "_german_"
		        }
		      }
		    }
		  }
		}';

		return json_decode($json);
	}
}
