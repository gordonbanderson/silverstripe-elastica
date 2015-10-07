<?php
use \SilverStripe\Elastica\QueryGenerator;

/**
 * Test query generation
 * @package elastica
 */
class QueryGeneratorTest extends SapphireTest {

	public function testTextOnly() {
		$qg = new QueryGenerator();
		$qg->setQueryText('New Zealand');
		$qg->setFields(null);
		$qg->setSelectedFilters(null);

		//As the query is not empty it should not matter whether or not the show results for empty
		//query flag is set or not - test with true and false

		$qg->setShowResultsForEmptyQuery(false);
		$expected = array('query_string' => array('query' => 'New Zealand', 'lenient' => true));
		$this->assertEquals($expected, $qg->generateElasticaQuery()->toArray());

		$qg->setShowResultsForEmptyQuery(true);
		$expected = array('query_string' => array('query' => 'New Zealand', 'lenient' => true));
		$this->assertEquals($expected, $qg->generateElasticaQuery()->toArray());
	}


	public function testEmptyTextShowNone() {
		$qg = new QueryGenerator();
		$qg->setQueryText('');
		$qg->setFields(null);
		$qg->setSelectedFilters(null);
		$qg->setShowResultsForEmptyQuery(false);
		$expected = array('query_string' => array('query' => '', 'lenient' => true));
		$this->assertEquals($expected, $qg->generateElasticaQuery()->toArray());
	}


	public function testEmptyTextShowAll() {
		$qg = new QueryGenerator();
		$qg->setQueryText('');
		$qg->setFields(null);
		$qg->setSelectedFilters(null);
		$qg->setShowResultsForEmptyQuery(true);

		//FIXME ideally want entirely empty query here but this works as a stopgap
		$expected = array('query_string' => array('query' => '*', 'lenient' => true));
		$this->assertEquals($expected, $qg->generateElasticaQuery()->toArray());
	}


	public function testEmptyTextShowNoneWithAggregations() {
		$qg = new QueryGenerator();
		$qg->setQueryText('');
		$qg->setFields(null);
		$qg->setSelectedFilters(null);
		$qg->setShowResultsForEmptyQuery(false);
		$qg->setQueryResultManipulator('FlickrPhotoElasticaSearchHelper');
		$aggs = $this->baseAggs();
		$expected = array(
			'aggs' => $aggs,
			'size' => 10,
			'from' => 0,
			'query' => array('query_string' => array('query' => '', 'lenient' => true)),
			'sort' => array('TakenAt' => 'desc')
		);

		echo "RESULT:";
		print_r(json_encode($qg->generateElasticaQuery()->toArray()));
		die;

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
		$ranges[1] = array('from' => '03', 'to' => '0.9', 'key' => 'Horizontal');
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
}
