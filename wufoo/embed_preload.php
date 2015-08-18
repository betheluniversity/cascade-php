<?php
/**
 * Created by PhpStorm.
 * User: ces55739
 * Date: 8/17/15
 * Time: 3:52 PM
 */

function fetchJSONFile($url, $print=true) {
    // get the global $remote_user from cas.php
    global $remote_user;
    $url = "$url/$remote_user";
    $json = file_get_contents($url);
    $json = json_decode($json, true);

    if( $print)
        echo $json['data'];
    else
        return $json['data'];
}