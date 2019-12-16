<?php

session_start();

$staging = strstr(getcwd(), "/staging");

//prepare a URL for returing
if ($staging){
    $url = 'https://staging.bethel.edu/admissions/apply/';
}else{
    $url = 'https://www.bethel.edu/admissions/apply';
}

$email = $_POST["email"];
$email = strtolower($email);
$email = trim($email);

//log_entry($email);
$first = $_POST["first"];
$last = $_POST["last"];

$payload = array(
    "email" => $email,
    "first_name" => $first,
    "last_name" => $last
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
    $wsapi_url = 'https://wsapi.xp.bethel.edu/salesforce/register';
}else{
    $wsapi_url = 'https://wsapi.bethel.edu/salesforce/register';
}

// Here is the returned value
$result = file_get_contents($wsapi_url, false, $context);

$json = json_decode($result, true);


if($json['success'] == true){
    $contact_id = $json['contact_id'];
    $url = "https://www.bethel.edu/admissions/apply/confirm?cid=$contact_id";
    echo $url;

///
//    $subject = "Created account for email $email with cid=$contact_id and uid=$user_id";
//    mail($mail_to,$subject,$subject,"From: $from\n");
//    log_entry($subject);
//
//    header("Location: $url");
}else{
    echo 'api returned false';
//    $subject = "Failed to create account for email $email with cid=$contact_id and uid=$user_id";
//    mail($mail_to,$subject,$subject,"From: $from\n");
//    log_entry($subject);
//    log_entry($json);
//    $url .= "?email=false";
//    header("Location: $url");
}

?>
