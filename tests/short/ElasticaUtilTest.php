<?php

use SilverStripe\Elastica\ElasticaUtil;

/**
 * Test the functionality of ElasticaUtil class.
 */
class ElasticaUtilTest extends ElasticsearchTestBase
{
    public function testHumanReadableFalse()
    {
        $this->assertEquals('No', ElasticaUtil::showBooleanHumanReadable(false));
    }

    public function testHumanReadableTrue()
    {
        $this->assertEquals('No', ElasticaUtil::showBooleanHumanReadable(false));
    }

    public function testPairOfConsecutiveIncorrectWords()
    {
        $sa = $this->getSuggestionArray('New Zealind raalway',
            'new zealand railway',
            'new <strong class="hl">zealand railway</strong>');

        $pair = ElasticaUtil::getPhraseSuggestion($sa);
        $expected = array(
            'suggestedQuery' => 'New Zealand railway',
            'suggestedQueryHighlighted' => 'New <strong class="hl">Zealand railway</strong>',
        );
        $this->assertEquals($expected, $pair);
    }

    public function testBooleanQuery()
    {
        $sa = $this->getSuggestionArray('New Zealind AND sheep',
            'new zealand and sheep',
            'new <strong class="hl">zealand</strong> and sheep');
        $pair = ElasticaUtil::getPhraseSuggestion($sa);
        $expected = array(
            'suggestedQuery' => 'New Zealand AND sheep',
            'suggestedQueryHighlighted' => 'New <strong class="hl">Zealand</strong> AND sheep',
        );
        $this->assertEquals($expected, $pair);
    }

    public function testOneIncorrectWord()
    {
        $sa = $this->getSuggestionArray('New Zealind',
            'new zealand',
            'new <strong class="hl">zealand</strong>');
        $pair = ElasticaUtil::getPhraseSuggestion($sa);
        $expected = array(
            'suggestedQuery' => 'New Zealand',
            'suggestedQueryHighlighted' => 'New <strong class="hl">Zealand</strong>',
        );
        $this->assertEquals($expected, $pair);
    }

    public function testOneIncorrectWordLowerCase()
    {
        $sa = $this->getSuggestionArray('new zealind',
            'new zealand',
            'new <strong class="hl">zealand</strong>');
        $pair = ElasticaUtil::getPhraseSuggestion($sa);
        $expected = array(
            'suggestedQuery' => 'new zealand',
            'suggestedQueryHighlighted' => 'new <strong class="hl">zealand</strong>',
        );
        $this->assertEquals($expected, $pair);
    }

    public function testNoSuggestionsRequired()
    {
        $suggestion = array(
            'text' => 'New Zealand',
            'offset' => 0,
            'length' => 11,
            'options' => array(),
        );
        $sa = array();
        array_push($sa, $suggestion);
        $pair = ElasticaUtil::getPhraseSuggestion($sa);
        $this->assertEquals(null, $pair);
    }

    /**
     * Test parsing no terms suggested.
     */
    public function testParseExplanationNoTerms()
    {
        #FIXME Possibly flickrphotoTO
        $explanation = '() -ConstantScore(_uid:FlickrPhoto#7369)';
        $expected = array();
        $terms = ElasticaUtil::parseSuggestionExplanation($explanation);
        $this->assertEquals($expected, $terms);
    }

    public function testParseExplanationDoubleStartingBracket()
    {
        $explanation = '((Title.standard:wellington Title.standard:view Description.standard:new ';
        $explanation .= 'Description.standard:zealand Description.standard:wellington Description.standard:view Description.standard:including Description.standard:buildings Description.standard:exhibition Description.standard:aerial)~3) -ConstantScore(_uid:FlickrPhoto#3079)';
        $terms = ElasticaUtil::parseSuggestionExplanation($explanation);

        $termsDescription = $terms['Description.standard'];
        sort($termsDescription);

        $termsTitle = $terms['Title.standard'];
        sort($termsTitle);

        $expected = array(
            'Title.standard' => $termsTitle,
            'Description.standard' => $termsDescription,
        );

        $terms = array(
            'Title.standard' => array('view', 'wellington'),
            'Description.standard' => array(
                'aerial',
                'buildings',
                'exhibition',
                'including',
                'new',
                'view',
                'wellington',
                'zealand',
            ),
        );
        $this->assertEquals($expected, $terms);
    }

    public function testParseExplanationNoLeadingBrackets()
    {
        $explanation = 'Title.standard:new -ConstantScore(_uid:FlickrPhotoTO#76)';
        $terms = ElasticaUtil::parseSuggestionExplanation($explanation);
        $expected = array('Title.standard' => array('new'));
        $this->assertEquals($expected, $terms);
    }

    public function testParseExplanationBracketsShowing()
    {
        $explanation = '(Description.standard:bay Description.standard:tram Description.standard:manners) -ConstantScore(_uid:FlickrPhoto#4645)';
        $terms = ElasticaUtil::parseSuggestionExplanation($explanation);
        $expected = array('Description.standard' => array('bay', 'tram', 'manners'));
        $this->assertEquals($expected, $terms);
    }

    /**
     * Simulate a call to Elastica to get suggestions for a given phrase.
     *
     * @return [type] [description]
     */
    private function getSuggestionArray($phrase, $suggestion, $highlightedSuggestion)
    {
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
