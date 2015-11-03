<?php
use \SilverStripe\Elastica\ElasticSearcher;
use \SilverStripe\Elastica\QueryGenerator;

/**
 * Test query generation
 * @package elastica
 */
class QueryGeneratorTest extends ElasticsearchBaseTest {
	//public static $fixture_file = 'elastica/tests/ElasticaTest.yml';
	public static $fixture_file = 'elastica/tests/lotsOfPhotos.yml';


	public static $ignoreFixtureFileFor = array('testToQuoted*');

	public function testTextOnly() {
		$qg = new QueryGenerator();
		$qg->setQueryText('New Zealand');
		$qg->setFields(null);
		$qg->setSelectedFilters(null);

		//As the query is not empty it should not matter whether or not the show results for empty
		//query flag is set or not - test with true and false

		$qg->setShowResultsForEmptyQuery(false);
		$qs = array('query_string' => array('query' => 'New Zealand', 'lenient' => true));
		$expected = array(
			'query' => $qs,
			'size' => 10,
			'from' => 0,
			'suggest' => $this->getDefaultSuggest('New Zealand')
		);

		$this->assertEquals($expected, $qg->generateElasticaQuery()->toArray());

		$qg->setShowResultsForEmptyQuery(true);
		$this->assertEquals($expected, $qg->generateElasticaQuery()->toArray());
	}


	private function getDefaultSuggest($q) {
		return array(
				'query-phrase-suggestions' => array(
					'phrase' => array(
						'field' => '_all',
						'size' => 4,
						'highlight' => array(
							'pre_tag' => '<strong class="hl">',
							'post_tag' => '</strong>'
						)
					),
					'text' => $q
				)
		);
	}


	public function testEmptyTextShowNone() {
		$qg = new QueryGenerator();
		$qg->setQueryText('');
		$qg->setFields(null);
		$qg->setSelectedFilters(null);
		$qg->setShowResultsForEmptyQuery(false);

		$qs = array('query_string' => array('query' => '', 'lenient' => true));
		$expected = array(
			'query' => $qs,
			'size' => 10,
			'from' => 0,
			'suggest' => $this->getDefaultSuggest('')
		);

		$this->assertEquals($expected, $qg->generateElasticaQuery()->toArray());
	}


	public function testEmptyTextShowAll() {
		$qg = new QueryGenerator();
		$qg->setQueryText('');
		$qg->setFields(null);
		$qg->setSelectedFilters(null);
		$qg->setShowResultsForEmptyQuery(true);

		$qs = array('query_string' => array('query' => '*', 'lenient' => true));

		//In order to show all results an empty query works,
		//e.g. curl -XGET 'http://localhost:9200/elastica_ss_module_test_en_us/_search?pretty'
		$expected = array(
			'size' => 10,
			'from' => 0,
			'suggest' => $this->getDefaultSuggest('')
		);

		$this->assertEquals($expected, $qg->generateElasticaQuery()->toArray());
	}


	public function testMultiMatchWithText() {
		$qg = new QueryGenerator();
		$qg->setQueryText('New Zealand');
		$fields = array('Title' => 1, 'Description' => 1);
		$qg->setFields($fields);
		$qg->setSelectedFilters(null);
		$qg->setClasses('FlickrPhotoTO');

		//As the query is not empty it should not matter whether or not the show results for empty
		//query flag is set or not - test with true and false

		$qg->setShowResultsForEmptyQuery(false);
		$qs = array('multi_match' => array(
			'fields' => array('Title','Title.*','Description','Description.*'),
			'type' => 'most_fields',
			'query' => 'New Zealand',
			'lenient' => true
			)
		);
		$expected = array(
			'query' => $qs,
			'size' => 10,
			'from' => 0,
			'suggest' => $this->getDefaultSuggest('New Zealand')
		);

		$this->assertEquals($expected, $qg->generateElasticaQuery()->toArray());

		$qg->setShowResultsForEmptyQuery(true);
		$this->assertEquals($expected, $qg->generateElasticaQuery()->toArray());
	}



	public function testMultiMatchWithNoText() {
		$qg = new QueryGenerator();
		$qg->setQueryText('');
		$fields = array('Title' => 1, 'Description' => 1);
		$qg->setFields($fields);
		$qg->setSelectedFilters(null);
		$qg->setClasses('FlickrPhotoTO');

		//As the query is not empty it should not matter whether or not the show results for empty
		//query flag is set or not - test with true and false

		//Case of empty query, do not show results
		$qg->setShowResultsForEmptyQuery(false);
		$qs = array(
			'multi_match' => array(
				'fields' => array('Title','Title.*','Description','Description.*'),
				'type' => 'most_fields',
				'query' => '',
				'lenient' => true
			)
		);
		$expected = array(
			'query' => $qs,
			'size' => 10,
			'from' => 0,
			'suggest' => $this->getDefaultSuggest('')
		);

		$this->assertEquals($expected, $qg->generateElasticaQuery()->toArray());


		// Now the case of empty query and show results
		$qg->setShowResultsForEmptyQuery(true);
		unset($expected['query']);
		$this->assertEquals($expected, $qg->generateElasticaQuery()->toArray());
	}


	// ---- tests with aggregations ----


	public function testEmptyTextShowNoResultsWithAggregations() {
		$this->assertFalse(false, 'This is not possible - an empty query returns 0 docs and 0 aggregations');
	}


	/*
	Test aggregations with and without text query
	 */
	public function testTextShowResultsWithAggregations() {
		$qg = new QueryGenerator();
		$qg->setQueryText('');
		$qg->setFields(null);
		$qg->setSelectedFilters(null);
		$qg->setShowResultsForEmptyQuery(true);
		$qg->setQueryResultManipulator('FlickrPhotoTOElasticaSearchHelper');
		$aggs = $this->baseAggs();

		//tests are complete
		$expected = array(
			'aggs' => $aggs,
			'size' => 10,
			'from' => 0,
			'sort' => array('TakenAt' => 'desc'),
			'suggest' => $this->getDefaultSuggest('')
		);

		print_r($qg->generateElasticaQuery());

		echo(json_encode($qg->generateElasticaQuery()->toArray()));
		$this->assertEquals($expected, $qg->generateElasticaQuery()->toArray());

		$qg->setQueryText('New Zealand');
		echo(json_encode($qg->generateElasticaQuery()->toArray()));
		unset($expected['sort']);
		$expected['query'] = array('query_string' => array('query' => 'New Zealand', 'lenient' => true));
		$expected['suggest'] = $this->getDefaultSuggest('New Zealand');
		$this->assertEquals($expected, $qg->generateElasticaQuery()->toArray());

		$qg->setShowResultsForEmptyQuery(false);
		$qg->setQueryText('New Zealand');
		echo(json_encode($qg->generateElasticaQuery()->toArray()));
		$expected['query'] = array('query_string' => array('query' => 'New Zealand', 'lenient' => true));
		$this->assertEquals($expected, $qg->generateElasticaQuery()->toArray());
	}


	/*
	Should generate this working query:
	curl -XGET 'http://localhost:9200/elastica_ss_module_test_en_us/_search?pretty' -d '
	{
	  "query": {
	    "filtered": {
	      "filter": {
	        "term": {
	          "ISO": 400
	        }
	      }
	    }
	  },
	  "aggs": {
	    "Aperture": {
	      "terms": {
	        "field": "Aperture",
	        "size": 0,
	        "order": {
	          "_term": "asc"
	        }
	      }
	    },
	    "ShutterSpeed": {
	      "terms": {
	        "field": "ShutterSpeed",
	        "size": 0,
	        "order": {
	          "_term": "asc"
	        }
	      }
	    },
	    "FocalLength35mm": {
	      "terms": {
	        "field": "FocalLength35mm",
	        "size": 0,
	        "order": {
	          "_term": "asc"
	        }
	      }
	    },
	    "ISO": {
	      "terms": {
	        "field": "ISO",
	        "size": 0,
	        "order": {
	          "_term": "asc"
	        }
	      }
	    },
	    "Aspect": {
	      "range": {
	        "field": "AspectRatio",
	        "ranges": [{
	          "from": 1.0e-7,
	          "to": 0.3,
	          "key": "Panoramic"
	        }, {
	          "from": 0.3,
	          "to": 0.9,
	          "key": "Horizontal"
	        }, {
	          "from": 0.9,
	          "to": 1.2,
	          "key": "Square"
	        }, {
	          "from": 1.2,
	          "to": 1.79,
	          "key": "Vertical"
	        }, {
	          "from": 1.79,
	          "to": 10000000,
	          "key": "Tallest"
	        }]
	      }
	    }
	  },
	  "size": 10,
	  "from": 0
	}
	'
	 */
	public function testTextOneFilterAggregate() {
		$qg = new QueryGenerator();
		$qg->setQueryText('');
		$qg->setFields(null);
		$filters = array('ISO' => 400);
		$qg->setSelectedFilters($filters);
		$qg->setShowResultsForEmptyQuery(true);
		$qg->setQueryResultManipulator('FlickrPhotoTOElasticaSearchHelper');
		$aggs = $this->baseAggs();

		//FIXME - query needs removed in this case, leave as a reminder for now until
		//tests are complete
		$expected = array(
			'aggs' => $aggs,
			'size' => 10,
			'from' => 0,
			'query' => array(
				'filtered' => array(
					'filter' => array('term' => array('ISO' => 400))
				)
			),
			'sort' => array('TakenAt' => 'desc'),
			'suggest' => $this->getDefaultSuggest('')
		);

		echo(json_encode($qg->generateElasticaQuery()->toArray()));
		$this->assertEquals($expected, $qg->generateElasticaQuery()->toArray());

		$qg->setQueryText('New Zealand');
		unset($expected['sort']); // use query text search relevance for sorting, ie default Elasticsearch
		$expected['query']['filtered']['query']['query_string'] = array('query' => 'New Zealand', 'lenient' => true);
		$expected['suggest'] = $this->getDefaultSuggest('New Zealand');
		echo(json_encode($qg->generateElasticaQuery()->toArray()));
		$this->assertEquals($expected, $qg->generateElasticaQuery()->toArray());
	}


	public function testTextTwoFilterAggregate() {
		$qg = new QueryGenerator();
		$qg->setQueryText('');
		$qg->setFields(null);
		$filters = array('ISO' => 400, 'Aspect' => 'Square');
		$qg->setSelectedFilters($filters);
		$qg->setShowResultsForEmptyQuery(true);
		$qg->setQueryResultManipulator('FlickrPhotoTOElasticaSearchHelper');
		$aggs = $this->baseAggs();

		//FIXME - query needs removed in this case, leave as a reminder for now until
		//tests are complete
		$expected = array(
			'aggs' => $aggs,
			'size' => 10,
			'from' => 0,
			'query' => array(
				'filtered' => array(
					'filter' =>
					array('and' => array(
						0 => array( 'term' =>  array('ISO' => 400)),
						1 => array( 'range' => array(
							'AspectRatio' => array(
								'gte' => '0.9',
								'lt' => '1.2'
							)
						))
					)
				))
			),
			'sort' => array('TakenAt' => 'desc'),
			'suggest' => $this->getDefaultSuggest('')
		);

		echo(json_encode($qg->generateElasticaQuery()->toArray()));
		$this->assertEquals($expected, $qg->generateElasticaQuery()->toArray());

		$qg->setQueryText('New Zealand');
		unset($expected['sort']); // use query text search relevance for sorting, ie default Elasticsearch
		$expected['query']['filtered']['query']['query_string'] = array('query' => 'New Zealand', 'lenient' => true);
		$expected['suggest'] = $this->getDefaultSuggest('New Zealand');
		$this->assertEquals($expected, $qg->generateElasticaQuery()->toArray());
	}


	public function testTextThreeFilterAggregate() {
		$qg = new QueryGenerator();
		$qg->setQueryText('');
		$qg->setFields(null);
		$filters = array('ISO' => 400, 'Aspect' => 'Square', 'Aperture' => 5.6);
		$qg->setSelectedFilters($filters);
		$qg->setShowResultsForEmptyQuery(true);
		$qg->setQueryResultManipulator('FlickrPhotoTOElasticaSearchHelper');
		$aggs = $this->baseAggs();

		//FIXME - query needs removed in this case, leave as a reminder for now until
		//tests are complete
		$expected = array(
			'aggs' => $aggs,
			'size' => 10,
			'from' => 0,
			'query' => array(
				'filtered' => array('filter' =>
					array('and' => array(
						0 => array( 'term' =>  array('ISO' => 400)),
						1 => array( 'range' => array(
							'AspectRatio' => array(
								'gte' => '0.9',
								'lt' => '1.2'
							)
						)),
						2 => array( 'term' =>  array('Aperture' => 5.6)),
					)
				))
			),
			'sort' => array('TakenAt' => 'desc'),
			'suggest' => $this->getDefaultSuggest('')
		);

		$this->assertEquals($expected, $qg->generateElasticaQuery()->toArray());

		$qg->setQueryText('New Zealand');
		unset($expected['sort']); // use query text search relevance for sorting, ie default Elasticsearch
		$expected['query']['filtered']['query']['query_string'] = array('query' => 'New Zealand', 'lenient' => true);
		$expected['suggest'] = $this->getDefaultSuggest('New Zealand');
		$this->assertEquals($expected, $qg->generateElasticaQuery()->toArray());
	}


	public function testMultiMatchOneFilterAggregate() {
		$qg = new QueryGenerator();
		$qg->setQueryText('');
		$qg->setFields(array('Title' => 2, 'Content' => 1));
		$filters = array('ISO' => 400);
		$qg->setSelectedFilters($filters);
		$qg->setShowResultsForEmptyQuery(true);
		$qg->setQueryResultManipulator('FlickrPhotoTOElasticaSearchHelper');
		$aggs = $this->baseAggs();

		//FIXME - query needs removed in this case, leave as a reminder for now until
		//tests are complete
		$expected = array(
			'aggs' => $aggs,
			'size' => 10,
			'from' => 0,
			'query' => array(
				'filtered' => array(
					'filter' => array('term' => array('ISO' => 400))
				)
			),
			'sort' => array('TakenAt' => 'desc'),
			'suggest' => $this->getDefaultSuggest('')
		);

		echo(json_encode($qg->generateElasticaQuery()->toArray()));
		$this->assertEquals($expected, $qg->generateElasticaQuery()->toArray());

		$qg->setQueryText('New Zealand');
		unset($expected['sort']); // use relevance of search sorting

		$expected['query']['filtered']['query']['multi_match'] = array(
			'query' => 'New Zealand',
			'lenient' => true,
			'fields' => array('Title^2', 'Title.*^2','Content', 'Content.*'),
			'type' => 'most_fields'
		);

		$expected['suggest'] = $this->getDefaultSuggest('New Zealand');
		print_r($qg->generateElasticaQuery()->toArray());
		$this->assertEquals($expected, $qg->generateElasticaQuery()->toArray());
	}



	public function testMultiMatchTwoFilterAggregate() {
		$qg = new QueryGenerator();
		$qg->setQueryText('');
		$qg->setFields(array('Title' => 2, 'Content' => 1));
		$filters = array('ISO' => 400, 'Aspect' => 'Square');
		$qg->setSelectedFilters($filters);
		$qg->setShowResultsForEmptyQuery(true);
		$qg->setQueryResultManipulator('FlickrPhotoTOElasticaSearchHelper');
		$aggs = $this->baseAggs();

		//FIXME - query needs removed in this case, leave as a reminder for now until
		//tests are complete
		$expected = array(
			'aggs' => $aggs,
			'size' => 10,
			'from' => 0,
			'query' => array(
				'filtered' => array(
					'filter' =>
					array('and' => array(
						0 => array( 'term' =>  array('ISO' => 400)),
						1 => array( 'range' => array(
							'AspectRatio' => array(
								'gte' => '0.9',
								'lt' => '1.2'
							)
						))
					)
				))
			),
			'sort' => array('TakenAt' => 'desc'),
			'suggest' => $this->getDefaultSuggest('')
		);

		echo(json_encode($qg->generateElasticaQuery()->toArray()));
		$this->assertEquals($expected, $qg->generateElasticaQuery()->toArray());

		$qg->setQueryText('New Zealand');
		unset($expected['sort']); // use relevance of search sorting
		$expected['query']['filtered']['query']['multi_match'] = array(
			'query' => 'New Zealand',
			'lenient' => true,
			'fields' => array('Title^2', 'Title.*^2','Content', 'Content.*'),
			'type' => 'most_fields'
		);
		$expected['suggest'] = $this->getDefaultSuggest('New Zealand');
		$this->assertEquals($expected, $qg->generateElasticaQuery()->toArray());
	}



	public function testMultiMatchThreeFilterAggregate() {
		$qg = new QueryGenerator();
		$qg->setQueryText('');
		$qg->setFields(array('Title' => 2, 'Content' => 1));
		$filters = array('ISO' => 400, 'Aspect' => 'Square', 'Aperture' => 5.6);
		$qg->setSelectedFilters($filters);
		$qg->setShowResultsForEmptyQuery(true);
		$qg->setQueryResultManipulator('FlickrPhotoTOElasticaSearchHelper');
		$aggs = $this->baseAggs();

		//FIXME - query needs removed in this case, leave as a reminder for now until
		//tests are complete
		$expected = array(
			'aggs' => $aggs,
			'size' => 10,
			'from' => 0,
			'query' => array(
				'filtered' => array('filter' =>
					array('and' => array(
						0 => array( 'term' =>  array('ISO' => 400)),
						1 => array( 'range' => array(
							'AspectRatio' => array(
								'gte' => '0.9',
								'lt' => '1.2'
							)
						)),
						2 => array( 'term' =>  array('Aperture' => 5.6)),
					)
				))
			),
			'sort' => array('TakenAt' => 'desc'),
			'suggest' => $this->getDefaultSuggest('')
		);

		$this->assertEquals($expected, $qg->generateElasticaQuery()->toArray());

		$qg->setQueryText('New Zealand');
		unset($expected['sort']);
		$expected['query']['filtered']['query']['multi_match'] = array(
			'query' => 'New Zealand',
			'lenient' => true,
			'fields' => array('Title^2', 'Title.*^2','Content', 'Content.*'),
			'type' => 'most_fields'
		);
		$expected['suggest'] = $this->getDefaultSuggest('New Zealand');
		$this->assertEquals($expected, $qg->generateElasticaQuery()->toArray());
	}




	// ---- tests for field array to elasticsearch syntax
	public function testConvertWeightedFieldsForElasticaUnaryStrings() {
		$qg = new QueryGenerator();
		$qg->setClasses('FlickrPhotoTO');
		$fields = array('Title' => 1, 'Description' => 1);
		$expected = array('Title', 'Title.*','Description', 'Description.*');
		$this->assertEquals($expected, $qg->convertWeightedFieldsForElastica($fields));
	}


	public function testConvertWeightedFieldsForElasticaMultipleStrings() {
		$qg = new QueryGenerator();
		$qg->setClasses('FlickrPhotoTO');
		$fields = array('Title' => 2, 'Description' => 1);
		$expected = array('Title^2', 'Title.*^2','Description', 'Description.*');
		$this->assertEquals($expected, $qg->convertWeightedFieldsForElastica($fields));
	}


	public function testConvertWeightedFieldsForElasticaTestNonString() {
		$qg = new QueryGenerator();
		$qg->setClasses('FlickrPhotoTO');
		$fields = array('Aperture' => 2, 'FocalLength35mm' => 1);
		$expected = array('Aperture^2', 'FocalLength35mm');
		$this->assertEquals($expected, $qg->convertWeightedFieldsForElastica($fields));
	}


	public function testConvertWeightedFieldsForElasticaNonExistent() {
		$qg = new QueryGenerator();
		$qg->setClasses('FlickrPhotoTO');
		$fields = array('Aperture' => 2, 'FocalLength35mm' => 1, 'Wibble' => 2);
		try {
			$this->assertEquals('This test should fail', $qg->convertWeightedFieldsForElastica($fields));
			$this->fail('An exception should have been thrown as the field Wibble does not exist');
		} catch (Exception $e) {
			$this->assertEquals('Field Wibble does not exist', $e->getMessage());
		}

	}


	public function testSearchFieldsMappingForClasses() {
		$qg = new QueryGenerator();
		$qg->setClasses('FlickrPhotoTO,Page');
		$fields = array('Title' => 2, 'Description' => 1);
		$expected = array('Title^2', 'Title.*^2','Description', 'Description.*');
		$this->assertEquals($expected, $qg->convertWeightedFieldsForElastica($fields));

		$qg->setClasses(array('FlickrPhotoTO','Page'));
		$this->assertEquals($expected, $qg->convertWeightedFieldsForElastica($fields));

	}


	public function testSearchFieldsMappingForClassesCaching() {
		QueryGenerator::resetCacheHitCounter();
		$cache = SS_Cache::factory('elasticsearch');
		// Previous tests may have altered this so start from a known position
		$cache->remove('SEARCHABLE_FIELDS_FlickrPhotoTO_Page');
		$qg = new QueryGenerator();
		$qg->setClasses('FlickrPhotoTO,Page');
		$fields = array('Title' => 2, 'Description' => 1);
		$expected = array('Title^2', 'Title.*^2','Description', 'Description.*');
		$this->assertEquals($expected, $qg->convertWeightedFieldsForElastica($fields));

		//Execute a 2nd time
		$this->assertEquals($expected, $qg->convertWeightedFieldsForElastica($fields));
		//Check for cache hit
		$this->assertEquals(1, QueryGenerator::getCacheHitCounter());

		//Execute a 3rd time
		$this->assertEquals($expected, $qg->convertWeightedFieldsForElastica($fields));
		//Check for cache hit
		$this->assertEquals(2, QueryGenerator::getCacheHitCounter());
	}


	public function testSearchFieldsMappingForSiteTree() {
		$qg = new QueryGenerator();
		$qg->setClasses(null); // select all of site tree classes
		$fields = array('Title' => 2, 'Content' => 1);
		$expected = array('Title^2', 'Title.*^2','Content', 'Content.*');
		$this->assertEquals($expected, $qg->convertWeightedFieldsForElastica($fields));

		echo "--------------------\n";
		$qg->setClasses(array('FlickrPhotoTO','Page'));
		$this->assertEquals($expected, $qg->convertWeightedFieldsForElastica($fields));

	}


	public function testPagination() {
		$qg = new QueryGenerator();
		$qg->setQueryText('New Zealand');
		$qg->setFields(null);
		$qg->setSelectedFilters(null);
		$qg->setPageLength(12);
		$qg->setStart(24);

		//As the query is not empty it should not matter whether or not the show results for empty
		//query flag is set or not - test with true and false

		$qg->setShowResultsForEmptyQuery(false);
		$this->assertEquals(false, $qg->getShowResultsForEmptyQuery());
		$qs = array('query_string' => array('query' => 'New Zealand', 'lenient' => true));
		$expected = array(
			'query' => $qs,
			'size' => 12,
			'from' => 24,
			'suggest' => $this->getDefaultSuggest('New Zealand')
		);

		$this->assertEquals($expected, $qg->generateElasticaQuery()->toArray());

		$qg->setShowResultsForEmptyQuery(true);
		$this->assertEquals(true, $qg->getShowResultsForEmptyQuery());
		$this->assertEquals($expected, $qg->generateElasticaQuery()->toArray());
	}



	/**
	 * Get the basic aggregates that should be returned for the augmenter being tested
	 * @return [type] [description]
	 */
	private function baseAggs() {
		$result = array();
		$result['Aperture'] = array(
			'terms' => array(
				'field' => 'Aperture',
				'size' => 0,
				'order' => array('_term' => 'asc')
			)
		);
		$result['ShutterSpeed'] =  array(
			'terms' => array(
				'field' => 'ShutterSpeed',
				'size' => 0,
				'order' => array('_term' => 'asc')
			)
		);
		$result['FocalLength35mm'] =  array(
			'terms' => array(
				'field' => 'FocalLength35mm',
				'size' => 0,
				'order' => array('_term' => 'asc')
			)
		);
		$result['ISO'] =  array(
			'terms' => array(
				'field' => 'ISO',
				'size' => 0,
				'order' => array('_term' => 'asc')
			)
		);

		$ranges = array();
		$ranges[0] = array('from' => '1.0E-7', 'to' => '0.3', 'key' => 'Panoramic');
		$ranges[1] = array('from' => '0.3', 'to' => '0.9', 'key' => 'Horizontal');
		$ranges[2] = array('from' => '0.9', 'to' => '1.2', 'key' => 'Square');
		$ranges[3] = array('from' => '1.2', 'to' => '1.79', 'key' => 'Vertical');
		$ranges[4] = array('from' => '1.79', 'to' => '10000000', 'key' => 'Tallest');

		$result['Aspect'] =  array(
			'range' => array(
				'field' => 'AspectRatio',
				'ranges' => $ranges
			)
		);
		return $result;
	}


		// ---- tests for the toQuotedCSV function ----
	public function testToQuotedCSVFromString() {
		$expected = "'Bangkok','Nonthaburi','Saraburi','Chiang Mai'";
		$items = 'Bangkok,Nonthaburi,Saraburi,Chiang Mai';
		$quoted = QueryGenerator::convertToQuotedCSV($items);
		$this->assertEquals($expected, $quoted);
	}

	public function testToQuotedCSVFromArray() {
		$expected = "'Bangkok','Nonthaburi','Saraburi','Chiang Mai'";
		$items = array('Bangkok','Nonthaburi','Saraburi','Chiang Mai');
		$quoted = QueryGenerator::convertToQuotedCSV($items);
		$this->assertEquals($expected, $quoted);
	}

	public function testToQuotedCSVEmptyString() {
		$quoted = QueryGenerator::convertToQuotedCSV('');
		$this->assertEquals('', $quoted);
	}

	public function testToQuotedCSVEmptyArray() {
		$quoted = QueryGenerator::convertToQuotedCSV(array());
		$this->assertEquals('', $quoted);
	}

	public function testToQuotedCSVNull() {
		$quoted = QueryGenerator::convertToQuotedCSV(null);
		$this->assertEquals('', $quoted);
	}
}
