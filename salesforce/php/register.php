<?php

session_start();

$staging = strstr(getcwd(), "/staging");

//Changes the authenticating URL depending on the staging environment
if ($staging){
    $wsapi_url = 'https://wsapi.xp.bethel.edu/salesforce/send_to_auth';
}else{
    $wsapi_url = 'https://wsapi.bethel.edu/salesforce/send_to_auth';
}

//prepare a URL for returning
$url = isset($_POST["referrer"]) ? $_POST["referrer"] : '';

$redir = isset($_POST["redir"]) ? $_POST["redir"] : '';

$email = $_POST["email"];
$email = strtolower($email);
$email = trim($email);

$first = $_POST["first"];
$last = $_POST["last"];
$programCode = isset($_POST["programCode"]) ? $_POST["programCode"] : '';
$quickCreate = isset($_POST["quickCreate"]) ? $_POST["quickCreate"] : '';

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
    'utm_campaign' => $utm_campaign,
    'program_code' => $programCode,
    'quick_create' => $quickCreate,
    'redir' => $redir
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

if($json['success'] == true && $json['account_recovery'] == true){
    $url .= (parse_url($url, PHP_URL_QUERY) ? '&' : '?') . 'existing-account=true';
    header("Location: $url");
}elseif($json['success'] == true){
    $contact_id = $json['contact_id'];
    $url = "https://www.bethel.edu/admissions/apply/confirm?cid=$contact_id";
    header("Location: $url");
}else{
    $url .= (parse_url($url, PHP_URL_QUERY) ? '&' : '?') . 'email=false';
    header("Location: $url");
}
