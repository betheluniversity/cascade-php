<?php

function create_moodle_feed(){
    $feed = autoCache("create_moodle_feed_logic", array(), 300, "No");
    return $feed;
}

function create_moodle_feed_logic(){
    //include_once $_SERVER["DOCUMENT_ROOT"] . "/code/php_helper_for_cascade.php";
    //include_once $_SERVER["DOCUMENT_ROOT"] . "/code/general-cascade/feed_helper.php";

    $url = 'https://bethel-university--full.sandbox.my.salesforce.com/services/apexrest/course';
    $xml = simplexml_load_file($url);
    print_r($xml);
    return '1';
}

?>
