<?php
/**
 * Created by PhpStorm.
 * User: ces55739
 * Date: 8/17/15
 * Time: 3:52 PM
 */

// todo: rename and move this to a general method!
// This is a general method, I know it is used for the wufoo cascade format. It could be used elsewhere (11/2/17)
function fetchJSONFile($url, $data, $print=true, $method='POST') {
    if (strpos($_SERVER['HTTP_HOST'], 'staging') !== false) {
        $url = str_replace('.bethel.edu', '.xp.bethel.edu', $url);
    }

    $opts = array('http' =>
        array(
            'method'  => $method,
            'header'  => 'Content-type: application/x-www-form-urlencoded',
            'content' => http_build_query($data)
        )
    );
    $context = stream_context_create($opts);
    $json = file_get_contents($url, false, $context);
    $json = json_decode($json, true);

    // temporary code in transition
    // todo: after this code is launched, we can update the wufoo code that calls this. There may be other instances
    // that we should check for
    if( array_key_exists('data', $json)){
        $json = $json['data'];
    }

    if( $print)
        echo $json;
    else
        return $json;
}