<?php

/**
 *
 */
class EnglishIndexSettings extends BaseIndexSettings
{
    public function __construct()
    {
        $swords = 'that,into,a,an,and,are,as,at,be,but,by,for,if,in,into,is,it,of,on,or,';
        $swords .= 'such,that,the,their,then,there,these,they,this,to,was,will,';
        $swords .= 'with';
        $this->setStopWords($swords);
        $this->setAsciiFolding(true);

        $this->addFilter('english_stop', array(
            'type' => 'stop',
            'stopwords' => $this->stopWords,
        ));

        $this->stopWordFilter = 'english_stop';

/*
        //Add words here not to be stemmed
        $this->addFilter('english_keywords', array(
            'type' => 'keyword_marker',
            'keywords' => "[]"
        ));
*/

        //Add stemmers
        $this->addFilter('english_stemmer', array(
            'type' => 'stemmer',
            'language' => 'english',
        ));

        $this->addFilter('english_possessive_stemmer', array(
            'type' => 'stemmer',
            'language' => 'possessive_english',
        ));

        //Optional
        $this->addFilter('english_snowball', array(
            'type' => 'snowball',
            'language' => 'English',
        ));

        $englishWithURLs = array(
            'tokenizer' => 'uax_url_email',
            //'filter' => array('english_possessive_stemmer', 'lowercase', 'english_stop', /*'english_keywords',*/ 'english_stemmer' ),
            'filter' => array('no_single_chars', 'english_snowball', 'lowercase', $this->stopWordFilter),
            'type' => 'custom',
        );

        $this->addAnalyzer('stemmed', $englishWithURLs);
    }
}
