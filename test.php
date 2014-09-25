<?php

$key = '4d8e4e3b3bb356e43ca0285422be903ac3869ab2d33224f514c0c0e0b2533e7a';
$secret = 'finykVRLogAMLZepVPb2TPEVosaWxKgG9JALrEhgDvCathPoUz';
$timestamp = time();

$signature = base64_encode(hash_hmac('sha1', $secret, $key));

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "http://wsapi.bethel.edu/username/ejc84332/");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
curl_setopt($ch, CURLOPT_HEADER, FALSE);
curl_setopt($ch, CURLOPT_POST, TRUE);
curl_setopt($ch, CURLOPT_HTTPHEADER, "HMAC: UKW-EaC9diBPuRTgwaUprw4pf4h1nTJyClCT48dbhQo");
$response = curl_exec($ch);
curl_close($ch);
echo $response;

?>
