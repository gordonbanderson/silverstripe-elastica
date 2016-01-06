<?php

/**
 */
class SearchableTestPage extends Page implements TestOnly
{
    private static $searchable_fields = array('Country', 'PageDate');

    private static $db = array(
        'Country' => 'Varchar',
        'PageDate' => 'Date',
    );
}

/**
 */
class SearchableTestPage_Controller extends Controller implements TestOnly
{
}
