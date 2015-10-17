<?php

/**
 * Test the functionality of ElasticaUtil class
 * @package elastica
 */
class ElasticaUtiTest extends SapphireTest {


	public function test1() {
		$sa = $this->getSuggestionArray();
		$pair = ElasticUtil::getPhraseSuggestion($sa);
		$this->assertEquals(array(), $pair));
	}


	/**
	 * Simulate a call to Elastica to get suggestions for a given phrase
	 * @return [type] [description]
	 */
	private function getSuggestionArray() {
		$phrase = 'New Zealind raalway';
		$result = array();
		$suggest1 = array();
		$suggest1['text'] = $phrase;
		$suggest1['offset'] = 0;
		$suggest1['length'] = strlen($phrase);
		$options = array();
		$option0 = array();
		$option0['text'] = 'new zealand railway';
		$option0['highlighted'] = '*zealand railway*';

		//For completeness, currently not used
		$option0['score'] = 9.0792E-5;

		$options[0] = $option0;
		$suggest1['options'] = $options;
		array_push($result, $suggest1);
		return $result;
		/*
			/*
	Array
(
    [0] => Array
        (
            [text] => New Zealind raalway
            [offset] => 0
            [length] => 19
            [options] => Array
                (
                    [0] => Array
                        (
                            [text] => new zealand railway
                            [highlighted] => new *zealand railway*
                            [score] => 9.079269E-5
                        )
		 */
	}
}
