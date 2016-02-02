<?php
/**
* Only show a page with login when not logged in.
*/
use Elastica\Document;
use Elastica\Query;
use Elastica\Aggregation\Filter;
use Elastica\Filter\Term;
use Elastica\Aggregation\Terms;
use \SilverStripe\Elastica\Searchable;
use \SilverStripe\Elastica\QueryGenerator;
use \SilverStripe\Elastica\ElasticaUtil;

//FIXME namespace

class ElasticSearchPage extends Page
{
    private static $defaults = array(
        'ShowInMenus' => 0,
        'ShowInSearch' => 0,
        'ClassesToSearch' => '',
        'ResultsPerPage' => 10,
        'SiteTreeOnly' => true,
        'MinTermFreq' => 2,
        'MaxTermFreq' => 25,
        'MinWordLength' => 3,
        'MinDocFreq' => 2,
        'MaxDocFreq' => 0,
        'MinWordLength' => 0,
        'MaxWordLength' => 0,
        'MinShouldMatch' => '30%',
    );

    private static $db = array(
        'ClassesToSearch' => 'Text',
        // unique identifier used to find correct search page for results
        // e.g. a separate search page for blog, pictures etc
        'Identifier' => 'Varchar',
        'ResultsPerPage' => 'Int',
        'SearchHelper' => 'Varchar',
        'SiteTreeOnly' => 'Boolean',
        'ContentForEmptySearch' => 'HTMLText',
        'MinTermFreq' => 'Int',
        'MaxTermFreq' => 'Int',
        'MinWordLength' => 'Int',
        'MinDocFreq' => 'Int',
        'MaxDocFreq' => 'Int',
        'MinWordLength' => 'Int',
        'MaxWordLength' => 'Int',
        'MinShouldMatch' => 'Varchar',
        'SimilarityStopWords' => 'Text',
    );

    private static $many_many = array(
        'ElasticaSearchableFields' => 'SearchableField',
    );

    private static $many_many_extraFields = array(
        'ElasticaSearchableFields' => array(
        'Searchable' => 'Boolean', // allows the option of turning off a single field for searching
        'SimilarSearchable' => 'Boolean', // allows field to be used in more like this queries.
        'Active' => 'Boolean', // preserve previous edits of weighting when classes changed
        'EnableAutocomplete' => 'Boolean', // whether or not to show autocomplete search for this field
        'Weight' => 'Int', // Weight to apply to this field in a search
        ),
    );

    private static $has_one = array(
        'AutoCompleteFunction' => 'AutoCompleteOption',
        'AutoCompleteField' => 'SearchableField',
    );

    /*
    Add a tab with details of what to search
     */
    public function getCMSFields()
    {
        Requirements::javascript('elastica/javascript/elasticaedit.js');
        $fields = parent::getCMSFields();

        $fields->addFieldToTab('Root', new TabSet('Search',
            new Tab('SearchFor'),
            new Tab('Fields'),
            new Tab('AutoComplete'),
            new Tab('Aggregations'),
            new Tab('Similarity')
        ));

        // ---- similarity tab ----
        $html = '<button class="ui-button-text-alternate ui-button-text"
		id="MoreLikeThisDefaultsButton"
		style="display: block;float: right;">Restore Defaults</button>';
        $defaultsButton = new LiteralField('DefaultsButton', $html);
        $fields->addFieldToTab('Root.Search.Similarity', $defaultsButton);
        $sortedWords = $this->SimilarityStopWords;

        $stopwordsField = StringTagField::create(
            'SimilarityStopWords',
            'Stop Words for Similar Search',
            explode(',', $sortedWords),
            $sortedWords
        );

        $stopwordsField->setShouldLazyLoad(true); // tags should be lazy loaded

        $fields->addFieldToTab('Root.Search.Similarity', $stopwordsField);

        $lf = new LiteralField('SimilarityNotes', _t('Elastica.SIMILARITY_NOTES',
            'Default values are those used by Elastica'));
        $fields->addFieldToTab('Root.Search.Similarity', $lf);
        $fields->addFieldToTab('Root.Search.Similarity', new TextField('MinTermFreq',
            'The minimum term frequency below which the terms will be ignored from the input '.
            'document. Defaults to 2.'));
        $fields->addFieldToTab('Root.Search.Similarity', new TextField('MaxTermFreq',
            'The maximum number of query terms that will be selected. Increasing this value gives '.
            'greater accuracy at the expense of query execution speed. Defaults to 25.'));
        $fields->addFieldToTab('Root.Search.Similarity', new TextField('MinWordLength',
            'The minimum word length below which the terms will be ignored.  Defaults to 0.'));
        $fields->addFieldToTab('Root.Search.Similarity', new TextField('MinDocFreq',
            'The minimum document frequency below which the terms will be ignored from the input '.
            'document. Defaults to 5.'));
        $fields->addFieldToTab('Root.Search.Similarity', new TextField('MaxDocFreq',
            'The maximum document frequency above which the terms will be ignored from the input '.
            'document. This could be useful in order to ignore highly frequent words such as stop '.
            'words. Defaults to unbounded (0).'));
        $fields->addFieldToTab('Root.Search.Similarity', new TextField('MinWordLength',
            'The minimum word length below which the terms will be ignored. The old name min_'.
            'word_len is deprecated. Defaults to 0.'));
        $fields->addFieldToTab('Root.Search.Similarity', new TextField('MaxWordLength',
            'The maximum word length above which the terms will be ignored. The old name max_word_'.
            'len is deprecated. Defaults to unbounded (0).'));
        $fields->addFieldToTab('Root.Search.Similarity', new TextField('MinShouldMatch',
            'This parameter controls the number of terms that must match. This can be either a '.
            'number or a percentage.  See '.
            'https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-minimum-should-match.html'));

        // ---- search details tab ----
        $identifierField = new TextField('Identifier',
            'Identifier to allow this page to be found in form templates');
        $fields->addFieldToTab('Root.Main', $identifierField, 'Content');
        $fields->addFieldToTab('Root.Search.SearchFor', new CheckboxField('SiteTreeOnly', 'Show search results for all SiteTree objects only'));

        $sql = 'SELECT DISTINCT "ClassName" from "SiteTree_Live" UNION '
             .'SELECT DISTINCT "ClassName" from "SiteTree" '
             ."WHERE \"ClassName\" != 'ErrorPage'"
             .'ORDER BY "ClassName"'
        ;

        $classes = array();
        $records = DB::query($sql);
        foreach ($records as $record) {
            array_push($classes, $record['ClassName']);
        }
        $list = implode(',', $classes);

        $clazzes = '';
        $clazzes = $this->ClassesToSearch;
        $allSearchableClasses = SearchableClass::get()->sort('Name')->map('Name')->toArray();
        $classesToSearchField = StringTagField::create(
            'ClassesToSearch',
            'Choose which SilverStripe classes to search',
            $allSearchableClasses,
            $clazzes
        );

        $fields->addFieldToTab('Root.Search.SearchFor', $classesToSearchField);

        $html = '<div class="field text" id="SiteTreeOnlyInfo">';
        $html .= '<p>Copy the following into the above field to ensure that all SiteTree classes are searched</p>';
        $html .= '<p class="message">'.$list;
        $html .= '</p></div>';
        $infoField = new LiteralField('InfoField', $html);
        $fields->addFieldToTab('Root.Search.SearchFor', $infoField);

        $fields->addFieldToTab('Root.Main', new HTMLEditorField('ContentForEmptySearch'));

        $fields->addFieldToTab('Root.Search.SearchFor', new NumericField('ResultsPerPage',
                                            'The number of results to return on a page'));
        $fields->addFieldToTab('Root.Search.Aggregations', new TextField('SearchHelper',
            'ClassName of object to manipulate search details and results.  Leave blank for standard search'));

        $ottos = AutoCompleteOption::get()->Filter('Locale', $this->Locale)->map('ID', 'Name')->
                                            toArray();
        $df = DropdownField::create('AutoCompleteFunctionID', 'Autocomplete Function')->
                                    setSource($ottos);
        $df->setEmptyString('-- Please select what do do after find as you type has occurred --');

        $ottos = $this->ElasticaSearchableFields()->filter('EnableAutocomplete', 1)->Map('ID', 'Name')->toArray();
        $autoCompleteFieldDF = DropDownField::create('AutoCompleteFieldID', 'Field to use for autocomplete')->setSource($ottos);
        $autoCompleteFieldDF->setEmptyString('-- Please select which field to use for autocomplete --');

        $fields->addFieldToTab('Root.Search.AutoComplete',
                FieldGroup::create(
                    $autoCompleteFieldDF,
                    $df
                )->setTitle('Autocomplete')
         );

        // ---- grid of searchable fields ----
        $html = '<p id="SearchFieldIntro">'._t('SiteConfig.ELASTICA_SEARCH_INFO',
                "Select a field to edit it's properties").'</p>';
        $fields->addFieldToTab('Root.Search.Fields', $h1 = new LiteralField('SearchInfo', $html));
        $searchPicker = new PickerField('ElasticaSearchableFields', 'Searchable Fields',
            $this->ElasticaSearchableFields()->filter('Active', 1)->sort('Name'));

        $fields->addFieldToTab('Root.Search.Fields', $searchPicker);

        $pickerConfig = $searchPicker->getConfig();

        $pickerConfig->removeComponentsByType(new GridFieldAddNewButton());
        $pickerConfig->removeComponentsByType(new GridFieldDeleteAction());
        $pickerConfig->removeComponentsByType(new PickerFieldAddExistingSearchButton());
        $pickerConfig->getComponentByType('GridFieldPaginator')->setItemsPerPage(100);

        $searchPicker->enableEdit();
        $edittest = $pickerConfig->getComponentByType('GridFieldDetailForm');
        $edittest->setFields(FieldList::create(
            TextField::create('Name', 'Field Name'),
            TextField::create('ClazzName', 'Class'),
            HiddenField::create('Autocomplete', 'This can be autocompleted'),
            CheckboxField::create('ManyMany[Searchable]', 'Use for normal searching'),
            CheckboxField::create('ManyMany[SimilarSearchable]', 'Use for similar search'),
            NumericField::create('ManyMany[Weight]', 'Weighting'),
            CheckboxField::create('ShowHighlights', 'Show highlights from search in results for this field')
        ));

        $edittest->setItemEditFormCallback(function ($form) {
            $fields = $form->Fields();
            $fields->dataFieldByName('ClazzName')->setReadOnly(true);
            $fields->dataFieldByName('ClazzName')->setDisabled(true);
            $fields->dataFieldByName('Name')->setReadOnly(true);
            $fields->dataFieldByName('Name')->setDisabled(true);
        });

        // What do display on the grid of searchable fields
        $dataColumns = $pickerConfig->getComponentByType('GridFieldDataColumns');
        $dataColumns->setDisplayFields(array(
            'Name' => 'Name',
            'ClazzName' => 'Class',
            'Type' => 'Type',
            'Searchable' => 'Use for Search?',
            'SimilarSearchable' => 'Use for Similar Search?',
            'ShowHighlights' => 'Show Search Highlights',
            'Weight' => 'Weighting',
        ));

        return $fields;
    }

    public function getCMSValidator()
    {
        return new ElasticSearchPage_Validator();
    }

    /**
     * Avoid duplicate identifiers, and check that ClassesToSearch actually exist and are Searchable.
     *
     * @return DataObject result with or without error
     */
    public function validate()
    {
        $result = parent::validate();
        $mode = Versioned::get_reading_mode();
        $suffix = '';
        if ($mode == 'Stage.Live') {
            $suffix = '_Live';
        }

        if (!$this->Identifier) {
            $result->error('The identifier cannot be blank');
        }

        $where = '"ElasticSearchPage'.$suffix.'"."ID" != '.$this->ID." AND \"Identifier\" = '{$this->Identifier}'";
        $existing = self::get()->where($where)->count();
        if ($existing > 0) {
            $result->error('The identifier '.$this->Identifier.' already exists');
        }

        // now check classes to search actually exist, assuming in site tree not set
        if (!$this->SiteTreeOnly) {
            if ($this->ClassesToSearch == '') {
                $result->error('At least one searchable class must be available, or SiteTreeOnly flag set');
            } else {
                $toSearch = explode(',', $this->ClassesToSearch);
                foreach ($toSearch as $clazz) {
                    try {
                        $instance = Injector::inst()->create($clazz);
                        if (!$instance->hasExtension('SilverStripe\Elastica\Searchable')) {
                            $result->error('The class '.$clazz.' must have the Searchable extension');
                        }
                    } catch (ReflectionException $e) {
                        $result->error('The class '.$clazz.' does not exist');
                    }
                }
            }
        }

        foreach ($this->ElasticaSearchableFields() as $esf) {
            if ($esf->Weight == 0) {
                $result->error("The field {$esf->ClazzName}.{$esf->Name} has a zero weight. ");
            } elseif ($esf->Weight < 0) {
                $result->error("The field {$esf->ClazzName}.{$esf->Name} has a negative weight. ");
            }
        }

        return $result;
    }

    public function onAfterWrite()
    {
        // FIXME - move to a separate testable method and call at build time also
        $nameToMapping = QueryGenerator::getSearchFieldsMappingForClasses($this->ClassesToSearch);
        $names = array_keys($nameToMapping);

        #FIXME -  SiteTree only
        $relevantClasses = $this->ClassesToSearch; // due to validation this will be valid
        if ($this->SiteTreeOnly) {
            $relevantClasses = SearchableClass::get()->filter('InSiteTree', true)->Map('Name')->toArray();
        }
        $quotedClasses = QueryGenerator::convertToQuotedCSV($relevantClasses);
        $quotedNames = QueryGenerator::convertToQuotedCSV($names);

        error_log('RELEVANT CLASSES: ' . $relevantClasses);
        error_log('QUOTED CLASSES: ' . $quotedClasses);
        error_log('QUOTED NAMES: ' . $quotedClasses);


        $where = "\"Name\" IN ($quotedNames) AND \"ClazzName\" IN ($quotedClasses)";

        error_log('WHERE:' . $where);

        // Get the searchfields for the ClassNames searched
        $sfs = SearchableField::get()->where($where);

        // Get the searchable fields associated with this search page
        $esfs = $this->ElasticaSearchableFields();

        // Remove existing searchable fields for this page from the list of all available
        $delta = array_keys($esfs->map()->toArray());
        $newSearchableFields = $sfs->exclude('ID', $delta);

        if ($newSearchableFields->count() > 0) {
            foreach ($newSearchableFields->getIterator() as $newSearchableField) {
                $newSearchableField->Active = true;
                $newSearchableField->Weight = 1;

                $esfs->add($newSearchableField);

                // Note 1 used instead of true for SQLite3 testing compatibility
                $sql = 'UPDATE "ElasticSearchPage_ElasticaSearchableFields" SET ';
                $sql .= '"Active"=1, "Weight"=1 WHERE "ElasticSearchPageID" = '.$this->ID;
                DB::query($sql);
            }
        }

        // Mark all the fields for this page as inactive initially
        $sql = 'UPDATE "ElasticSearchPage_ElasticaSearchableFields" SET "ACTIVE"=0 WHERE ';
        $sql .= "\"ElasticSearchPageID\"={$this->ID}";
        DB::query($sql);

        $activeIDs = array_keys($sfs->map()->toArray());
        $activeIDs = implode(',', $activeIDs);

        //Mark as active the relevant ones
        $sql = 'UPDATE "ElasticSearchPage_ElasticaSearchableFields" SET "ACTIVE"=1 WHERE ';
        $sql .= "\"ElasticSearchPageID\"={$this->ID} AND \"SearchableFieldID\" IN (";
        $sql .= "$activeIDs)";
        DB::query($sql);
    }

    /*
    Obtain an instance of the form - this is need for rendering the search box in the header
    */
    public function SearchForm($buttonTextOverride = null)
    {
        $result = new ElasticSearchForm($this, 'SearchForm');
        $fields = $result->Fields();
        $identifierField = new HiddenField('identifier');
        $identifierField->setValue($this->Identifier);
        $fields->push($identifierField);
        $qField = $fields->fieldByName('q');

        if ($buttonTextOverride) {
            $result->setButtonText($buttonTextOverride);
        }

        if ($this->AutoCompleteFieldID > 0) {
            ElasticaUtil::addAutocompleteToQueryField(
                $qField,
                $this->ClassesToSearch,
                $this->SiteTreeOnly,
                $this->Link(),
                $this->AutocompleteFunction()->Slug
            );
        }

        return $result;
    }

    /*
    If a manipulator object is set, assume aggregations are present.  Used to add the column
    for aggregates
     */
    public function HasAggregations()
    {
        return $this->SearchHelper != null;
    }
}
