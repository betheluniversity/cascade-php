<?php

$courses = isset($_POST["courses"]) ? $_POST["courses"] : [];
if ($courses) {
    $response = process_form($_POST);
    //echo $response;
}

function process_form($data) {
    $account_url = isset($data["account_url"]) ? $data["account_url"] : '';
    $type = isset($data["type"]) ? $data["type"] : '';
    $courses = isset($data["courses"]) ? $data["courses"] : [];

    if ($type) {
        $account_url .= "?type=";
        $account_url .= $type;
        if ($courses) {
            $account_url .= "&courses=";
            $account_url .= implode('%3B', $courses);
        }
    } elseif ($courses) {
        $account_url .= "?courses=";
        $account_url .= implode('%3B', $courses);
    }

    header("Location: $account_url");
}

function get_course_offerings_cached($params){
    $courses = autoCache("get_course_offerings", $params, 300, "No");
    return $courses;
}

function get_course_offerings($params){

    // Get the path to this file
    $php_path = '/code' . explode('code', __FILE__)[1];

    // Get the needed parameters
    $account_url = isset($params["account_url"]) ? $params["account_url"] : '';
    $type = isset($params["type"]) ? $params["type"] : 'all';

    // Get the prod or staging WSAPI URL
    $staging = strstr(getcwd(), "/staging");
    if ($staging){
        $wsapi_url = 'https://wsapi.xp.bethel.edu';
    }else{
        $wsapi_url = 'https://wsapi.bethel.edu';
    }
    $wsapi_url .= '/salesforce/course-offerings/' . $type;

    // Get the list of courses from WSAPI
    try {
        $results = json_decode(@file_get_contents($wsapi_url));
    } catch(ErrorException $e) {
        $results = $e;
    }

    $courses = Array();
    foreach ($results as $course) {
        array_push( $courses, json_decode(json_encode($course), true));
    }

    // Process the courses list
    if ($type != 'all' && !$courses) {
        // Invalid Org Code entered. Refresh using no parameters.
        return '<script type="text/javascript">window.location = window.location.href.split("?")[0];</script>';
    } else {
        $twig = makeTwigEnviron('/code/salesforce/twig');
        $data = array(
            'php_path' => $php_path,
            'account_url' => $account_url,
            'type' => $type,
            'courses' => $courses
        );
        return $twig->render('course_offerings.html', $data);
    }
}
