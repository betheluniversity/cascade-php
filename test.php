<?php

$secret = '3pwmt3tyscqMapW';
$timestamp = time();

$path_and_query = "/username/ejc84332/?TIMESTAMP=$timestamp&ACCOUNT_ID=labs";
$host = "http://wsapi.bethel.edu";

$signature = hash_hmac('sha1', $path_and_query, $secret);


$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $host . $path_and_query);
curl_setopt($ch, CURLOPT_HTTPHEADER, array("X-Auth-Signature: $signature"));
$response = curl_exec($ch);
curl_close($ch);
echo $response;


#host = "http://localhost:8080"
//sig = hmac.new("", digestmod=hashlib.sha1,  msg=path_and_query).hexdigest()
//print sig
//print host+path_and_query
//req = requests.get(host+path_and_query, headers={'})
//print req

?>
