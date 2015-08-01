<?php
/**
* Only show a page with login when not logged in
*/
class ElasticSearchPage extends Page {
		static $defaults = array(
			'ShowInMenus' => 0,
		 	'ShowInSearch' => 0,
		 	'ClassesToSearch' => 'Page'
		);

		private static $db = array('ClassesToSearch' => 'Text');


		function getCMSFields() {
			$fields = parent::getCMSFields();
			$fields->addFieldToTab('Root.ClassesToSearch', new TextField('ClassesToSearch'));
			return $fields;
		}
}


class ElasticSearchPage_Controller extends Page_Controller {

	private static $allowed_actions = array('ElasticSearchForm', 'results');

	public function init() {
		parent::init();
	}

	/* Results from search submission */
	function results($data, $form) {
		$startTime = microtime(true);
		$resultList = $form->getResults();

		// at this point ResultList object, not yet executed search query

		$searchResultsPaginated = new \PaginatedList(
			$resultList,
			\Controller::curr()->request
		);

		$searchResultsPaginated->setTotalItems($resultList->getTotalItems());

		/*
		$list->setPageStart($start);
		$list->setPageLength($pageLength);
		$list->setTotalItems($totalCount);
		 */

		// basic form
		$data = array(
			'SearchResults' => $searchResultsPaginated,
			'Test' => 'Testing'
			/*,
			'Query' => $form->getSearchQuery(),
			'Title' => $this->Title,
			'PageNumber' => 4
			*/

		);



		//$this->Query = $form->getSearchQuery();



		$endTime = microtime(true);

		$elapsed = round(100*($endTime-$startTime))/100;
		$data['ElapsedTime'] = $elapsed;


		return $this->customise($data)->renderWith(array('ElasticSearchPageResults', 'Page'));
	}



	/*
	Search form components
	*/
	function ElasticSearchForm() {

		// show search term or empty text
		$searchText = isset($this->Query) ? $this->Query : '';

		$tf = new TextField("Search", "", $searchText);
		$tf->addExtraClass('small-9 medium-10 large-11 columns');

		$fields = new FieldList(
			$tf
		);

		// form action is here, but to which page does it go
		$fa = new FormAction('results', _t('SearchPage.SEARCH', 'Search'));

		// for zurb
		$fa->useButtonTag = true;
		$fa->addExtraClass('button tiny small-3 medium-2 large-1 columns');

		$actions = new FieldList(
			$fa
		);

		$requiredFields = new RequiredFields();
		$form = new ElasticSearchForm($this, "ElasticSearchForm", $fields, $actions, $requiredFields);
		$form->addExtraClass('inline');

		// restrict the scope to certain classes
		$form->setTypes($this->ClassesToSearch);
		return $form;
	}


	function forTemplate() {
			return $this->renderWith(array(
				 $this->class,
				 'ElasticSearchForm'
			));
	 }




}
