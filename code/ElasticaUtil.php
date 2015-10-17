<?php

/**
 * Utility methods to help with searching functions, and also testable without fixtures
 */
class ElasticaUtil {

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

                    [1] => Array
                        (
                            [text] => new zealand railways
                            [highlighted] => new *zealand railways*
                            [score] => 3.1240943E-5
                        )

                    [2] => Array
                        (
                            [text] => new zealand roadway
                            [highlighted] => new *zealand roadway*
                            [score] => 2.6352465E-5
                        )

                    [3] => Array
                        (
                            [text] => new zealand railwaj
                            [highlighted] => new *zealand railwaj*
                            [score] => 1.9387107E-5
                        )

                )

        )

)
	 */
	public static function getPhraseSuggestion($alternativeQuerySuggestions) {
		print_r($alternativeQuerySuggestions);
		$suggestedPhrase = null;
		$originalQuery = $alternativeQuerySuggestions[0]['text'];

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
			$highlightedParts = explode(' ', $suggestedPhraseHighlighted);

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
					echo "CHAR:$chr";
    				if (mb_strtolower($chr, "UTF-8") != $chr) {
    					$upperLowercaseWord = $lowercaseWord;
    					$upperLowercaseWord[0] = $chr;

    					echo "A=$possiblyUppercaseHighlighted B=$lowercaseWord C=$possiblyUppercaseHighlighted";
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

			$resultArray['suggestedQuery'] = implode(' ', $plain);
			$resultArray['suggestedQueryHighlighted'] = implode(' ', $highlighted);
		}

					print_r($resultArray);


		return $resultArray;
	}
}
