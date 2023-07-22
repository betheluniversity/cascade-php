<?php

session_start();

$staging = strstr(getcwd(), "/staging");

//Changes the authenticating URL depending on the staging environment
if ($staging){
    $wsapi_url = 'https://wsapi.xp.bethel.edu/salesforce/check-registration';
    $moodle_url = 'https://moodle.xp.bethel.edu';
}else{
    $wsapi_url = 'https://wsapi.bethel.edu/salesforce/check-registration';
    $moodle_url = 'https://moodle.bethel.edu';
}

$reg_id = isset($_POST["reg_id"]) ? $_POST["reg_id"] : '';
$referrer = isset($_POST["referrer"]) ? $_POST["referrer"] : '';

$payload = array(
    "reg_id" => $reg_id
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

// Here is the returned value
$result = file_get_contents($wsapi_url, false, $context);
$json = json_decode($result, true);

$url = $url = $referrer . '?error=true';
if($json['status'] == 'success'){
    $url = $moodle_url;
}elseif($json['status'] == 'timeout'){
    $url = $referrer . '?timeout=true';
}
header("Location: $url");
