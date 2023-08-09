<?php

function create_moodle_feed($org_id){
    $feed = autoCache("create_moodle_feed_logic", array($org_id), 300, "No");
    return $feed;
}

function create_moodle_feed_logic($org_id){

    $twig = makeTwigEnviron('/code/salesforce/twig');

    $staging = strstr(getcwd(), "/staging");

    //Changes the authenticating URL depending on the staging environment
    if ($staging){
        $wsapi_url = 'https://wsapi.xp.bethel.edu/salesforce/course-offerings';
    }else{
        $wsapi_url = 'https://wsapi.bethel.edu/salesforce/course-offerings';
    }

    if ($org_id) {
        $wsapi_url .= "/";
        $wsapi_url .= $org_id;
    }

    try {
        $results = json_decode(@file_get_contents($wsapi_url));
    } catch(ErrorException $e) {
        $results = $e;
    }

    $courses = Array();
    foreach ($results as $class) {
        array_push( $courses, json_decode(json_encode($class), true));
    }

    return $twig->render('course_offerings.html', array('courses' => $courses));
}

