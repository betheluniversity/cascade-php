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
        array_push( $courses, json_decode(json_encode($class), true));
    }

    return $twig->render('onramps_feed.html', array('results' => $courses));
}

