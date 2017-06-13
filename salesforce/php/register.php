<?php


session_start();

// SOAP_CLIENT_BASEDIR - folder that contains the PHP Toolkit and your WSDL
// $USERNAME - variable that contains your Salesforce.com username (must be in the form of an email)
// $PASSWORD - variable that contains your Salesforce.ocm password
define("SOAP_CLIENT_BASEDIR", "toolkit/soapclient");
require_once (SOAP_CLIENT_BASEDIR.'/SforceEnterpriseClient.php');
require_once (SOAP_CLIENT_BASEDIR.'/SforceHeaderOptions.php');
require_once ('userAuth.php');


if(isset($_SESSION['interesting_referer'])){
    $referer = $_SESSION['interesting_referer'];
}else{
    $referer = $_SESSION["HTTP_REFERER"];
    $referer = explode('/', $referer);
    // $referer : Array ( [0] => https: [1] => [2] => staging.bethel.edu [3] => _testing [4] => jmo [5] => basic ) Array
    $referer = $referer[3];
}



$mySforceConnection = new SforceEnterpriseClient();
$mySoapClient = $mySforceConnection->createConnection(SOAP_CLIENT_BASEDIR.'/enterprise.wsdl.xml');
$mylogin = $mySforceConnection->login($USERNAME, $PASSWORD);

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

function log_entry($message){
    error_log($message . "\n");
}

function search_for_contact($email){
    global $mySforceConnection;
    // search for a Contact with this email?
    $response = $mySforceConnection->query("SELECT Email, Id FROM Contact WHERE Email = '$email'");
    $records = $response->{'records'};
    $output = print_r($response,1);
    log_entry('contact search : ' . $output);
    $has_contact = sizeof($records);
    return $records;
}

function create_new_contact($first, $last, $email){
    global $mySforceConnection;
    global $referer;
    log_entry('Referrer: '. $referer);

    $sObject = new stdclass();
    $sObject->FirstName = $first;
    $sObject->LastName = $last;
    $sObject->Email = $email;
    $sObject->referrer_site__c = $referer;
    try{
        $createResponse = $mySforceConnection->create(array($sObject), 'Contact');
    }catch(Exception $e){
        log_entry("failed to create contact");
        log_entry($e->getMessage());
        return null;
    }

    $contact_id = $createResponse[0]->id;
    $output = print_r($createResponse,1);
    log_entry('Referrer: '. $referer);
    log_entry('contact create : ' . $output);
    return $contact_id;
}

function search_for_user($email){
    global $mySforceConnection;
    // test for existing user
    $response = $mySforceConnection->query("SELECT Email, Id FROM User WHERE Email = '$email'");
    $records = $response->{'records'};
    return $records;
}

function create_new_user($first, $last, $email, $contact_id){
    global $mySforceConnection;
    global $PORTALUSERID;
    $sObject = new stdclass();
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
    try{
        $createResponse = $mySforceConnection->create(array($sObject), 'User');
    }catch(Exception $e){
        log_entry("failed to create user");
        log_entry($e->getMessage());
        return null;
    }

    $output = print_r($createResponse,1);
    log_entry('create user : ' . $output);
    $user_id = $createResponse[0]->id;
    return $user_id;
}

function update_contact_referer_site($contact_id){
    global $mySforceConnection;
    global $referer;
    $records[0] = new stdclass();
    $records[0]->Id = $contact_id;
    $records[0]->referrer_site__c = $referer;

    $response = $mySforceConnection->update($records, 'Contact');
    foreach ($response as $result) {
        log_entry($result->id . " updated referer site<br/>\n");
    }
}

function add_referer_to_contact($contact_id){
    global $mySforceConnection;

    $sObject = new stdclass();
    $sObject->Contact__c = $contact_id;
    $sObject->Referrer_Type__c = "Application";
    // This object should only be interesting_referer. Blank if there isn't one.
    $sObject->Referer_URL__c = $_SESSION['interesting_referer'];

    try {

        $createResponse = $mySforceConnection->create(array($sObject), 'Referrer__c');
    }catch (Exception $e){
        return "add_referer_to_contact fail";
    }
    return $createResponse;
}


$staging = strstr(getcwd(), "staging/public");
$mail_to = "web-development@bethel.edu";
$mail_from = "salesforce-register@bethel.edu";
$subject = "salesforce register submission";
$message = "";

//prepare a URL for returing
if ($staging){
    $url = 'http://staging.bethel.edu/admissions/apply/';
}else{
    $url = 'https://www.bethel.edu/admissions/apply';
}

$email = $_POST["email"];
$email = strtolower($email);
log_entry($email);
$first = $_POST["first"];
$last = $_POST["last"];

$search_email = escapeEmail($email);
$search_email = '{' . $search_email . '}';


try {
        $contact_id = "";
        $contact_records = search_for_contact($email);
        if (sizeof($contact_records) > 0){
            //We found it, get the id of the first (email is unique, so only one result)
            $contact_id = $contact_records[0]->Id;

            // update the referer site
            update_contact_referer_site($contact_id);

        }else{
            log_entry('no contact found. Creating...');
            //Create one and save the id
            $contact_id = create_new_contact($first, $last, $email);
        }

        if ($contact_id == ""){
            // todo why does this case happen?
            $url .= "?cid=false";
            $subject = "failed to find or create contact id for email $email";
            log_entry($subject);
            mail($mail_to,$subject,$subject,"From: $from\n");
            header("Location: $url");
            exit;
        }else{
            log_entry("contact id is : " . $contact_id);
        }

        $user_id = "";
        $user_records = search_for_user($email);
        if (sizeof($user_records) > 0){
            //Contact already has a user, go to account recovery page. (Or login?)
            $user_id = $user_records[0]->Id;
            log_entry('found user_id');
        }
        else{
            log_entry('No user found. Creating...');
            $user_id = create_new_user($first, $last, $email, $contact_id);
        }
        log_entry("user_id is " . $user_id);

        if ($user_id == ""){
            // todo why does this case happen?
            $url .= "?uid=false";
            $subject = "failed to find or create user id for email $email with cid=$contact_id";
            mail($mail_to,$subject,$subject,"From: $from\n");
            log_entry($subject);
            header("Location: $url");
            exit;
        }else{
            log_entry("user id is : " . $user_id);
        }

        add_referer_to_contact($contact_id);

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
    $url = "https://www.bethel.edu/admissions/apply/confirm?cid=$contact_id";

    $subject = "Created account for email $email with cid=$contact_id and uid=$user_id";
    mail($mail_to,$subject,$subject,"From: $from\n");
    log_entry($subject);

    header("Location: $url");
}else{
    $subject = "Failed to create account for email $email with cid=$contact_id and uid=$user_id";
    mail($mail_to,$subject,$subject,"From: $from\n");
    log_entry($subject);
    $url .= "?email=false";
    header("Location: $url");
}

?>
