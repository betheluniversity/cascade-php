<?php

function create_moodle_feed(){
    $feed = autoCache("create_moodle_feed_logic", array(), 300, "No");
    return $feed;
}

function create_moodle_feed_logic(){
    //include_once $_SERVER["DOCUMENT_ROOT"] . "/code/php_helper_for_cascade.php";
    //include_once $_SERVER["DOCUMENT_ROOT"] . "/code/general-cascade/feed_helper.php";

    $url = "https://wsapi.xp.bethel.edu/salesforce/moodle-enrichment";
    try {
        $results = json_decode(@file_get_contents($url));
    } catch(ErrorException $e) {
        $results = $e;
    }
    foreach ($results as $class) {
        $class_array = json_decode(json_encode($class), true);
        print "Name: " . $class_array[Name] . "\n";
        print "Description: " . $class_array[Description__c] . "\n";
        print "ID: " . $class_array[Id] . "\n";
        print "Registration Cost: " . $class_array[Registration_Cost__c] . "\n";
        print "Attributes: " . $class_array[attributes] . "\n";
        print "Start Date: " . $class_array[hed__Start_Date__c] . "\n";
        print "End Date: " . $class_array[hed__End_Date__c] . "\n";
    }
    print_r($class_array);
    return '1';
}

