<?php

/**
 * Utility methods to help with searching functions, and also testable without fixtures
 */
class ElasticaUtil {

	/**
	 * Marker string for pre highlight - can be any string unlikely to appear in a search
	 */
	private static $pre_marker = " |PREZXCVBNM12345678";

	/**
	 * Marker string for psot highlight - can be any string unlikely to appear in a search
	 */
	private static $post_marker = "POSTZXCVBNM12345678| ";


	public static function getPhraseSuggestion($alternativeQuerySuggestions) {
		$suggestedPhrase = null;
		$originalQuery = $alternativeQuerySuggestions[0]['text'];

		$highlightsCfg = \Config::inst()->get('Elastica', 'Highlights');
		$preTags = $highlightsCfg['PreTags'];
		$postTags = $highlightsCfg['PostTags'];
		$lenPreTags = strlen($preTags);
		$lenPostTags = strlen($postTags);

		$suggestedPhraseCapitalised = array();

		//Use the first suggested phrase
		$options = $alternativeQuerySuggestions[0]['options'];

		$resultArray = null;

		if (sizeof($options) > 0) {
			//take the first suggestion
			$suggestedPhrase = $options[0]['text'];
			$suggestedPhraseHighlighted = $options[0]['highlighted'];

			// now need to fix capitalisation
			$originalParts = explode(' ', $originalQuery);
			$suggestedParts = explode(' ', $suggestedPhrase);

			$markedHighlightedParts = ' '.$suggestedPhraseHighlighted.' ';
			//echo "T1 *$markedHighlightedParts*, pretags = *$preTags*\n";
			$markedHighlightedParts = str_replace(' '.$preTags, ' '.self::$pre_marker, $markedHighlightedParts);
			//echo "T2 *$markedHighlightedParts*, postTags = *$postTags*\n";

			//echo "T2a Replacing *$postTags* with ".self::$post_marker." in *$markedHighlightedParts*\n";
			$markedHighlightedParts = str_replace($postTags.' ', self::$post_marker, $markedHighlightedParts);
			//echo "T3 *$markedHighlightedParts*\n";

			$markedHighlightedParts = trim($markedHighlightedParts);
			$markedHighlightedParts = trim($markedHighlightedParts);

			$highlightedParts = preg_split('/\s+/', $markedHighlightedParts);


			//echo "ORIGINAL PARTS:\n";
			print_r($originalParts);

			//echo "SUGGESTED PARTS (from Elastica):\n";
			print_r($suggestedParts);

			//echo "SUGGESTED MARKED UP HIGHLIGHTED PARTS (from Elastica):\n";
			print_r($highlightedParts);



			//Create a mapping of lowercase to uppercase terms
			$lowerToUpper = array();
			$lowerToHighlighted = array();
			$ctr = 0;
			foreach ($suggestedParts as $lowercaseWord) {
				$lowerToUpper[$lowercaseWord] = $originalParts[$ctr];
				$lowerToHighlighted[$lowercaseWord] = $highlightedParts[$ctr];
				$ctr++;
			}

			$plain = array();
			$highlighted = array();
			foreach ($suggestedParts as $lowercaseWord) {
				$possiblyUppercase = $lowerToUpper[$lowercaseWord];
				$possiblyUppercaseHighlighted = $lowerToHighlighted[$lowercaseWord];

				//If the terms are identical other than case, e.g. new => New, then simply swap
				if (strtolower($possiblyUppercase) == $lowercaseWord) {
					array_push($plain, $possiblyUppercase);
					array_push($highlighted, $possiblyUppercase);
				} else {
					//Need to check capitalisation of terms suggested that are different

					$chr = mb_substr ($possiblyUppercase, 0, 1, "UTF-8");
    				if (mb_strtolower($chr, "UTF-8") != $chr) {
    					$upperLowercaseWord = $lowercaseWord;
    					$upperLowercaseWord[0] = $chr;

    					//$possiblyUppercaseHighlighted = str_replace($lowercaseWord, $possiblyUppercase, $possiblyUppercaseHighlighted);
    					$withHighlights = str_replace($lowercaseWord, $upperLowercaseWord, $possiblyUppercaseHighlighted);

    					$lowercaseWord[0] = $chr;

    					//str_replace(search, replace, subject)

    					array_push($plain, $lowercaseWord);
    					array_push($highlighted, $withHighlights);
    				} else {
    					//No need to capitalise, so add suggested word
    					array_push($plain, $lowercaseWord);

    					//No need to capitalise, so add suggested highlighted word
    					array_push($highlighted, $possiblyUppercaseHighlighted);
    				}
				}
			}

			$highlighted = ' '.implode(' ', $highlighted).' ';
			//echo "T1 $highlighted\n";
			$highlighted = str_replace(self::$pre_marker, ' '.$preTags, $highlighted);
			//echo "T2 $highlighted\n";
			$highlighted = str_replace(self::$post_marker, $postTags.' ', $highlighted);
			//echo "T3 $highlighted\n";

			$resultArray['suggestedQuery'] = implode(' ', $plain);
			$resultArray['suggestedQueryHighlighted'] = trim($highlighted);
		}
		return $resultArray;
	}
}
