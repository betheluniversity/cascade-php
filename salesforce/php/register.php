<?php

session_start();

$staging = strstr(getcwd(), "/staging");

//prepare a URL for returning
if ($staging){
    $url = 'https://staging.bethel.edu/admissions/apply/';
}else{
    $url = 'https://www.bethel.edu/admissions/apply';
}

$email = $_POST["email"];
$email = strtolower($email);
$email = trim($email);


$first = $_POST["first"];
$last = $_POST["last"];

// prep UTM data
$utm_source = '';
$utm_medium = '';
$utm_campaign = '';

if( $_COOKIE['utm_source'] )
    $utm_source = ucwords(str_replace('_', ' ', $_COOKIE['utm_source']));
if( $_COOKIE['utm_medium'] )
    $utm_medium = ucwords(str_replace('_', ' ', $_COOKIE['utm_medium']));
if( $_COOKIE['utm_campaign'] )
    $utm_campaign = $_COOKIE['utm_campaign'];

$payload = array(
    "email" => $email,
    "first_name" => $first,
    "last_name" => $last,
    'utm_source' => $utm_source,
    'utm_medium' => $utm_medium,
    'utm_campaign' => $utm_campaign
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

//Changes the authenticating URL depending on the staging environment
if ($staging){
    $wsapi_url = 'https://wsapi.xp.bethel.edu/salesforce/register';
}else{
    $wsapi_url = 'https://wsapi.bethel.edu/salesforce/register';
}

// Here is the returned value
$result = file_get_contents($wsapi_url, false, $context);

$json = json_decode($result, true);

if($json['success'] == true && $json['account_recovery'] == true){
    $url .= "?account-recovery=true";
    header("Location: $url");
}elseif($json['success'] == true){
    $contact_id = $json['contact_id'];
    $url = "https://www.bethel.edu/admissions/apply/confirm?cid=$contact_id";
    header("Location: $url");
}else{
    $url .= "?email=false";
    header("Location: $url");
}
