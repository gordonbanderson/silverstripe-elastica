<?php

/**
 */
class SearchableTestFatherPage extends SearchableTestPage
{
    private static $searchable_fields = array('FatherText');

    private static $db = array(
        'FatherText' => 'Varchar(255)',
    );
}

/**
 */
class SearchableTestFatherPage_Controller extends SearchableTestPage_Controller
{
}
