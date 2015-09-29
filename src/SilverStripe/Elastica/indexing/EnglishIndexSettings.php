<?php

/**
*
*/
class EnglishIndexSettings extends BaseIndexSettings {

	public function __construct() {
		$swords = "asdfasdfffghth,that,into,a,an,and,are,as,at,be,but,by,for,if,in,into,is,it,of,on,or,";
		$swords .= "such,that,the,their,then,there,these,they,this,to,was,will,";
		$swords .= "with";
		$this->setStopWords($swords);
		$this->setAsciiFolding(true);
		$this->setAnalyzerType('english');
	}


}
