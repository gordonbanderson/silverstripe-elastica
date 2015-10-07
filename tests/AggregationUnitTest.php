<?php

use \FlickrPhotoElasticaSearchHelper;
use \SilverStripe\Elastica\ElasticSearcher;

/**
 * Test the functionality of the Searchable extension
 * @package elastica
 */
class AggregationUnitTest extends ElasticsearchBaseTest {
	//public static $fixture_file = 'elastica/tests/lotsOfPhotos.yml';
	public static $fixture_file = 'elastica/tests/lotsOfPhotos.yml';


/*

//Test code generator


		$aggCtr = 0;
		foreach ($aggregations as $agg) {
			echo "\n";
			echo "//Asserting name of aggregate as {$agg->Name}\n";
			echo '$agg = $aggregations['.$aggCtr."];\n";
			echo '$this->assertEquals("'.$agg->Name."\", '{$agg->Name}');\n"; //FIXME
			echo '$buckets = $agg->Buckets->toArray();'."\n";
			$aggCtr++;
			$bucketCtr = 0;
			foreach ($agg->Buckets->toArray() as $bucket) {
				echo "\n//Asserting aggregate of {$agg->Name}, {$bucket->Key} has count {$bucket->DocumentCount}\n";
				echo '$this->assertEquals("'.$bucket->Key.'", $buckets['.$bucketCtr."]->Key);\n";
				echo '$this->assertEquals('.$bucket->DocumentCount.', $buckets['.$bucketCtr."]->DocumentCount);\n";

				$bucketCtr++;
			}
		}


		$aggCtr = 0;
		foreach ($aggregations as $agg) {
			echo "\n";
			echo "//Asserting name of aggregate as {$agg->Name}\n";
			echo '$agg = $aggregations['.$aggCtr."];\n";
			echo '$this->assertEquals("'.$agg->Name."\", '{$agg->Name}');\n";
			echo '$buckets = $agg->Buckets->toArray();'."\n";
			$aggCtr++;
			$bucketCtr = 0;
			echo '$bucketSum = 0;'."\n";
			foreach ($agg->Buckets->toArray() as $bucket) {
				echo "\n//Asserting aggregate of {$agg->Name}, {$bucket->Key} has count {$bucket->DocumentCount}\n";
				echo '$this->assertEquals("'.$bucket->Key.'", $buckets['.$bucketCtr."]->Key);\n";
				echo '$this->assertEquals('.$bucket->DocumentCount.', $buckets['.$bucketCtr."]->DocumentCount);\n";
				echo '$bucketSum += $buckets['.$bucketCtr.']->DocumentCount;'."\n";
				$bucketCtr++;
			}
			echo '$this->assertEquals(100, $bucketSum);'."\n";
		}


 */




	public function testGetResults() {
		// several checks needed  here including aggregations
	}


	public function testAllFieldsQuery() {
		//Use a text search against all fields
		$resultList = $this->search('New Zealand', null);
		$this->assertEquals(10, $resultList->count());

		// check the query formation
		$query = $resultList->getQuery()->toArray();
		$this->assertEquals(0, $query['from']);
		$this->assertEquals(10, $query['size']);

		$sort = array('TakenAt' => 'desc');
		$this->assertEquals($sort, $query['sort']);

		$q = new stdClass();
		$q->Test = 'wibble';

		print_r($resultList->getQuery());

		$this->assertEquals($q, $query['query']['match_all']);

		$aggs = array();
		$aggs['Aperture'] = array();
		$aggs['ShutterSpeed'] = array();
		$aggs['FocalLength35mm'] = array();
		$aggs['ISO'] = array();
		$aggs['Aspect'] = array();

		/*


		 */
		$this->assertEquals($aggs, $query['aggs']);

		$this->assertEquals($expected, $resultList->getQuery()->toArray());

		// check the aggregate results
		$aggregations = $resultList->getAggregations();

		//Asserting name of aggregate as ISO
		$agg = $aggregations[0];
		$this->assertEquals("ISO", $agg->Name);
		$buckets = $agg->Buckets->toArray();

		//Asserting aggregate of ISO, 64 has count 5
		$this->assertEquals("64", $buckets[0]->Key);
		$this->assertEquals(5, $buckets[0]->DocumentCount);

		//Asserting aggregate of ISO, 100 has count 11
		$this->assertEquals("100", $buckets[1]->Key);
		$this->assertEquals(11, $buckets[1]->DocumentCount);

		//Asserting aggregate of ISO, 200 has count 12
		$this->assertEquals("200", $buckets[2]->Key);
		$this->assertEquals(12, $buckets[2]->DocumentCount);

		//Asserting aggregate of ISO, 400 has count 13
		$this->assertEquals("400", $buckets[3]->Key);
		$this->assertEquals(13, $buckets[3]->DocumentCount);

		//Asserting aggregate of ISO, 800 has count 15
		$this->assertEquals("800", $buckets[4]->Key);
		$this->assertEquals(15, $buckets[4]->DocumentCount);

		//Asserting aggregate of ISO, 1600 has count 13
		$this->assertEquals("1600", $buckets[5]->Key);
		$this->assertEquals(13, $buckets[5]->DocumentCount);

		//Asserting aggregate of ISO, 2000 has count 11
		$this->assertEquals("2000", $buckets[6]->Key);
		$this->assertEquals(11, $buckets[6]->DocumentCount);

		//Asserting aggregate of ISO, 3200 has count 19
		$this->assertEquals("3200", $buckets[7]->Key);
		$this->assertEquals(19, $buckets[7]->DocumentCount);

		//Asserting name of aggregate as Focal Length
		$agg = $aggregations[1];
		$this->assertEquals("Focal Length", $agg->Name);
		$buckets = $agg->Buckets->toArray();

		//Asserting aggregate of Focal Length, 24 has count 12
		$this->assertEquals("24", $buckets[0]->Key);
		$this->assertEquals(12, $buckets[0]->DocumentCount);

		//Asserting aggregate of Focal Length, 50 has count 11
		$this->assertEquals("50", $buckets[1]->Key);
		$this->assertEquals(11, $buckets[1]->DocumentCount);

		//Asserting aggregate of Focal Length, 80 has count 11
		$this->assertEquals("80", $buckets[2]->Key);
		$this->assertEquals(11, $buckets[2]->DocumentCount);

		//Asserting aggregate of Focal Length, 90 has count 20
		$this->assertEquals("90", $buckets[3]->Key);
		$this->assertEquals(20, $buckets[3]->DocumentCount);

		//Asserting aggregate of Focal Length, 120 has count 11
		$this->assertEquals("120", $buckets[4]->Key);
		$this->assertEquals(11, $buckets[4]->DocumentCount);

		//Asserting aggregate of Focal Length, 150 has count 17
		$this->assertEquals("150", $buckets[5]->Key);
		$this->assertEquals(17, $buckets[5]->DocumentCount);

		//Asserting aggregate of Focal Length, 200 has count 17
		$this->assertEquals("200", $buckets[6]->Key);
		$this->assertEquals(17, $buckets[6]->DocumentCount);

		//Asserting name of aggregate as Shutter Speed
		$agg = $aggregations[2];
		$this->assertEquals("Shutter Speed", $agg->Name);
		$buckets = $agg->Buckets->toArray();

		//Asserting aggregate of Shutter Speed, 2/250 has count 17
		$this->assertEquals("2/250", $buckets[0]->Key);
		$this->assertEquals(17, $buckets[0]->DocumentCount);

		//Asserting aggregate of Shutter Speed, 1/100 has count 15
		$this->assertEquals("1/100", $buckets[1]->Key);
		$this->assertEquals(15, $buckets[1]->DocumentCount);

		//Asserting aggregate of Shutter Speed, 1/30 has count 17
		$this->assertEquals("1/30", $buckets[2]->Key);
		$this->assertEquals(17, $buckets[2]->DocumentCount);

		//Asserting aggregate of Shutter Speed, 1/15 has count 9
		$this->assertEquals("1/15", $buckets[3]->Key);
		$this->assertEquals(9, $buckets[3]->DocumentCount);

		//Asserting aggregate of Shutter Speed, 1/2 has count 18
		$this->assertEquals("1/2", $buckets[4]->Key);
		$this->assertEquals(18, $buckets[4]->DocumentCount);

		//Asserting aggregate of Shutter Speed, 2 has count 11
		$this->assertEquals("2", $buckets[5]->Key);
		$this->assertEquals(11, $buckets[5]->DocumentCount);

		//Asserting aggregate of Shutter Speed, 6 has count 12
		$this->assertEquals("6", $buckets[6]->Key);
		$this->assertEquals(12, $buckets[6]->DocumentCount);

		//Asserting name of aggregate as Aperture
		$agg = $aggregations[3];
		$this->assertEquals("Aperture", $agg->Name);
		$buckets = $agg->Buckets->toArray();

		//Asserting aggregate of Aperture, 2.8 has count 20
		$this->assertEquals("2.8", $buckets[0]->Key);
		$this->assertEquals(20, $buckets[0]->DocumentCount);

		//Asserting aggregate of Aperture, 5.6 has count 23
		$this->assertEquals("5.6", $buckets[1]->Key);
		$this->assertEquals(23, $buckets[1]->DocumentCount);

		//Asserting aggregate of Aperture, 11 has count 17
		$this->assertEquals("11", $buckets[2]->Key);
		$this->assertEquals(17, $buckets[2]->DocumentCount);

		//Asserting aggregate of Aperture, 16 has count 16
		$this->assertEquals("16", $buckets[3]->Key);
		$this->assertEquals(16, $buckets[3]->DocumentCount);

		//Asserting aggregate of Aperture, 22 has count 23
		$this->assertEquals("22", $buckets[4]->Key);
		$this->assertEquals(23, $buckets[4]->DocumentCount);

		//Asserting name of aggregate as Aspect
		$agg = $aggregations[4];
		$this->assertEquals("Aspect", $agg->Name);
		$buckets = $agg->Buckets->toArray();

		//Asserting aggregate of Aspect, Panoramic has count 9
		$this->assertEquals("Panoramic", $buckets[0]->Key);
		$this->assertEquals(9, $buckets[0]->DocumentCount);

		//Asserting aggregate of Aspect, Horizontal has count 31
		$this->assertEquals("Horizontal", $buckets[1]->Key);
		$this->assertEquals(31, $buckets[1]->DocumentCount);

		//Asserting aggregate of Aspect, Square has count 16
		$this->assertEquals("Square", $buckets[2]->Key);
		$this->assertEquals(16, $buckets[2]->DocumentCount);

		//Asserting aggregate of Aspect, Vertical has count 38
		$this->assertEquals("Vertical", $buckets[3]->Key);
		$this->assertEquals(38, $buckets[3]->DocumentCount);

		//Asserting aggregate of Aspect, Tallest has count 5
		$this->assertEquals("Tallest", $buckets[4]->Key);
		$this->assertEquals(5, $buckets[4]->DocumentCount);

	}


	public function testAllFieldsEmptyQuery() {
		//Use a text search against all fields
		$resultList = $this->search('', null);
		$this->assertEquals(10, $resultList->count());

		//check query
		$query = $resultList->getQuery()->toArray();
		$this->assertEquals(0, $query['from']);
		$this->assertEquals(10, $query['size']);

		$sort = array('TakenAt' => 'desc');
		$this->assertEquals($sort, $query['sort']);

		$q = new stdClass();
		$q->Test = 'wibble';

		print_r($resultList->getQuery());

		$this->assertEquals($q, $query['query']['match_all']);

		$aggs = array();
		$aggs['Aperture'] = array();
		$aggs['ShutterSpeed'] = array();
		$aggs['FocalLength35mm'] = array();
		$aggs['ISO'] = array();
		$aggs['Aspect'] = array();

		//check aggregations
		$aggregations = $resultList->getAggregations();
		$aggCtr = 0;
		//Asserting name of aggregate as ISO
		$agg = $aggregations[0];
		$this->assertEquals("ISO", $agg->Name);
		$buckets = $agg->Buckets->toArray();
		$bucketSum = 0;

		//Asserting aggregate of ISO, 64 has count 5
		$this->assertEquals("64", $buckets[0]->Key);
		$this->assertEquals(5, $buckets[0]->DocumentCount);
		$bucketSum += $buckets[0]->DocumentCount;

		//Asserting aggregate of ISO, 100 has count 11
		$this->assertEquals("100", $buckets[1]->Key);
		$this->assertEquals(11, $buckets[1]->DocumentCount);
		$bucketSum += $buckets[1]->DocumentCount;

		//Asserting aggregate of ISO, 200 has count 12
		$this->assertEquals("200", $buckets[2]->Key);
		$this->assertEquals(12, $buckets[2]->DocumentCount);
		$bucketSum += $buckets[2]->DocumentCount;

		//Asserting aggregate of ISO, 400 has count 13
		$this->assertEquals("400", $buckets[3]->Key);
		$this->assertEquals(13, $buckets[3]->DocumentCount);
		$bucketSum += $buckets[3]->DocumentCount;

		//Asserting aggregate of ISO, 800 has count 16
		$this->assertEquals("800", $buckets[4]->Key);
		$this->assertEquals(16, $buckets[4]->DocumentCount);
		$bucketSum += $buckets[4]->DocumentCount;

		//Asserting aggregate of ISO, 1600 has count 13
		$this->assertEquals("1600", $buckets[5]->Key);
		$this->assertEquals(13, $buckets[5]->DocumentCount);
		$bucketSum += $buckets[5]->DocumentCount;

		//Asserting aggregate of ISO, 2000 has count 11
		$this->assertEquals("2000", $buckets[6]->Key);
		$this->assertEquals(11, $buckets[6]->DocumentCount);
		$bucketSum += $buckets[6]->DocumentCount;

		//Asserting aggregate of ISO, 3200 has count 19
		$this->assertEquals("3200", $buckets[7]->Key);
		$this->assertEquals(19, $buckets[7]->DocumentCount);
		$bucketSum += $buckets[7]->DocumentCount;
		$this->assertEquals(100, $bucketSum);

		//Asserting name of aggregate as Focal Length
		$agg = $aggregations[1];
		$this->assertEquals("Focal Length", $agg->Name);
		$buckets = $agg->Buckets->toArray();
		$bucketSum = 0;

		//Asserting aggregate of Focal Length, 24 has count 12
		$this->assertEquals("24", $buckets[0]->Key);
		$this->assertEquals(12, $buckets[0]->DocumentCount);
		$bucketSum += $buckets[0]->DocumentCount;

		//Asserting aggregate of Focal Length, 50 has count 11
		$this->assertEquals("50", $buckets[1]->Key);
		$this->assertEquals(11, $buckets[1]->DocumentCount);
		$bucketSum += $buckets[1]->DocumentCount;

		//Asserting aggregate of Focal Length, 80 has count 11
		$this->assertEquals("80", $buckets[2]->Key);
		$this->assertEquals(11, $buckets[2]->DocumentCount);
		$bucketSum += $buckets[2]->DocumentCount;

		//Asserting aggregate of Focal Length, 90 has count 20
		$this->assertEquals("90", $buckets[3]->Key);
		$this->assertEquals(20, $buckets[3]->DocumentCount);
		$bucketSum += $buckets[3]->DocumentCount;

		//Asserting aggregate of Focal Length, 120 has count 12
		$this->assertEquals("120", $buckets[4]->Key);
		$this->assertEquals(12, $buckets[4]->DocumentCount);
		$bucketSum += $buckets[4]->DocumentCount;

		//Asserting aggregate of Focal Length, 150 has count 17
		$this->assertEquals("150", $buckets[5]->Key);
		$this->assertEquals(17, $buckets[5]->DocumentCount);
		$bucketSum += $buckets[5]->DocumentCount;

		//Asserting aggregate of Focal Length, 200 has count 17
		$this->assertEquals("200", $buckets[6]->Key);
		$this->assertEquals(17, $buckets[6]->DocumentCount);
		$bucketSum += $buckets[6]->DocumentCount;
		$this->assertEquals(100, $bucketSum);

		//Asserting name of aggregate as Shutter Speed
		$agg = $aggregations[2];
		$this->assertEquals("Shutter Speed", $agg->Name);
		$buckets = $agg->Buckets->toArray();
		$bucketSum = 0;

		//Asserting aggregate of Shutter Speed, 2/250 has count 17
		$this->assertEquals("2/250", $buckets[0]->Key);
		$this->assertEquals(17, $buckets[0]->DocumentCount);
		$bucketSum += $buckets[0]->DocumentCount;

		//Asserting aggregate of Shutter Speed, 1/100 has count 15
		$this->assertEquals("1/100", $buckets[1]->Key);
		$this->assertEquals(15, $buckets[1]->DocumentCount);
		$bucketSum += $buckets[1]->DocumentCount;

		//Asserting aggregate of Shutter Speed, 1/30 has count 17
		$this->assertEquals("1/30", $buckets[2]->Key);
		$this->assertEquals(17, $buckets[2]->DocumentCount);
		$bucketSum += $buckets[2]->DocumentCount;

		//Asserting aggregate of Shutter Speed, 1/15 has count 10
		$this->assertEquals("1/15", $buckets[3]->Key);
		$this->assertEquals(10, $buckets[3]->DocumentCount);
		$bucketSum += $buckets[3]->DocumentCount;

		//Asserting aggregate of Shutter Speed, 1/2 has count 18
		$this->assertEquals("1/2", $buckets[4]->Key);
		$this->assertEquals(18, $buckets[4]->DocumentCount);
		$bucketSum += $buckets[4]->DocumentCount;

		//Asserting aggregate of Shutter Speed, 2 has count 11
		$this->assertEquals("2", $buckets[5]->Key);
		$this->assertEquals(11, $buckets[5]->DocumentCount);
		$bucketSum += $buckets[5]->DocumentCount;

		//Asserting aggregate of Shutter Speed, 6 has count 12
		$this->assertEquals("6", $buckets[6]->Key);
		$this->assertEquals(12, $buckets[6]->DocumentCount);
		$bucketSum += $buckets[6]->DocumentCount;
		$this->assertEquals(100, $bucketSum);

		//Asserting name of aggregate as Aperture
		$agg = $aggregations[3];
		$this->assertEquals("Aperture", $agg->Name);
		$buckets = $agg->Buckets->toArray();
		$bucketSum = 0;

		//Asserting aggregate of Aperture, 2.8 has count 21
		$this->assertEquals("2.8", $buckets[0]->Key);
		$this->assertEquals(21, $buckets[0]->DocumentCount);
		$bucketSum += $buckets[0]->DocumentCount;

		//Asserting aggregate of Aperture, 5.6 has count 23
		$this->assertEquals("5.6", $buckets[1]->Key);
		$this->assertEquals(23, $buckets[1]->DocumentCount);
		$bucketSum += $buckets[1]->DocumentCount;

		//Asserting aggregate of Aperture, 11 has count 17
		$this->assertEquals("11", $buckets[2]->Key);
		$this->assertEquals(17, $buckets[2]->DocumentCount);
		$bucketSum += $buckets[2]->DocumentCount;

		//Asserting aggregate of Aperture, 16 has count 16
		$this->assertEquals("16", $buckets[3]->Key);
		$this->assertEquals(16, $buckets[3]->DocumentCount);
		$bucketSum += $buckets[3]->DocumentCount;

		//Asserting aggregate of Aperture, 22 has count 23
		$this->assertEquals("22", $buckets[4]->Key);
		$this->assertEquals(23, $buckets[4]->DocumentCount);
		$bucketSum += $buckets[4]->DocumentCount;
		$this->assertEquals(100, $bucketSum);

		//Asserting name of aggregate as Aspect
		$agg = $aggregations[4];
		$this->assertEquals("Aspect", $agg->Name);
		$buckets = $agg->Buckets->toArray();
		$bucketSum = 0;

		//Asserting aggregate of Aspect, Panoramic has count 9
		$this->assertEquals("Panoramic", $buckets[0]->Key);
		$this->assertEquals(9, $buckets[0]->DocumentCount);
		$bucketSum += $buckets[0]->DocumentCount;

		//Asserting aggregate of Aspect, Horizontal has count 31
		$this->assertEquals("Horizontal", $buckets[1]->Key);
		$this->assertEquals(31, $buckets[1]->DocumentCount);
		$bucketSum += $buckets[1]->DocumentCount;

		//Asserting aggregate of Aspect, Square has count 16
		$this->assertEquals("Square", $buckets[2]->Key);
		$this->assertEquals(16, $buckets[2]->DocumentCount);
		$bucketSum += $buckets[2]->DocumentCount;

		//Asserting aggregate of Aspect, Vertical has count 39
		$this->assertEquals("Vertical", $buckets[3]->Key);
		$this->assertEquals(39, $buckets[3]->DocumentCount);
		$bucketSum += $buckets[3]->DocumentCount;

		//Asserting aggregate of Aspect, Tallest has count 5
		$this->assertEquals("Tallest", $buckets[4]->Key);
		$this->assertEquals(5, $buckets[4]->DocumentCount);
		$bucketSum += $buckets[4]->DocumentCount;
		$this->assertEquals(100, $bucketSum);

	}


	/*
	Search for an empty query against the Title and Description fields
	 */
	public function testAggregationWithEmptyQuery() {
		$resultList = $this->search('');

		//assert there are actually some results
		$this->assertGreaterThan(0,$resultList->getTotalItems());
		$aggregations = $resultList->getAggregations()->toArray();

		/*
		For all of the aggregates, all results are returned due to empty query string, so the number
		of aggregates should always add up to 100.  Check some values at the database level for
		further confirmation also
		FIXME - finish DB checks
		 */

		//Asserting name of aggregate as ISO
		$agg = $aggregations[0];
		$this->assertEquals("ISO", $agg->Name);
		$buckets = $agg->Buckets->toArray();
		$bucketSum = 0;

		//Asserting aggregate of ISO, 64 has count 5
		$this->assertEquals("64", $buckets[0]->Key);
		$this->assertEquals(5, $buckets[0]->DocumentCount);
		$this->assertEquals(FlickrPhoto::get()->filter('ISO',64)->count(),5);
		$bucketSum += $buckets[0]->DocumentCount;

		//Asserting aggregate of ISO, 100 has count 11
		$this->assertEquals("100", $buckets[1]->Key);
		$this->assertEquals(11, $buckets[1]->DocumentCount);
		$this->assertEquals(FlickrPhoto::get()->filter('ISO',100)->count(),11);
		$bucketSum += $buckets[1]->DocumentCount;

		//Asserting aggregate of ISO, 200 has count 12
		$this->assertEquals("200", $buckets[2]->Key);
		$this->assertEquals(12, $buckets[2]->DocumentCount);
		$this->assertEquals(FlickrPhoto::get()->filter('ISO',200)->count(),12);
		$bucketSum += $buckets[2]->DocumentCount;

		//Asserting aggregate of ISO, 400 has count 13
		$this->assertEquals("400", $buckets[3]->Key);
		$this->assertEquals(13, $buckets[3]->DocumentCount);
		$this->assertEquals(FlickrPhoto::get()->filter('ISO',400)->count(),13);
		$bucketSum += $buckets[3]->DocumentCount;

		//Asserting aggregate of ISO, 800 has count 16
		$this->assertEquals("800", $buckets[4]->Key);
		$this->assertEquals(16, $buckets[4]->DocumentCount);
		$this->assertEquals(FlickrPhoto::get()->filter('ISO',800)->count(),16);
		$bucketSum += $buckets[4]->DocumentCount;

		//Asserting aggregate of ISO, 1600 has count 13
		$this->assertEquals("1600", $buckets[5]->Key);
		$this->assertEquals(13, $buckets[5]->DocumentCount);
		$this->assertEquals(FlickrPhoto::get()->filter('ISO',1600)->count(),13);
		$bucketSum += $buckets[5]->DocumentCount;

		//Asserting aggregate of ISO, 2000 has count 11
		$this->assertEquals("2000", $buckets[6]->Key);
		$this->assertEquals(11, $buckets[6]->DocumentCount);
		$this->assertEquals(FlickrPhoto::get()->filter('ISO',2000)->count(),11);
		$bucketSum += $buckets[6]->DocumentCount;

		//Asserting aggregate of ISO, 3200 has count 19
		$this->assertEquals("3200", $buckets[7]->Key);
		$this->assertEquals(19, $buckets[7]->DocumentCount);
		$this->assertEquals(FlickrPhoto::get()->filter('ISO',3200)->count(),19);
		$bucketSum += $buckets[7]->DocumentCount;
		$this->assertEquals(100, $bucketSum);

		//Asserting name of aggregate as Focal Length
		$agg = $aggregations[1];
		$this->assertEquals("Focal Length", $agg->Name);
		$buckets = $agg->Buckets->toArray();
		$bucketSum = 0;

		//Asserting aggregate of Focal Length, 24 has count 12
		$this->assertEquals("24", $buckets[0]->Key);
		$this->assertEquals(12, $buckets[0]->DocumentCount);
		$this->assertEquals(FlickrPhoto::get()->filter('FocalLength35mm',24)->count(),12);
		$bucketSum += $buckets[0]->DocumentCount;

		//Asserting aggregate of Focal Length, 50 has count 11
		$this->assertEquals("50", $buckets[1]->Key);
		$this->assertEquals(11, $buckets[1]->DocumentCount);
		$this->assertEquals(FlickrPhoto::get()->filter('FocalLength35mm',50)->count(),11);
		$bucketSum += $buckets[1]->DocumentCount;

		//Asserting aggregate of Focal Length, 80 has count 11
		$this->assertEquals("80", $buckets[2]->Key);
		$this->assertEquals(11, $buckets[2]->DocumentCount);
		$this->assertEquals(FlickrPhoto::get()->filter('FocalLength35mm',80)->count(),11);
		$bucketSum += $buckets[2]->DocumentCount;

		//Asserting aggregate of Focal Length, 90 has count 20
		$this->assertEquals("90", $buckets[3]->Key);
		$this->assertEquals(20, $buckets[3]->DocumentCount);
		$this->assertEquals(FlickrPhoto::get()->filter('FocalLength35mm',90)->count(),20);
		$bucketSum += $buckets[3]->DocumentCount;

		//Asserting aggregate of Focal Length, 120 has count 12
		$this->assertEquals("120", $buckets[4]->Key);
		$this->assertEquals(12, $buckets[4]->DocumentCount);
		$this->assertEquals(FlickrPhoto::get()->filter('FocalLength35mm',120)->count(),12);
		$bucketSum += $buckets[4]->DocumentCount;

		//Asserting aggregate of Focal Length, 150 has count 17
		$this->assertEquals("150", $buckets[5]->Key);
		$this->assertEquals(17, $buckets[5]->DocumentCount);
		$this->assertEquals(FlickrPhoto::get()->filter('FocalLength35mm',150)->count(),17);
		$bucketSum += $buckets[5]->DocumentCount;

		//Asserting aggregate of Focal Length, 200 has count 17
		$this->assertEquals("200", $buckets[6]->Key);
		$this->assertEquals(17, $buckets[6]->DocumentCount);
		$this->assertEquals(FlickrPhoto::get()->filter('FocalLength35mm',200)->count(),17);
		$bucketSum += $buckets[6]->DocumentCount;
		$this->assertEquals(100, $bucketSum);

		//Asserting name of aggregate as Shutter Speed
		$agg = $aggregations[2];
		$this->assertEquals("Shutter Speed", $agg->Name);
		$buckets = $agg->Buckets->toArray();
		$bucketSum = 0;

		//Asserting aggregate of Shutter Speed, 2/250 has count 17
		$this->assertEquals("2/250", $buckets[0]->Key);
		$this->assertEquals(17, $buckets[0]->DocumentCount);
		$bucketSum += $buckets[0]->DocumentCount;

		//Asserting aggregate of Shutter Speed, 1/100 has count 15
		$this->assertEquals("1/100", $buckets[1]->Key);
		$this->assertEquals(15, $buckets[1]->DocumentCount);
		$bucketSum += $buckets[1]->DocumentCount;

		//Asserting aggregate of Shutter Speed, 1/30 has count 17
		$this->assertEquals("1/30", $buckets[2]->Key);
		$this->assertEquals(17, $buckets[2]->DocumentCount);
		$bucketSum += $buckets[2]->DocumentCount;

		//Asserting aggregate of Shutter Speed, 1/15 has count 10
		$this->assertEquals("1/15", $buckets[3]->Key);
		$this->assertEquals(10, $buckets[3]->DocumentCount);
		$bucketSum += $buckets[3]->DocumentCount;

		//Asserting aggregate of Shutter Speed, 1/2 has count 18
		$this->assertEquals("1/2", $buckets[4]->Key);
		$this->assertEquals(18, $buckets[4]->DocumentCount);
		$bucketSum += $buckets[4]->DocumentCount;

		//Asserting aggregate of Shutter Speed, 2 has count 11
		$this->assertEquals("2", $buckets[5]->Key);
		$this->assertEquals(11, $buckets[5]->DocumentCount);
		$bucketSum += $buckets[5]->DocumentCount;

		//Asserting aggregate of Shutter Speed, 6 has count 12
		$this->assertEquals("6", $buckets[6]->Key);
		$this->assertEquals(12, $buckets[6]->DocumentCount);
		$bucketSum += $buckets[6]->DocumentCount;
		$this->assertEquals(100, $bucketSum);

		//Asserting name of aggregate as Aperture
		$agg = $aggregations[3];
		$this->assertEquals("Aperture", $agg->Name);
		$buckets = $agg->Buckets->toArray();
		$bucketSum = 0;

		//Asserting aggregate of Aperture, 2.8 has count 21
		$this->assertEquals("2.8", $buckets[0]->Key);
		$this->assertEquals(21, $buckets[0]->DocumentCount);
		$bucketSum += $buckets[0]->DocumentCount;

		//Asserting aggregate of Aperture, 5.6 has count 23
		$this->assertEquals("5.6", $buckets[1]->Key);
		$this->assertEquals(23, $buckets[1]->DocumentCount);
		$bucketSum += $buckets[1]->DocumentCount;

		//Asserting aggregate of Aperture, 11 has count 17
		$this->assertEquals("11", $buckets[2]->Key);
		$this->assertEquals(17, $buckets[2]->DocumentCount);
		$bucketSum += $buckets[2]->DocumentCount;

		//Asserting aggregate of Aperture, 16 has count 16
		$this->assertEquals("16", $buckets[3]->Key);
		$this->assertEquals(16, $buckets[3]->DocumentCount);
		$bucketSum += $buckets[3]->DocumentCount;

		//Asserting aggregate of Aperture, 22 has count 23
		$this->assertEquals("22", $buckets[4]->Key);
		$this->assertEquals(23, $buckets[4]->DocumentCount);
		$bucketSum += $buckets[4]->DocumentCount;
		$this->assertEquals(100, $bucketSum);

		//Asserting name of aggregate as Aspect
		$agg = $aggregations[4];
		$this->assertEquals("Aspect", $agg->Name);
		$buckets = $agg->Buckets->toArray();
		$bucketSum = 0;

		//Asserting aggregate of Aspect, Panoramic has count 9
		$this->assertEquals("Panoramic", $buckets[0]->Key);
		$this->assertEquals(9, $buckets[0]->DocumentCount);
		$bucketSum += $buckets[0]->DocumentCount;

		//Asserting aggregate of Aspect, Horizontal has count 31
		$this->assertEquals("Horizontal", $buckets[1]->Key);
		$this->assertEquals(31, $buckets[1]->DocumentCount);
		$bucketSum += $buckets[1]->DocumentCount;

		//Asserting aggregate of Aspect, Square has count 16
		$this->assertEquals("Square", $buckets[2]->Key);
		$this->assertEquals(16, $buckets[2]->DocumentCount);
		$bucketSum += $buckets[2]->DocumentCount;

		//Asserting aggregate of Aspect, Vertical has count 39
		$this->assertEquals("Vertical", $buckets[3]->Key);
		$this->assertEquals(39, $buckets[3]->DocumentCount);
		$bucketSum += $buckets[3]->DocumentCount;

		//Asserting aggregate of Aspect, Tallest has count 5
		$this->assertEquals("Tallest", $buckets[4]->Key);
		$this->assertEquals(5, $buckets[4]->DocumentCount);
		$bucketSum += $buckets[4]->DocumentCount;
		$this->assertEquals(100, $bucketSum);
	}


	/*
	Search for the query 'New Zealand' against the Title and Description fields
	 */
	public function testAggregationWithQuery() {
		$resultList = $this->search('New Zealand');
		$aggregations = $resultList->getAggregations()->toArray();


		//Asserting name of aggregate as ISO
		$agg = $aggregations[0];
		$this->assertEquals("ISO", $agg->Name);
		$buckets = $agg->Buckets->toArray();

		//Asserting aggregate of ISO, 64 has count 5
		$this->assertEquals("64", $buckets[0]->Key);
		$this->assertEquals(5, $buckets[0]->DocumentCount);

		//Asserting aggregate of ISO, 100 has count 11
		$this->assertEquals("100", $buckets[1]->Key);
		$this->assertEquals(11, $buckets[1]->DocumentCount);

		//Asserting aggregate of ISO, 200 has count 12
		$this->assertEquals("200", $buckets[2]->Key);
		$this->assertEquals(12, $buckets[2]->DocumentCount);

		//Asserting aggregate of ISO, 400 has count 13
		$this->assertEquals("400", $buckets[3]->Key);
		$this->assertEquals(13, $buckets[3]->DocumentCount);

		//Asserting aggregate of ISO, 800 has count 16
		$this->assertEquals("800", $buckets[4]->Key);
		$this->assertEquals(16, $buckets[4]->DocumentCount);

		//Asserting aggregate of ISO, 1600 has count 13
		$this->assertEquals("1600", $buckets[5]->Key);
		$this->assertEquals(13, $buckets[5]->DocumentCount);

		//Asserting aggregate of ISO, 2000 has count 11
		$this->assertEquals("2000", $buckets[6]->Key);
		$this->assertEquals(11, $buckets[6]->DocumentCount);

		//Asserting aggregate of ISO, 3200 has count 19
		$this->assertEquals("3200", $buckets[7]->Key);
		$this->assertEquals(19, $buckets[7]->DocumentCount);

		//Asserting name of aggregate as Focal Length
		$agg = $aggregations[1];
		$this->assertEquals("Focal Length", $agg->Name);
		$buckets = $agg->Buckets->toArray();

		//Asserting aggregate of Focal Length, 24 has count 12
		$this->assertEquals("24", $buckets[0]->Key);
		$this->assertEquals(12, $buckets[0]->DocumentCount);

		//Asserting aggregate of Focal Length, 50 has count 11
		$this->assertEquals("50", $buckets[1]->Key);
		$this->assertEquals(11, $buckets[1]->DocumentCount);

		//Asserting aggregate of Focal Length, 80 has count 11
		$this->assertEquals("80", $buckets[2]->Key);
		$this->assertEquals(11, $buckets[2]->DocumentCount);

		//Asserting aggregate of Focal Length, 90 has count 20
		$this->assertEquals("90", $buckets[3]->Key);
		$this->assertEquals(20, $buckets[3]->DocumentCount);

		//Asserting aggregate of Focal Length, 120 has count 12
		$this->assertEquals("120", $buckets[4]->Key);
		$this->assertEquals(12, $buckets[4]->DocumentCount);

		//Asserting aggregate of Focal Length, 150 has count 17
		$this->assertEquals("150", $buckets[5]->Key);
		$this->assertEquals(17, $buckets[5]->DocumentCount);

		//Asserting aggregate of Focal Length, 200 has count 17
		$this->assertEquals("200", $buckets[6]->Key);
		$this->assertEquals(17, $buckets[6]->DocumentCount);

		//Asserting name of aggregate as Shutter Speed
		$agg = $aggregations[2];
		$this->assertEquals("Shutter Speed", $agg->Name);
		$buckets = $agg->Buckets->toArray();

		//Asserting aggregate of Shutter Speed, 2/250 has count 17
		$this->assertEquals("2/250", $buckets[0]->Key);
		$this->assertEquals(17, $buckets[0]->DocumentCount);

		//Asserting aggregate of Shutter Speed, 1/100 has count 15
		$this->assertEquals("1/100", $buckets[1]->Key);
		$this->assertEquals(15, $buckets[1]->DocumentCount);

		//Asserting aggregate of Shutter Speed, 1/30 has count 17
		$this->assertEquals("1/30", $buckets[2]->Key);
		$this->assertEquals(17, $buckets[2]->DocumentCount);

		//Asserting aggregate of Shutter Speed, 1/15 has count 10
		$this->assertEquals("1/15", $buckets[3]->Key);
		$this->assertEquals(10, $buckets[3]->DocumentCount);

		//Asserting aggregate of Shutter Speed, 1/2 has count 18
		$this->assertEquals("1/2", $buckets[4]->Key);
		$this->assertEquals(18, $buckets[4]->DocumentCount);

		//Asserting aggregate of Shutter Speed, 2 has count 11
		$this->assertEquals("2", $buckets[5]->Key);
		$this->assertEquals(11, $buckets[5]->DocumentCount);

		//Asserting aggregate of Shutter Speed, 6 has count 12
		$this->assertEquals("6", $buckets[6]->Key);
		$this->assertEquals(12, $buckets[6]->DocumentCount);

		//Asserting name of aggregate as Aperture
		$agg = $aggregations[3];
		$this->assertEquals("Aperture", $agg->Name);
		$buckets = $agg->Buckets->toArray();

		//Asserting aggregate of Aperture, 2.8 has count 21
		$this->assertEquals("2.8", $buckets[0]->Key);
		$this->assertEquals(21, $buckets[0]->DocumentCount);

		//Asserting aggregate of Aperture, 5.6 has count 23
		$this->assertEquals("5.6", $buckets[1]->Key);
		$this->assertEquals(23, $buckets[1]->DocumentCount);

		//Asserting aggregate of Aperture, 11 has count 17
		$this->assertEquals("11", $buckets[2]->Key);
		$this->assertEquals(17, $buckets[2]->DocumentCount);

		//Asserting aggregate of Aperture, 16 has count 16
		$this->assertEquals("16", $buckets[3]->Key);
		$this->assertEquals(16, $buckets[3]->DocumentCount);

		//Asserting aggregate of Aperture, 22 has count 23
		$this->assertEquals("22", $buckets[4]->Key);
		$this->assertEquals(23, $buckets[4]->DocumentCount);

		//Asserting name of aggregate as Aspect
		$agg = $aggregations[4];
		$this->assertEquals("Aspect", $agg->Name);
		$buckets = $agg->Buckets->toArray();

		//Asserting aggregate of Aspect, Panoramic has count 9
		$this->assertEquals("Panoramic", $buckets[0]->Key);
		$this->assertEquals(9, $buckets[0]->DocumentCount);

		//Asserting aggregate of Aspect, Horizontal has count 31
		$this->assertEquals("Horizontal", $buckets[1]->Key);
		$this->assertEquals(31, $buckets[1]->DocumentCount);

		//Asserting aggregate of Aspect, Square has count 16
		$this->assertEquals("Square", $buckets[2]->Key);
		$this->assertEquals(16, $buckets[2]->DocumentCount);

		//Asserting aggregate of Aspect, Vertical has count 39
		$this->assertEquals("Vertical", $buckets[3]->Key);
		$this->assertEquals(39, $buckets[3]->DocumentCount);

		//Asserting aggregate of Aspect, Tallest has count 5
		$this->assertEquals("Tallest", $buckets[4]->Key);
		$this->assertEquals(5, $buckets[4]->DocumentCount);
	}


	public function testOneAggregateSelected() {
		$fields = array('Title' => 1, 'Description' => 1);
		$resultList = $this->search('New Zealand', $fields);
		$originalAggregations = $resultList->getAggregations()->toArray();

		$filters = array('ISO' => 3200);
		$resultListFiltered = $this->search('New Zealand', $fields,$filters);
		$filteredAggregations = $resultListFiltered->getAggregations()->toArray();

		$this->checkDrillingDownHasHappened($filteredAggregations, $originalAggregations);
	}


	public function testTwoAggregatesSelected() {
		$fields = array('Title' => 1, 'Description' => 1);
		$resultList = $this->search('New Zealand', $fields, array('ISO' => 400));
		$originalAggregations = $resultList->getAggregations()->toArray();

		$filters = array('ISO' => 400, 'Aspect' => 'Vertical' );
		$resultListFiltered = $this->search('New Zealand', $fields,$filters);
		$filteredAggregations = $resultListFiltered->getAggregations()->toArray();

		$this->checkDrillingDownHasHappened($filteredAggregations, $originalAggregations);
	}


	public function testThreeAggregatesSelected() {
		$fields = array('Title' => 1, 'Description' => 1);
		$resultList = $this->search('New Zealand', $fields, array('ISO' => 400,
										'Aspect' => 'Vertical'));
		$originalAggregations = $resultList->getAggregations()->toArray();

		$filters = array('ISO' => 400, 'Aspect' => 'Vertical', 'Aperture' => 5 );
		$resultListFiltered = $this->search('New Zealand', $fields,$filters);
		$filteredAggregations = $resultListFiltered->getAggregations()->toArray();

		$this->checkDrillingDownHasHappened($filteredAggregations, $originalAggregations);
	}


	private function checkDrillingDownHasHappened($filteredAggregations, $originalAggregations) {
		$aggCtr = 0;

		$names = array();
		foreach ($filteredAggregations as $filteredAgg) {
			echo "NAME:{$filteredAgg->Name}\n";

			$origAgg = $originalAggregations[$aggCtr];
			$bucketCtr = 0;
			$origBuckets = $origAgg->Buckets->toArray();
			$filteredBuckets = $filteredAgg->Buckets->toArray();

			$origCounts = array();
			$filteredCounts = array();

			foreach ($origBuckets as $bucket) {
				$origCounts[$bucket->Key] = $bucket->DocumentCount;
			}

			foreach ($filteredBuckets as $bucket) {
				$filteredCounts[$bucket->Key] = $bucket->DocumentCount;
			}

			echo "pairs\n";
			print_r($origCounts);
			print_r($filteredCounts);
			$akf = array_keys($filteredCounts);
			echo "AKF SIZE:".sizeof($akf);

			foreach ($akf as $aggregateKey) {
				$this->assertGreaterThanOrEqual(
					$filteredCounts[$aggregateKey], $origCounts[$aggregateKey]
				);
			}

			$aggCtr++;



		}
	}


	/*
	ResultList and ElasticSearcher both have accessors to the aggregates.  Check they are the same
	 */
	public function testGetAggregations() {
		$es = new \ElasticSearcher();
		$es->setStart(0);
		$es->setPageLength(10);
		//$es->addFilter('IsInSiteTree', false);
		$es->setClasses('FlickrPhoto');
		$es->setQueryResultManipulator('FlickrPhotoElasticaSearchHelper');
		$resultList = $es->search('New Zealand');
		$this->assertEquals($resultList->getAggregations(), $es->getAggregations());
	}


	private function testAggregationNonExistentField() {
		$this->fail('Not yet implemented');
	}


	/**
	 * Test searching
	 * http://stackoverflow.com/questions/28305250/elasticsearch-customize-score-for-synonyms-stemming
	 */
	private function search($query,$fields = array('Title' => 1, 'Description' => 1),
								$filters = array()) {
		$es = new ElasticSearcher();
		$es->setStart(0);
		$es->setPageLength(10);
		//$es->addFilter('IsInSiteTree', false);
		$es->setClasses('FlickrPhoto');
		$es->setQueryResultManipulator('FlickrPhotoElasticaSearchHelper');

		//Add filters
		foreach ($filters as $key => $value) {
			$es->addFilter($key,$value);
		}
		$resultList = $es->search($query, $fields);

		$ctr = 0;

		echo "{$resultList->count()} items found searching for '$query'\n\n";
		foreach ($resultList as $result) {
			$ctr++;
			echo("($ctr) ".$result->Title."\n");
			if ($result->SearchHighlightsByField->Content) {
				foreach ($result->SearchHighlightsByField->Content as $highlight) {
					echo("- ".$highlight->Snippet);
				}
			}
		}



		return $resultList;
	}

}
