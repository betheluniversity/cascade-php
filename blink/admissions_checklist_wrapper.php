<?php
/**
 * Created by PhpStorm.
 * User: ces55739
 * Date: 8/28/15
 * Time: 2:11 PM
 */

$url = 'http://wsapi.bethel.edu/blink/admissions-checklist/';
$username = $_GET['urn:sungardhe:dir:loginId'];

$url = $url.$username;
$results = json_decode(file_get_contents($url), true);

echo html_entity_decode($results['html']);

