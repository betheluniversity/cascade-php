<?php
/**
 * Created by PhpStorm.
 * User: ejc84332
 * Date: 8/2/16
 * Time: 2:39 PM
 */

$user = $_POST['user'];

$staging = strstr(getcwd(), "/staging");

$payload = array(
    "email" => $user['email'],
    "first_name" => $user['firstName'],
    "last_name" => $user['lastName'],
    "school" => $_POST['degree-type']
);

$json_payload = json_encode($payload);
$options = array(
    'http' => array(
        'header'  => "Content-type: application/json",
        'method'  => 'POST',
        'content' => $json_payload,
    ),
);

$context  = stream_context_create($options);

//Changes the authenticating URL depending on the staging enviroment
if ($staging){
    $wsapi_url = 'https://wsapi.xp.bethel.edu/salesforce/homepagerfi';
}else{
    $wsapi_url = 'https://wsapi.bethel.edu/salesforce/homepagerfi';
}


// Here is the returned value
$result = file_get_contents($wsapi_url, false, $context);

$json = json_decode($result, true);