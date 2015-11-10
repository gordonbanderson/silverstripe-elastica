<?php

class ElasticSearchPage_Validator extends RequiredFields {

	protected $customRequired = array('Name');

	/**
	 * Constructor
	 */
	public function __construct() {
		$required = array('ResultsPerPage', 'Identifier');

		parent::__construct($required);
	}

	function php($data) {
		parent::php($data);
		$valid = true;
		//Debug::message("Validating data: " . print_r($data, true));
		$valid = parent::php($data);
		//Debug::message("Returning false, just to check");
		//return false;
		//


		// Check if any classes to search if site tree only is not ticked
		if (!$data['SiteTreeOnly']) {
			if (!$data['ClassesToSearch']) {
				$valid = false;
				$this->validationError("ClassesToSearch",
					"Please provide at least one class to search, or select 'Site Tree Only'",
					'error'
				);
			} else {
				$toSearch = $data['ClassesToSearch'];

				//Comes back from tag field as an array
				if (!is_array($toSearch)) {
					$toSearch = explode(',', $data['ClassesToSearch']);
				}
				foreach ($toSearch as $clazz) {
					try {
						$instance = Injector::inst()->create($clazz);
						if (!$instance->hasExtension('SilverStripe\Elastica\Searchable')) {
							$this->validationError('The class '.$clazz.' must have the Searchable extension');
						}
					} catch (ReflectionException $e) {


						$this->validationError("ClassesToSearch",
							'The class '.$clazz.' does not exist',
							'error'
						);
					}
				}
			}
		}


		// now check classes to search actually exist, assuming in site tree not set
		if (!$data['SiteTreeOnly']) {
			if ($data['ClassesToSearch'] == '') {
				$result->validationError('ClassesToSearch',
					'At least one searchable class must be available, or SiteTreeOnly flag set',
					'error'
				);
			} else {

			}
		}

		// Check the identifier is unique
		$mode = Versioned::get_reading_mode();
		$suffix =  '';
		if ($mode == 'Stage.Live') {
			$suffix = '_Live';
		}

		$where = 'ElasticSearchPage'.$suffix.'.ID != '.$data['ID']." AND `Identifier` = '".$data['Identifier']."'";
		$existing = ElasticSearchPage::get()->where($where)->count();
		if ($existing > 0) {
			$valid = false;
			$this->validationError('Identifier',
					'The identifier '.$this->Identifier.' already exists',
					'error'
			);
		}


		// Check number of results per page >= 1
		if ($data['ResultsPerPage'] <= 0) {
			$valid = false;
			$this->validationError('ResultsPerPage',
				'Results per page must be >=1'
				,'error'
			);
		}
		return $valid;
	}
}


/*
/&
 [Title] =&gt; Standard Search
    [URLSegment] =&gt; standard-search
    [MenuTitle] =&gt; Standard Search
    [Identifier] =&gt; mainsearch
    [Content] =&gt; &lt;p&gt;asfdsafd&lt;/p&gt;
    [MetaDescription] =&gt;
    [ExtraMeta] =&gt;
    [ContentForEmptySearch] =&gt;
    [NewTransLang] =&gt; de_DE
    [createtranslation] =&gt;
    [AlternativeTemplate] =&gt;
    [SiteTreeOnly] =&gt;
    [ClassesToSearch] =&gt; Page,GutenbergExtract
    [ResultsPerPage] =&gt; 2
    [SearchHelper] =&gt;
    [AutoCompleteFieldID] =&gt;
    [AutoCompleteFunctionID] =&gt; 11
    [ElasticaSearchableFields] =&gt; Array
        (
            [GridState] =&gt; {&quot;GridFieldPaginator&quot;:{&quot;currentPage&quot;:1}}
        )

    [Locale] =&gt; en_US
    [ClassName] =&gt; ElasticSearchPage
    [ParentID] =&gt; 0
    [SecurityID] =&gt; 745ae4c89934bb8725579b12fb2974958ba0a288
    [ID] =&gt; 2738
    [AbsoluteLink] =&gt;
    [LiveLink] =&gt; http://elasticademo.silverstripe/standard-search/?stage=Live
    [StageLink] =&gt; http://elasticademo.silverstripe/standard-search/?stage=Stage
    [TreeTitle] =&gt; &lt;span class=&quot;jstree-pageicon&quot;&gt;&lt;/span&gt;&lt;span class=&quot;item&quot; data-allowedchildren=&quot;{&amp;quot;Page&amp;quot;:&amp;quot;Page&amp;quot;,&amp;quot;ElasticSearchPage&amp;quot;:&amp;quot;Elastic Search Page&amp;quot;,&amp;quot;POIMapPage&amp;quot;:&amp;quot;Points of Interest Page&amp;quot;,&amp;quot;Plot&amp;quot;:&amp;quot;Plot&amp;quot;,&amp;quot;Blog&amp;quot;:&amp;quot;Blog&amp;quot;,&amp;quot;BlogPost&amp;quot;:&amp;quot;Blog Post&amp;quot;,&amp;quot;GutenbergBookExtract&amp;quot;:&amp;quot;Gutenberg Book Extract&amp;quot;,&amp;quot;ErrorPage&amp;quot;:&amp;quot;Error Page&amp;quot;,&amp;quot;RedirectorPage&amp;quot;:&amp;quot;Redirector Page&amp;quot;,&amp;quot;VirtualPage&amp;quot;:&amp;quot;Virtual Page&amp;quot;}&quot;&gt;Standard Search&lt;/span&gt;
)
*/
