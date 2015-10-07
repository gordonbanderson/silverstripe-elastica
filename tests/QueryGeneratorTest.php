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
		$expected = array('query_string' => array('query' => 'New Zealand', 'lenient' => true));
		$this->assertEquals($expected, $qg->generateElasticaQuery()->toArray());
	}

}
