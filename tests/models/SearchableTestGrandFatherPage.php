<?php

/**
 */
class SearchableTestGrandFatherPage extends SearchableTestFatherPage implements TestOnly
{
    private static $searchable_fields = array('GrandFatherText');

    private static $db = array(
        'GrandFatherText' => 'Varchar(255)',
    );
}

/**
 */
class SearchableTestGrandFatherPage_Controller extends SearchableTestFatherPage_Controller implements TestOnly
{
}
