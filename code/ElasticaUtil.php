<?php

namespace SilverStripe\Elastica;

/**
 * Utility methods to help with searching functions, and also testable without fixtures.
 */
class ElasticaUtil
{
    /**
     * Marker string for pre highlight - can be any string unlikely to appear in a search.
     */
    private static $pre_marker = ' |PREZXCVBNM12345678';

    /**
     * Marker string for psot highlight - can be any string unlikely to appear in a search.
     */
    private static $post_marker = 'POSTZXCVBNM12345678| ';

    /**
     * @var bool true to show CLI output, false to hide
     */
    private static $cli_printer_output = true;

    /**
     * Function to display messages only if using the command line.
     *
     * @var string Text to display when in command line mode
     */
    public static function message($content)
    {
        if (\Director::is_cli() && self::$cli_printer_output === true) {
            echo "$content\n";
        }
    }

    /*
    Display a human readable yes or no
     */
    public static function showBooleanHumanReadable($assertion)
    {
        return $assertion ? 'Yes' : 'No';
    }

    public static function getPhraseSuggestion($alternativeQuerySuggestions)
    {
        $originalQuery = $alternativeQuerySuggestions[0]['text'];

        $highlightsCfg = \Config::inst()->get('Elastica', 'Highlights');
        $preTags = $highlightsCfg['PreTags'];
        $postTags = $highlightsCfg['PostTags'];

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
            $markedHighlightedParts = str_replace(' '.$preTags, ' '.self::$pre_marker, $markedHighlightedParts);

            $markedHighlightedParts = str_replace($postTags.' ', self::$post_marker, $markedHighlightedParts);

            $markedHighlightedParts = trim($markedHighlightedParts);
            $markedHighlightedParts = trim($markedHighlightedParts);

            $highlightedParts = preg_split('/\s+/', $markedHighlightedParts);

            //Create a mapping of lowercase to uppercase terms
            $lowerToUpper = array();
            $lowerToHighlighted = array();
            $ctr = 0;
            foreach ($suggestedParts as $lowercaseWord) {
                $lowerToUpper[$lowercaseWord] = $originalParts[$ctr];
                $lowerToHighlighted[$lowercaseWord] = $highlightedParts[$ctr];
                ++$ctr;
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

                    $chr = mb_substr($possiblyUppercase, 0, 1, 'UTF-8');
                    if (mb_strtolower($chr, 'UTF-8') != $chr) {
                        $upperLowercaseWord = $lowercaseWord;
                        $upperLowercaseWord[0] = $chr;
                        $withHighlights = str_replace($lowercaseWord, $upperLowercaseWord, $possiblyUppercaseHighlighted);
                        $lowercaseWord[0] = $chr;
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
            $highlighted = str_replace(self::$pre_marker, ' '.$preTags, $highlighted);
            $highlighted = str_replace(self::$post_marker, $postTags.' ', $highlighted);

            $resultArray['suggestedQuery'] = implode(' ', $plain);
            $resultArray['suggestedQueryHighlighted'] = trim($highlighted);
        }

        return $resultArray;
    }

    /**
     * The output format of this function is not documented, so at best this is guess work to an
     * extent.  Possible formats are:
     * - ((Title.standard:great Content.standard:ammunition Content.standard:could
     *     Content.standard:bair Content.standard:dancing Content.standard:column
     *     Content.standard:company Content.standard:infantry Content.standard:men
     *     Content.standard:soldier Content.standard:brigade Content.standard:zealand
     *     Content.standard:new)~3)
     *     -ConstantScore(_uid:GutenbergBookExtract#1519)
     *  (Description: bay Description: mannerstram).
     *
     * @param string $explanation explanation string for more like this terms from Elasticsearch
     *
     * @return array Array of fieldnames mapped to terms
     */
    public static function parseSuggestionExplanation($explanation)
    {
        // on 2 lines to keep php 5.3 happy
        $explanation = explode('-ConstantScore', $explanation);
        $explanation = $explanation[0];

        $bracketPos = strpos($explanation, ')~');

        if (substr($explanation, 0, 2) == '((') {
            $explanation = substr($explanation, 2, $bracketPos - 2);
        } elseif (substr($explanation, 0, 1) == '(') {
            $explanation = substr($explanation, 1, $bracketPos - 2);
        }

        $terms = array();

        //Field name(s) => terms
        $splits = explode(' ', $explanation);

        foreach ($splits as $fieldAndTerm) {
            $splits = explode(':', $fieldAndTerm);

            // This is the no terms case
            if (sizeof($splits) < 2) {
                break;
            }

            $fieldname = $splits[0];
            $term = $splits[1];

            if (!isset($terms[$fieldname])) {
                $terms[$fieldname] = array();
            }

            array_push($terms[$fieldname], $term);
        }

        return $terms;
    }

    /**
     * Add attributes necessary for jQuery to execute autocomplete.
     *
     * @param FormField &$queryField field used to type a search query
     */
    public static function addAutocompleteToQueryField(&$queryField, $classesToSearch, $siteTreeOnly, $link, $slug)
    {
        $queryField->setAttribute('data-autocomplete', 'true');
        $queryField->setAttribute('data-autocomplete-field', 'Title');
        $queryField->setAttribute('data-autocomplete-classes', $classesToSearch);
        $queryField->setAttribute('data-autocomplete-sitetree', $siteTreeOnly);
        $queryField->setAttribute('data-autocomplete-source', $link);
        $queryField->setAttribute('data-autocomplete-function', $slug);
    }

    /**
     * @return function print content to either web browser or command line.  Can be optionally supressed
     */
    public static function getPrinter()
    {
        return function ($content) {
            if (ElasticaUtil::getPrinterOutput() === true) {
                echo \Director::is_cli() ? "$content\n" : "<p>$content</p>";
            }

        };
    }

    /**
     * Set to true to show output on the command line or browser, false to not.
     *
     * @param   $newcli_printer_output true to show output, false to hide it
     */
    public static function setPrinterOutput($new_cli_printer_output)
    {
        self::$cli_printer_output = $new_cli_printer_output;
    }

    /**
     * Accessor to printer output variable.  Needed for PHP 5.3 compliance
     * @return bool true to output text when CLI, false not to
     */
    public static function getPrinterOutput() {
        return self::$cli_printer_output;
    }
}
