<?php

function create_moodle_feed(){
    $feed = autoCache("create_moodle_feed_logic", array(), 300, "No");
    return $feed;
}

function create_moodle_feed_logic(){
    //include_once $_SERVER["DOCUMENT_ROOT"] . "/code/php_helper_for_cascade.php";
    //include_once $_SERVER["DOCUMENT_ROOT"] . "/code/general-cascade/feed_helper.php";
    $twig = makeTwigEnviron('/code/moodle-enrichment/twig');
    $url = "https://wsapi.xp.bethel.edu/salesforce/moodle-enrichment";

    try {
        $results = json_decode(@file_get_contents($url));
    } catch(ErrorException $e) {
        $results = $e;
    }

    $courses = Array();
    foreach ($results as $class) {
        $class_array = json_decode(json_encode($class), true);
        print "Name: " . $class_array[Name] . "</br>";
        print "Description: " . $class_array[Description__c] . "</br>";
        print "ID: " . $class_array[Id] . "</br>";
        print "Registration Cost: " . $class_array[Registration_Cost__c] . "</br>";
        print "Type: " . $class_array[attributes][type] . "</br>";
        print "URL: " . $class_array[attributes][url] . "</br>";
        print "Registration Cost: " . $class_array[Registration_Cost__c] . "</br>";
        print "Start Date: " . $class_array[hed__Start_Date__c] . "</br>";
        print "End Date: " . $class_array[hed__End_Date__c] . "</br>";
//        $html = $twig->render('onramps_feed.html', array(
//            'results' => $course));
    }



    return '1';
}

