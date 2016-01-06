<?php
/**
 * Elastica Search Form.
 */
use Elastica\Query;

class ElasticSearchForm extends Form
{
    /**
     * List of types to search for, default (blank) returns all.
     *
     * @var string
     */
    private $types = '';

    /**
     * @param Controller $controller
     * @param string     $name       The name of the form (used in URL addressing)
     * @param FieldList  $fields     Optional, defaults to a single field named "Search". Search logic needs to be customized
     *                               if fields are added to the form.
     * @param FieldList  $actions    Optional, defaults to a single field named "Go".
     */
    public function __construct($controller, $name, $fields = null, $actions = null)
    {
        $searchText = isset($this->Query) ? $this->Query : '';
        $fields = new FieldList(
           $tf = new TextField('q', '', $searchText)
        );

        $buttonText = _t('SearchPage.SEARCH', 'Search');
        $actions = new FieldList(
            $fa = new FormAction('submit', $buttonText)
        );

        $this->SubmitButton = $fa;

        if (class_exists('Translatable') && singleton('SiteTree')->hasExtension('Translatable')) {
            $fields->push(new HiddenField('searchlocale', 'searchlocale', Translatable::get_current_locale()));
        }

        parent::__construct($controller, $name, $fields, $actions);
        $this->setFormMethod('post');
        $this->disableSecurityToken();
        $this->enableAutocomplete();
    }

    public function enableAutocomplete()
    {
        $q = $this->Fields()->fieldByName('q');
        $q->addExtraClass('autocomplete');
    }

    /**
     * Optionally allow templates to override the text of the submit button, perhaps if using a icon font
     * as per the default Simple theme.
     *
     * @param string $newButtonText Alternative search text
     */
    public function setButtonText($newButtonText)
    {
        $this->actions = new FieldList(
            $fa = new FormAction('submit', $newButtonText)
        );
    }
}
