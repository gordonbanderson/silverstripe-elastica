<?php

/**
*
*/
class ThaiIndexSettings {

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
		          "type": "english",
		          "stem_exclusion": [ "organization", "organizations" ],
		          "stopwords": [
		            "a", "an", "and", "are", "as", "at", "be", "but", "by", "for",
		            "if", "in", "into", "is", "it", "of", "on", "or", "such", "that",
		            "the", "their", "then", "there", "these", "they", "this", "to",
		            "was", "will", "with"
		          ]
		        }
		      }
		    }
		  }
		}';

		return json_decode($json);
	}
}
