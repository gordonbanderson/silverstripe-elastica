<?php
use \SilverStripe\Elastica\QueryGenerator;

/**
 * Test query generation
 * @package elastica
 */
class QueryGeneratorTest extends ElasticsearchBaseTest {
	//public static $fixture_file = 'elastica/tests/ElasticaTest.yml';
	public static $fixture_file = 'elastica/tests/lotsOfPhotos.yml';

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
			'from' => 0
		);

		$this->assertEquals($expected, $qg->generateElasticaQuery()->toArray());

		$qg->setShowResultsForEmptyQuery(true);
		$this->assertEquals($expected, $qg->generateElasticaQuery()->toArray());
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
			'from' => 0
		);

		$this->assertEquals($expected, $qg->generateElasticaQuery()->toArray());
	}


	public function testEmptyTextShowAll() {
		$qg = new QueryGenerator();
		$qg->setQueryText('');
		$qg->setFields(null);
		$qg->setSelectedFilters(null);
		$qg->setShowResultsForEmptyQuery(true);

		//FIXME ideally want entirely empty query here but this works as a stopgap
		$qs = array('query_string' => array('query' => '*', 'lenient' => true));
		$expected = array(
			'query' => array('test' => 'FIXME - REMOVE, NOT REQUIRED'),
			'size' => 10,
			'from' => 0
		);

		$this->assertEquals($expected, $qg->generateElasticaQuery()->toArray());
	}


	public function testMultiMatchWithText() {
		$qg = new QueryGenerator();
		$qg->setQueryText('New Zealand');
		$fields = array('Title' => 1, 'Description' => 1);
		$qg->setFields($fields);
		$qg->setSelectedFilters(null);
		$qg->setClasses('FlickrPhoto');

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
			'from' => 0
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
		$qg->setClasses('FlickrPhoto');

		//As the query is not empty it should not matter whether or not the show results for empty
		//query flag is set or not - test with true and false

		//Case of empty query, do not show results
		$qg->setShowResultsForEmptyQuery(false);
		$qs = array('multi_match' => array(
			'fields' => array('Title','Title.*','Description','Description.*'),
			'type' => 'most_fields',
			'query' => '',
			'lenient' => true
			)
		);
		$expected = array(
			'query' => $qs,
			'size' => 10,
			'from' => 0
		);
		$this->assertEquals($expected, $qg->generateElasticaQuery()->toArray());


		// Now the case of empty query and show results
		$qg->setShowResultsForEmptyQuery(true);
		unset($expected['query']);
		$expected['query'] = array('match_all' => new \stdClass());
		$this->assertEquals($expected, $qg->generateElasticaQuery()->toArray());

		print_r(json_encode($qg->generateElasticaQuery()->toArray()));
	}


	// ---- tests with aggregations ----


	public function testEmptyTextShowNoResultsWithAggregations() {
		$qg = new QueryGenerator();
		$qg->setQueryText('');
		$qg->setFields(null);
		$qg->setSelectedFilters(null);
		$qg->setShowResultsForEmptyQuery(false);
		$qg->setQueryResultManipulator('FlickrPhotoElasticaSearchHelper');
		$aggs = $this->baseAggs();

		//FIXME - query needs removed in this case, leave as a reminder for now until
		//tests are complete
		$expected = array(
			'aggs' => $aggs,
			'size' => 10,
			'from' => 0,
			'query' => array('test' => 'FIXME - SHOULD BE EMPTY QUOTES'),
			'sort' => array('TakenAt' => 'desc')
		);

		echo "RESULT:";
		print_r(json_encode($qg->generateElasticaQuery()->toArray()));

		$this->assertEquals($expected, $qg->generateElasticaQuery()->toArray());
	}



	public function testEmptyTextShowResultsWithAggregations() {
		$qg = new QueryGenerator();
		$qg->setQueryText('');
		$qg->setFields(null);
		$qg->setSelectedFilters(null);
		$qg->setShowResultsForEmptyQuery(false);
		$qg->setQueryResultManipulator('FlickrPhotoElasticaSearchHelper');
		$aggs = $this->baseAggs();

		//FIXME - query needs removed in this case, leave as a reminder for now until
		//tests are complete
		$expected = array(
			'aggs' => $aggs,
			'size' => 10,
			'from' => 0,
			'sort' => array('TakenAt' => 'desc'),
			'query' => array('test' => 'FIXME - REMOVE, NOT REQUIRED'),

		);

		echo "RESULT:";
		print_r(json_encode($qg->generateElasticaQuery()->toArray()));

		$this->assertEquals($expected, $qg->generateElasticaQuery()->toArray());
	}




	// ---- tests for field array to elasticsearch syntax
	public function testConvertWeightedFieldsForElasticaUnaryStrings() {
		$qg = new QueryGenerator();
		$qg->setClasses('FlickrPhoto');
		$fields = array('Title' => 1, 'Description' => 1);
		$expected = array('Title', 'Title.*','Description', 'Description.*');
		$this->assertEquals($expected, $qg->convertWeightedFieldsForElastica($fields));
	}


	public function testConvertWeightedFieldsForElasticaMultipleStrings() {
		$qg = new QueryGenerator();
		$qg->setClasses('FlickrPhoto');
		$fields = array('Title' => 2, 'Description' => 1);
		$expected = array('Title^2', 'Title.*^2','Description', 'Description.*');
		$this->assertEquals($expected, $qg->convertWeightedFieldsForElastica($fields));
	}


	public function testConvertWeightedFieldsForElasticaTestNonString() {
		$qg = new QueryGenerator();
		$qg->setClasses('FlickrPhoto');
		$fields = array('Aperture' => 2, 'FocalLength35mm' => 1);
		$expected = array('Aperture^2', 'FocalLength35mm');
		$this->assertEquals($expected, $qg->convertWeightedFieldsForElastica($fields));
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
		$quoted = ElasticSearcher::convertToQuotedCSV($items);
		$this->assertEquals($expected, $quoted);
	}

	public function testToQuotedCSVFromArray() {
		$expected = "'Bangkok','Nonthaburi','Saraburi','Chiang Mai'";
		$items = array('Bangkok','Nonthaburi','Saraburi','Chiang Mai');
		$quoted = ElasticSearcher::convertToQuotedCSV($items);
		$this->assertEquals($expected, $quoted);
	}

	public function testToQuotedCSVEmptyString() {
		$quoted = ElasticSearcher::convertToQuotedCSV('');
		$this->assertEquals('', $quoted);
	}

	public function testToQuotedCSVEmptyArray() {
		$quoted = ElasticSearcher::convertToQuotedCSV(array());
		$this->assertEquals('', $quoted);
	}

	public function testToQuotedCSVNull() {
		$quoted = ElasticSearcher::convertToQuotedCSV(null);
		$this->assertEquals('', $quoted);
	}
}
