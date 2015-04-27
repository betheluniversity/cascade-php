<?php
/**
 * Created by PhpStorm.
 * User: ejc84332
 * Date: 4/27/15
 * Time: 8:42 AM
 */


function course_catalog($code, $values){

    $data = array('options' => $values);
    $options = array(
        'http' => array(
            'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
            'method'  => 'POST',
            'content' => http_build_query($data),
        ),
    );
    $url = "http://wsapi.bethel.edu/courses/course-catalog/$code";
    $context  = stream_context_create($options);
    $content = file_get_contents($url, false, $context);

}