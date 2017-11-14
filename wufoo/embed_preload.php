<?php
/**
 * Created by PhpStorm.
 * User: ces55739
 * Date: 8/17/15
 * Time: 3:52 PM
 */

// This is a general method, I know it is used for the wufoo cascade format. It could be used elsewhere (11/2/17)
function fetchJSONFile($url, $data, $print=true) {
    $opts = array('http' =>
        array(
            'method'  => 'POST',
            'header'  => 'Content-type: application/x-www-form-urlencoded',
            'content' => http_build_query($data)
        )
    );
    $context = stream_context_create($opts);
    $json = file_get_contents($url, false, $context);
    $json = json_decode($json, true);

    if( $print)
        echo $json['data'];
    else
        return $json['data'];
}