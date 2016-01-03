<?php

use SilverStripe\Elastica\ElasticSearcher;
use SilverStripe\Elastica\QueryGenerator;

/**
 * Test the functionality of the Searchable extension
 * @package elastica
 */
class AggregationUnitTest extends ElasticsearchBaseTest {
	public static $fixture_file = 'elastica/tests/lotsOfPhotos.yml';


	public function testAllFieldsQuery() {
		//Use a text search against all fields
		$resultList = $this->search('New Zealand', null);
		$this->assertEquals(10, $resultList->count());

		// check the query formation
		$query = $resultList->getQuery()->toArray();
		$this->assertEquals(0, $query['from']);
		$this->assertEquals(10, $query['size']);

		$sort = array('TakenAt' => 'desc');

		$this->assertFalse(isset($query['sort']), 'Sort should not be set as text is being used');

		$expected = array('query' => 'New Zealand', 'lenient' => 1);
		$this->assertEquals($expected, $query['query']['query_string']);


		// check the aggregate results
		$aggregations = $resultList->getAggregations();
		$this->aggregationByName($aggregations);

		error_log('AGGS');
		foreach ($aggregations as $agg) {
			error_log($agg->Name);
			foreach ($agg->Buckets as $bucket) {
				error_log("\t$bucket->Key -> $bucket->DocumentCount");
			}
		}

		//Asserting name of aggregate as ISO
		$agg = $aggregations['ISO'];
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
		$agg = $aggregations['Focal Length'];
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
		$agg = $aggregations['Shutter Speed'];
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

		//Asserting aggregate of Shutter Speed, 1 has count 1
		$this->assertEquals("1", $buckets[5]->Key);
		$this->assertEquals(1, $buckets[5]->DocumentCount);

		//Asserting aggregate of Shutter Speed, 2 has count 10
		$this->assertEquals("2", $buckets[6]->Key);
		$this->assertEquals(10, $buckets[6]->DocumentCount);

		//Asserting aggregate of Shutter Speed, 6 has count 12
		$this->assertEquals("6", $buckets[7]->Key);
		$this->assertEquals(12, $buckets[7]->DocumentCount);

		//Asserting name of aggregate as Aperture
		$agg = $aggregations['Aperture'];
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
		$agg = $aggregations['Aspect'];
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
		$this->assertFalse(isset($query['query']));

		$aggs = array();
		$aggs['Aperture'] = array();
		$aggs['ShutterSpeed'] = array();
		$aggs['FocalLength35mm'] = array();
		$aggs['ISO'] = array();
		$aggs['Aspect'] = array();

		//check aggregations
		$aggregations = $resultList->getAggregations();
		$this->aggregationByName($aggregations);

		//Asserting name of aggregate as ISO
		$agg = $aggregations['ISO'];
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
		$agg = $aggregations['Focal Length'];
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
		$agg = $aggregations['Shutter Speed'];
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

		//Asserting aggregate of Shutter Speed, 1 has count 1
		$this->assertEquals("1", $buckets[5]->Key);
		$this->assertEquals(1, $buckets[5]->DocumentCount);

		//Asserting aggregate of Shutter Speed, 2 has count 10
		$this->assertEquals("2", $buckets[6]->Key);
		$this->assertEquals(10, $buckets[6]->DocumentCount);

		//Asserting aggregate of Shutter Speed, 6 has count 12
		$this->assertEquals("6", $buckets[7]->Key);
		$this->assertEquals(12, $buckets[7]->DocumentCount);

		//Asserting name of aggregate as Aperture
		$agg = $aggregations['Aperture'];
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
		$agg = $aggregations['Aspect'];
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
		$this->aggregationByName($aggregations);

		/*
		For all of the aggregates, all results are returned due to empty query string, so the number
		of aggregates should always add up to 100.  Check some values at the database level for
		further confirmation also
		FIXME - finish DB checks
		 */

		//Asserting name of aggregate as ISO
		$agg = $aggregations['ISO'];
		$this->assertEquals("ISO", $agg->Name);
		$buckets = $agg->Buckets->toArray();
		$bucketSum = 0;

		//Asserting aggregate of ISO, 64 has count 5
		$this->assertEquals("64", $buckets[0]->Key);
		$this->assertEquals(5, $buckets[0]->DocumentCount);
		$this->assertEquals(FlickrPhotoTO::get()->filter('ISO',64)->count(),5);
		$bucketSum += $buckets[0]->DocumentCount;

		//Asserting aggregate of ISO, 100 has count 11
		$this->assertEquals("100", $buckets[1]->Key);
		$this->assertEquals(11, $buckets[1]->DocumentCount);
		$this->assertEquals(FlickrPhotoTO::get()->filter('ISO',100)->count(),11);
		$bucketSum += $buckets[1]->DocumentCount;

		//Asserting aggregate of ISO, 200 has count 12
		$this->assertEquals("200", $buckets[2]->Key);
		$this->assertEquals(12, $buckets[2]->DocumentCount);
		$this->assertEquals(FlickrPhotoTO::get()->filter('ISO',200)->count(),12);
		$bucketSum += $buckets[2]->DocumentCount;

		//Asserting aggregate of ISO, 400 has count 13
		$this->assertEquals("400", $buckets[3]->Key);
		$this->assertEquals(13, $buckets[3]->DocumentCount);
		$this->assertEquals(FlickrPhotoTO::get()->filter('ISO',400)->count(),13);
		$bucketSum += $buckets[3]->DocumentCount;

		//Asserting aggregate of ISO, 800 has count 16
		$this->assertEquals("800", $buckets[4]->Key);
		$this->assertEquals(16, $buckets[4]->DocumentCount);
		$this->assertEquals(FlickrPhotoTO::get()->filter('ISO',800)->count(),16);
		$bucketSum += $buckets[4]->DocumentCount;

		//Asserting aggregate of ISO, 1600 has count 13
		$this->assertEquals("1600", $buckets[5]->Key);
		$this->assertEquals(13, $buckets[5]->DocumentCount);
		$this->assertEquals(FlickrPhotoTO::get()->filter('ISO',1600)->count(),13);
		$bucketSum += $buckets[5]->DocumentCount;

		//Asserting aggregate of ISO, 2000 has count 11
		$this->assertEquals("2000", $buckets[6]->Key);
		$this->assertEquals(11, $buckets[6]->DocumentCount);
		$this->assertEquals(FlickrPhotoTO::get()->filter('ISO',2000)->count(),11);
		$bucketSum += $buckets[6]->DocumentCount;

		//Asserting aggregate of ISO, 3200 has count 19
		$this->assertEquals("3200", $buckets[7]->Key);
		$this->assertEquals(19, $buckets[7]->DocumentCount);
		$this->assertEquals(FlickrPhotoTO::get()->filter('ISO',3200)->count(),19);
		$bucketSum += $buckets[7]->DocumentCount;
		$this->assertEquals(100, $bucketSum);

		//Asserting name of aggregate as Focal Length
		$agg = $aggregations['Focal Length'];
		$this->assertEquals("Focal Length", $agg->Name);
		$buckets = $agg->Buckets->toArray();
		$bucketSum = 0;

		//Asserting aggregate of Focal Length, 24 has count 12
		$this->assertEquals("24", $buckets[0]->Key);
		$this->assertEquals(12, $buckets[0]->DocumentCount);
		$this->assertEquals(FlickrPhotoTO::get()->filter('FocalLength35mm',24)->count(),12);
		$bucketSum += $buckets[0]->DocumentCount;

		//Asserting aggregate of Focal Length, 50 has count 11
		$this->assertEquals("50", $buckets[1]->Key);
		$this->assertEquals(11, $buckets[1]->DocumentCount);
		$this->assertEquals(FlickrPhotoTO::get()->filter('FocalLength35mm',50)->count(),11);
		$bucketSum += $buckets[1]->DocumentCount;

		//Asserting aggregate of Focal Length, 80 has count 11
		$this->assertEquals("80", $buckets[2]->Key);
		$this->assertEquals(11, $buckets[2]->DocumentCount);
		$this->assertEquals(FlickrPhotoTO::get()->filter('FocalLength35mm',80)->count(),11);
		$bucketSum += $buckets[2]->DocumentCount;

		//Asserting aggregate of Focal Length, 90 has count 20
		$this->assertEquals("90", $buckets[3]->Key);
		$this->assertEquals(20, $buckets[3]->DocumentCount);
		$this->assertEquals(FlickrPhotoTO::get()->filter('FocalLength35mm',90)->count(),20);
		$bucketSum += $buckets[3]->DocumentCount;

		//Asserting aggregate of Focal Length, 120 has count 12
		$this->assertEquals("120", $buckets[4]->Key);
		$this->assertEquals(12, $buckets[4]->DocumentCount);
		$this->assertEquals(FlickrPhotoTO::get()->filter('FocalLength35mm',120)->count(),12);
		$bucketSum += $buckets[4]->DocumentCount;

		//Asserting aggregate of Focal Length, 150 has count 17
		$this->assertEquals("150", $buckets[5]->Key);
		$this->assertEquals(17, $buckets[5]->DocumentCount);
		$this->assertEquals(FlickrPhotoTO::get()->filter('FocalLength35mm',150)->count(),17);
		$bucketSum += $buckets[5]->DocumentCount;

		//Asserting aggregate of Focal Length, 200 has count 17
		$this->assertEquals("200", $buckets[6]->Key);
		$this->assertEquals(17, $buckets[6]->DocumentCount);
		$this->assertEquals(FlickrPhotoTO::get()->filter('FocalLength35mm',200)->count(),17);
		$bucketSum += $buckets[6]->DocumentCount;
		$this->assertEquals(100, $bucketSum);

		//Asserting name of aggregate as Shutter Speed
		$agg = $aggregations['Shutter Speed'];
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

		//Asserting aggregate of Shutter Speed, 1 has count 1
		$this->assertEquals("1", $buckets[5]->Key);
		$this->assertEquals(1, $buckets[5]->DocumentCount);

		//Asserting aggregate of Shutter Speed, 2 has count 10
		$this->assertEquals("2", $buckets[6]->Key);
		$this->assertEquals(10, $buckets[6]->DocumentCount);

		//Asserting aggregate of Shutter Speed, 6 has count 12
		$this->assertEquals("6", $buckets[7]->Key);
		$this->assertEquals(12, $buckets[7]->DocumentCount);

		//Asserting name of aggregate as Aperture
		$agg = $aggregations['Aperture'];
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
		$agg = $aggregations['Aspect'];
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
		$this->aggregationByName($aggregations);

		//Asserting name of aggregate as ISO
		$agg = $aggregations['ISO'];

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
		$agg = $aggregations['Focal Length'];
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
		$agg = $aggregations['Shutter Speed'];
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

		//Asserting aggregate of Shutter Speed, 1 has count 1
		$this->assertEquals("1", $buckets[5]->Key);
		$this->assertEquals(1, $buckets[5]->DocumentCount);

		//Asserting aggregate of Shutter Speed, 2 has count 10
		$this->assertEquals("2", $buckets[6]->Key);
		$this->assertEquals(10, $buckets[6]->DocumentCount);

		//Asserting aggregate of Shutter Speed, 6 has count 12
		$this->assertEquals("6", $buckets[7]->Key);
		$this->assertEquals(12, $buckets[7]->DocumentCount);

		//Asserting name of aggregate as Aperture
		$agg = $aggregations['Aperture'];
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
		$agg = $aggregations['Aspect'];
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
		$ct = FlickrPhotoTO::get()->count();

		$fields = array('Title' => 1, 'Description' => 1);
		$resultList = $this->search('New Zealand', $fields, array('ISO' => 400,
										'Aspect' => 'Vertical'));
		$originalAggregations = $resultList->getAggregations()->toArray();

		$filters = array('ISO' => 400, 'Aspect' => 'Vertical', 'Aperture' => 5 );
		$resultListFiltered = $this->search('New Zealand', $fields,$filters);
		$filteredAggregations = $resultListFiltered->getAggregations()->toArray();

		$this->checkDrillingDownHasHappened($filteredAggregations, $originalAggregations);
	}


	/*
	Shutter speed is a special case as values are manipulated in order to maintain correct sort order
	 */
	public function testAggregateShutterSpeed() {
		$fields = array('Title' => 1, 'Description' => 1);
		$resultList = $this->search('', $fields, array('ShutterSpeed' => '1/100'));
		$aggregations = $resultList->getAggregations()->toArray();

		// 15 instances in the YML file
		$this->assertEquals(15, $resultList->getTotalItems());

		// Shutter speed of 1 second is a special case, only 1 case
		$resultList = $this->search('Image taken from page 471', $fields, array('ShutterSpeed' => '1'));
		$this->assertEquals(1, $resultList->getTotalItems());

		// test outlying vals and ensure they do not crash the code
		$resultList = $this->search('Image taken from page 471', $fields, array('ShutterSpeed' => ''));
		$this->assertEquals(0, $resultList->getTotalItems());

		$resultList = $this->search('Image taken from page 471', $fields, array('ShutterSpeed' => '/'));
		$this->assertEquals(0, $resultList->getTotalItems());

		$resultList = $this->search('Image taken from page 471', $fields, array('ShutterSpeed' => '2/'));
		$this->assertEquals(0, $resultList->getTotalItems());

		$resultList = $this->search('Image taken from page 471', $fields, array('ShutterSpeed' => '/2'));
		$this->assertEquals(0, $resultList->getTotalItems());
	}


	private function checkDrillingDownHasHappened($filteredAggregations, $originalAggregations) {
		$aggCtr = 0;

		foreach ($filteredAggregations as $filteredAgg) {
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

			$akf = array_keys($filteredCounts);

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
		$es = new ElasticSearcher();
		$es->setStart(0);
		$es->setPageLength(10);
		//$es->addFilter('IsInSiteTree', false);
		$es->setClasses('FlickrPhotoTO');
		$es->setQueryResultManipulator('FlickrPhotoTOElasticaSearchHelper');
		$resultList = $es->search('New Zealand');
		$this->assertEquals($resultList->getAggregations(), $es->getAggregations());
	}


	public function testAggregationNonExistentField() {
		$filters = array();
		$es = new ElasticSearcher();
		$es->setStart(0);
		$es->setPageLength(10);
		//$es->addFilter('IsInSiteTree', false);
		$es->setClasses('FlickrPhotoTO');
		$es->setQueryResultManipulator('FlickrPhotoTOElasticaSearchHelper');

		//Add filters
		foreach ($filters as $key => $value) {
			$es->addFilter($key,$value);
		}

		$es->showResultsForEmptySearch();

		try {
			$resultList = $es->search('whatever', array('Wibble' => 2));
			$this->fail('The field Wibble should cause this to error out with an exception');
		} catch (Exception $e) {
			$this->assertEquals('Field Wibble does not exist', $e->getMessage());
		}

	}


	/**
	 * Test searching
	 * http://stackoverflow.com/questions/28305250/elasticsearch-customize-score-for-synonyms-stemming
	 */
	private function search($queryText,$fields = array('Title' => 1, 'Description' => 1),
		$filters = array()) {
		$es = new ElasticSearcher();
		$es->setStart(0);
		$es->setPageLength(10);
		$es->setClasses('FlickrPhotoTO');
		$es->setQueryResultManipulator('FlickrPhotoTOElasticaSearchHelper');

		//Add filters
		foreach ($filters as $key => $value) {
			error_log("ADDING FILTER RESULT $key, $value");
			$es->addFilter($key,$value);
		}

		$es->showResultsForEmptySearch();

		$resultList = $es->search($queryText, $fields);

		return $resultList;
	}


	/**
	 * Change array keyed by number to that of aggregation name.
	 * Order of aggs returns differs on Travis for reasons unknown
	 * @param  array &$aggregations Referenced aggregations to tweak
	 */
	private function aggregationByName(&$aggregations) {
		$ctr = 0;
		foreach ($aggregations as $agg) {
			$aggregations[$agg->Name] = $agg;
			unset($aggregations[$ctr]);
			$ctr++;
		}
	}

}
