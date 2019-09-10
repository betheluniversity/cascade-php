<?php
/**
 * Created by PhpStorm.
 * User: cav28255
 * Date: 5/6/15
 * Time: 9:16 AM
 */


//called by cas-summer-courses format so it can use autoCache
function summer_courses($code){

    $url = "https://wsapi.bethel.edu/courses/$code";
    return file_get_contents($url);
}