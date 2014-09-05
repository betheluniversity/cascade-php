<?php
$staging = strstr(getcwd(), "staging/public");

function escapeEmail($email) {
    $characters = array('?', '&', '!', '^', '+', '-');
    $resp = "";
    $email = str_split($email);
    foreach ($email as $char){
        if(in_array($char, $characters)){
            $resp .= "\\" . $char;
        }else{
            $resp .= $char;
        }
    }
    return $resp;
}

//prepare a URL for returing
if ($staging){
    $url = 'http://staging.bethel.edu/admissions/apply/';
}else{
    $url = 'https://apply.bethel.edu/';
}

// SOAP_CLIENT_BASEDIR - folder that contains the PHP Toolkit and your WSDL
// $USERNAME - variable that contains your Salesforce.com username (must be in the form of an email)
// $PASSWORD - variable that contains your Salesforce.ocm password
define("SOAP_CLIENT_BASEDIR", "toolkit/soapclient");
require_once (SOAP_CLIENT_BASEDIR.'/SforceEnterpriseClient.php');
require_once (SOAP_CLIENT_BASEDIR.'/SforceHeaderOptions.php');
require_once ('userAuth.php');
try {
    $mySforceConnection = new SforceEnterpriseClient();
    $mySoapClient = $mySforceConnection->createConnection(SOAP_CLIENT_BASEDIR.'/enterprise.wsdl.xml');
    $mylogin = $mySforceConnection->login($USERNAME, $PASSWORD);

    $email = $_POST["email"];
    $first = $_POST["first"];
    $last = $_POST["last"];

    $search_email = escapeEmail($email);
    $search_email = '{' . $search_email . '}';
    // search for a Contact with this email?
    $response = $mySforceConnection->search("find $search_email in email fields returning contact(email, id)");
    $records = $response->{'searchRecords'};
    $output = print_r($response,1);
    error_log('contact search : ' . $output);
    $has_contact = sizeof($records);
    if ($has_contact > 0){
        //We found it, get the id of the first (email is unique, so only one result)
        $contact_id = $records[0]->Id;
    }else{
        //Create one and save the id
        $sObject = new stdclass();
        $sObject->FirstName = $first;
        $sObject->LastName = $last;
        $sObject->Email = $email;
        $createResponse = $mySforceConnection->create(array($sObject), 'Contact');
        $contact_id = $createResponse[0]->Id;
    }

    if ($contact_id == ""){
        $url .= "?cid=false";
        header("Location: $url");
        exit;
    }else{
        error_log("contact id is : " . $contact_id);
    }



    // test for existing user
    $response = $mySforceConnection->search("find $search_email in email fields returning user(email, id)");
    $output = print_r($response,1);
    error_log('user search : ' . $output);
    $has_contact = sizeof($records);
    $records = $response->{'searchRecords'};
    $has_user = sizeof($records);
    if ($has_user > 0){
        //Contact already has a user, go to account recovery page. (Or login?)
        $user_id = $records[0]->Id;
        error_log('found user_id');
    }
    else{
        error_log('user lookup failed');
        $user_id = false;
    }

    // Create user account based on contact info.
    if (!$has_user){
        $sObject = new stdclass();
        //now create a User. If there is a user they have an account already.
        //  Now create a User object tied to this Contact
        $sObject->Username = $email;
        $sObject->Email = $email;
        $sObject->LastName = $last;
        $sObject->FirstName = $first;
        $sObject->Alias = strtolower(substr($first, 0, 1) . substr($last, 0, 4)); //first letter of first name + 4 letters of last name?
        $sObject->TimeZoneSidKey = "America/Chicago";
        $sObject->LocaleSidKey = "en_US";
        $sObject->EmailEncodingKey = "UTF-8";
        $sObject->ProfileId = $PORTALUSERID; // profile id?
        $sObject->ContactId = $contact_id;
        $sObject->LanguageLocaleKey = "en_US";
        $createResponse = $mySforceConnection->create(array($sObject), 'User');
        $output = print_r($createResponse,1);
        error_log('create user : ' . $output);
        $user_id = $createResponse[0]->{'Id'};
    }

    if (!$user_id){
        $url .= "?uid=false";
        header("Location: $url");
        exit;
    }else{
        error_log("user id is : " . $user_id);
    }

} catch (Exception $e) {
    echo $mySforceConnection->getLastRequest();
    echo $e->faultstring;
}

// Check for frozen account.
try{
    $response = $mySforceConnection->query("SELECT Id, UserId, IsFrozen FROM UserLogin WHERE UserId = '$user_id'");
    $is_frozen = $response->{'records'}[0]->{'IsFrozen'};
    $frozen_id = $response->{'records'}[0]->Id;
}catch (Exception $e){
    //It fails if there is no record (never frozen)
    $is_frozen = false;
    $frozen_id = null;
}

//unfreeze if needed
if ($is_frozen){
    $sObject1 = new stdclass();
    $sObject1->Id = $frozen_id;
    $sObject1->IsFrozen = 0;
    //commit the update
    $response = $mySforceConnection->update(array ($sObject1), 'UserLogin');
}
//
####################################################################
## CAS account creation.
####################################################################
$credentials = array(
                    "auth" => array(
                        "username" => $BETHELAUTHUSERNAME,
                        "passwd" => $BETHELAUTHPASSWD,
                    ),
                    "user" => array(
                        "email" => $email,
                        "first_name" => $first,
                        "last_name" => $last,
                        "activate_email" => "true"
                    )
                );
//need this?
//if ($is_frozen){
//    //reset the password if it was frozen so the auth account is reset
//    $credentials['user']['reset'] = 'true';
//}
$credentials = json_encode($credentials);
$options = array(
    'http' => array(
        'header'  => "Content-type: application/json",
        'method'  => 'POST',
        'content' => $credentials,
    ),
);
$context  = stream_context_create($options);

if ($staging){
    $auth_url = 'https://auth.xp.bethel.edu/auth/email-account-management.cgi';
}else{
    $auth_url = 'https://auth.bethel.edu/auth/email-account-management.cgi';
}

// Here is the returned value
$result = file_get_contents($auth_url, false, $context);
$json = json_decode($result, true);

if($json['status'] == "success"){
    $url .= "?email=true&uid=$user_id&cid=$contact_id";
}else{
    $url .= "?email=false";
}

header("Location: $url");


?>