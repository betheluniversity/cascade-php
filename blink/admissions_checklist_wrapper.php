<?php
/**
 * Created by PhpStorm.
 * User: ces55739
 * Date: 8/28/15
 * Time: 2:11 PM
 */

$url = 'http://wsapi.bethel.edu/blink/admissions-checklist/';

if ( array_key_exists('urn:sungardhe:dir:loginId', $_GET))
    $username = $_GET['urn:sungardhe:dir:loginId'];
else
    $username = $_GET['username'];

$url = $url.$username;
$results = json_decode(file_get_contents($url), true);

echo html_entity_decode($results['html']);
