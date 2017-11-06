<?php
/**
 * Created by PhpStorm.
 * User: ejc84332
 * Date: 4/27/15
 * Time: 8:42 AM
 */


// this is the cached version
function course_catalog_call($code, $values){
    $data = array('options' => $values);
    $options = array(
        'http' => array(
            'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
            'method'  => 'POST',
            'content' => http_build_query($data),
        ),
    );
    $url = "https://wsapi.bethel.edu/courses/course-catalog/$code";
    $context  = stream_context_create($options);
    return file_get_contents($url, false, $context);
}

// this is the cached version
function individual_courses_call($code){
    $url = "https://wsapi.bethel.edu/courses/open-enrollment-courses/$code";
    return file_get_contents($url, false);
}

function individual_courses($code){
    $content = autoCache('individual_courses_call', array($code, 900));
    return $content;
}

function load_course_catalog ($values, $code) {
    $content = autoCache('course_catalog_call', array($code, $values, 900));
    $content = json_decode($content, true);

    if( strpos($content['data'], '<li') !== false ) {
        echo "<h2>Courses</h2>";
        echo $content['data'];
    }
}
