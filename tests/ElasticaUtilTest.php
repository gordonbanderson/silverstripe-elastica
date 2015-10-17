<?php

/**
 * Test the functionality of ElasticaUtil class
 * @package elastica
 */
class ElasticaUtiTest extends SapphireTest {


	public function testPairOfConsecutiveIncorrectWords() {
		$sa = $this->getSuggestionArray('New Zealind raalway',
			'new zealand railway',
			'New *Zealand railway*');
		$pair = ElasticaUtil::getPhraseSuggestion($sa);
		$expected = array(
			'suggestedQuery' => 'New Zealand railway',
			'suggestedQueryHighlighted' => 'New *Zealand railway*'
		);
		$this->assertEquals($expected, $pair);
	}


	public function testOneIncorrectWord() {
		$sa = $this->getSuggestionArray('New Zealind',
			'new zealand',
			'New *Zealand*');
		$pair = ElasticaUtil::getPhraseSuggestion($sa);
		$expected = array(
			'suggestedQuery' => 'New Zealand',
			'suggestedQueryHighlighted' => 'New *Zealand*'
		);
		$this->assertEquals($expected, $pair);
	}


	public function testOneIncorrectWordLowerCase() {
		$sa = $this->getSuggestionArray('new zealind',
			'new zealand',
			'new *zealand*');
		$pair = ElasticaUtil::getPhraseSuggestion($sa);
		$expected = array(
			'suggestedQuery' => 'new zealand',
			'suggestedQueryHighlighted' => 'new *zealand*'
		);
		$this->assertEquals($expected, $pair);
	}




/*
	public function testQueryNoSuggestions() {
		$sa = $this->getSuggestionArray('New Zealand','','');
		$pair = ElasticaUtil::getPhraseSuggestion($sa);
		$expected = array(
			'suggestedQuery' => 'New Zealand railway',
			'suggestedQueryHighlighted' => 'New *Zealand railway*'
		);
		$this->assertEquals($expected, $pair);
	}
*/


	/**
	 * Simulate a call to Elastica to get suggestions for a given phrase
	 * @return [type] [description]
	 */
	private function getSuggestionArray($phrase, $suggestion, $highlightedSuggestion) {
		$result = array();
		$suggest1 = array();
		$suggest1['text'] = $phrase;
		$suggest1['offset'] = 0;
		$suggest1['length'] = strlen($phrase);
		$options = array();
		$option0 = array();
		$option0['text'] = $suggestion;
		$option0['highlighted'] = $highlightedSuggestion;

		//For completeness, currently not used
		$option0['score'] = 9.0792E-5;

		$options[0] = $option0;
		$suggest1['options'] = $options;
		array_push($result, $suggest1);
		return $result;
	}
}
