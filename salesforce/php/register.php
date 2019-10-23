<?php

ini_set("soap.wsdl_cache_enabled", "0");
session_start();

define("SOAP_CLIENT_BASEDIR", "toolkit/soapclient");
// Importing Remote Based Assets
require_once (SOAP_CLIENT_BASEDIR.'/SforceEnterpriseClient.php');
require_once (SOAP_CLIENT_BASEDIR.'/SforceHeaderOptions.php');

// load usernames and passwords, config variables
require_once ('userAuth.php');

//Creates a new salesForceConnection
$mySforceConnection = new SforceEnterpriseClient();
//Creates the Connection
$mySoapClient = $mySforceConnection->createConnection(SOAP_CLIENT_BASEDIR.'/enterprise.wsdl.xml');
//Logs in with the connection just created
$mylogin = $mySforceConnection->login($USERNAME, $PASSWORD);
// Creates Error Message
$errorMessage;

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

//Custom error_log call - Takes in a string
function log_entry($message){
    // Date format: [Mon Jan 1 12:12:00 2000], as well as then inserting the message,
    // and sets the path to the /code/salesforce/php/register.log
    error_log("--------------------------------------------------------------------------------------------------------------------" . "\n", 3, "/opt/php_logs/register.log");
    error_log('[' . date("D M j H:i:s Y",time()) . '] ' . $message . "\n", 3, "/opt/php_logs/register.log");
}

function search_for_user($email){
    global $mySforceConnection;
    // test for existing user
    $response = $mySforceConnection->query("SELECT Email, Id FROM User WHERE Username = '$email'");
    $records = $response->{'records'};
    return $records;
}

//Creates a new SalesForceUser
function create_new_user($first, $last, $email, $contact_id){
    global $mySforceConnection;
    global $PORTALUSERID;
    global $errorMessage;
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
    //Initiating the creation of the Salesforce User
    $responseGood = false;
    $exception = null;
    //Tries to create a user 5 times
    for($i = 0; $i < 5; $i++) {
        try {
            //Attempts to create a new user
            $createResponse = $mySforceConnection->create(array($sObject), 'User');
            //If an exception has not occurred the responseGood changes to true
            $responseGood = true;
            //Then breaks and does not go through the remaining loops
            break;
        } catch (Exception $e) {
            $exception = $e;
//            log_entry("failed to create user");
//            $subject = $e->getMessage();
//            log_entry($subject);
//            mail('web-development@bethel.edu', $subject, $subject, "From: $from\n");
//            return null;
        }
    }

    //If the loop goes through and does not pass with at least one good request, then it will throw the exception info
    if(!$responseGood){
        log_entry("failed to create user");
        $subject = $exception->getMessage();
        log_entry($subject);
        mail('web-development@bethel.edu', $subject, $subject, "From: $from\n");
        return null;
    }
    $output = print_r($createResponse,1);
    log_entry('create user : ' . $output);
    //Creates response
    $errorMessage = json_decode(json_encode($createResponse[0]), true);
    $user_id = $createResponse[0]->id;
    add_permission_set($user_id);
    return $user_id;

}

function add_permission_set($user_id){
    global $mySforceConnection;
    global $PERMISSIONSETID;
    // give permission set
    $sObject = new stdclass();
    $sObject->AssigneeId = $user_id;
    $sObject->PermissionSetId = $PERMISSIONSETID;
    return $mySforceConnection->create(array($sObject), 'PermissionSetAssignment');
}

//Setting Variables, as well as declaring the environment
$staging = strstr(getcwd(), "/staging");
$mail_to = "web-development@bethel.edu";
$mail_from = "salesforce-register@bethel.edu";
$subject = "salesforce register submission";
$message = "";

//prepare a URL for returing
if ($staging){
    $url = 'https://staging.bethel.edu/admissions/apply/';
}else{
    $url = 'https://www.bethel.edu/admissions/apply';
}

$email = $_POST["email"];
$email = strtolower($email);
$email = trim($email);

log_entry($email);
$first = $_POST["first"];
$last = $_POST["last"];

$search_email = escapeEmail($email);
$search_email = '{' . $search_email . '}';

function create_interaction($first, $last, $email){
    global $mySforceConnection;

    $utm_source = '';
    $utm_medium = '';
    $utm_content = '';
    $utm_campaign = '';

    if( $_COOKIE['utm_source'] )
        $utm_source = $_COOKIE['utm_source'];
    if( $_COOKIE['utm_medium'] )
        $utm_medium = $_COOKIE['utm_medium'];
    if( $_COOKIE['utm_content'] )
        $utm_content = $_COOKIE['utm_content'];
    if( $_COOKIE['utm_campaign'] )
        $utm_campaign = $_COOKIE['utm_campaign'];


    $sObject = new stdclass();
    $sObject->First_Name__c = $first;
    $sObject->Last_Name__c = $last;
    $sObject->Email__c = $email;
    $sObject->Interaction_Source__c = 'Account Register';
    $sObject->Lead_Source__c = 'Website';
    $sObject->Source_Detail__c = 'Application';
    $sObject->Marketing_Campaign__c = $utm_campaign;
    $sObject->Marketing_Source__c = ucwords(str_replace('_', ' ', $utm_source));
    $sObject->Marketing_Medium__c = ucwords(str_replace('_', ' ', $utm_medium));

    try{
        $createResponse = $mySforceConnection->create(array($sObject), 'Interaction__c');
    }catch(Exception $e){
        log_entry("failed to create interaction");
        $subject = $e->getMessage();
        log_entry($subject);
        // todo update before golive
        mail('e-jameson@bethel.edu',$subject,$subject,"From: $from\n");
        return null;
    }

    $interaction_id = $createResponse[0]->id;
    $output = print_r($createResponse,1);
    log_entry('interaction create : ' . $output);
    return $interaction_id;
}

function get_contact_id($interaction_id){
    global $mySforceConnection;
    // test for existing user
    $response = $mySforceConnection->query("SELECT Contact__c, Id FROM Interaction__c WHERE Id = '$interaction_id'");
    return $response->{'records'}[0]->Contact__c;
}

####################################################################
// START MAIN LOGIC
####################################################################

try {
    global $errorMessage;
    $interaction_id = create_interaction($first, $last, $email);
    $contact_id = get_contact_id($interaction_id);
    $user_records = search_for_user($email);

    // if user was found, make sure it has the right permission set
    // todo add query or check to see if user has this permission set yet
    if (sizeof($user_records) > 0){
        // todo Contact already has a user, send different email from auth system, or show an error?
        $user_id = $user_records[0]->Id;
        log_entry('found user_id: ' . $user_id);
        add_permission_set($user_id);
        log_entry('gave user permission set: ' . $user_id);
    }
    //If  user was not found, Create one
    else{
        log_entry('No user found. Creating...');
        // this also adds the permission set
        $user_id = create_new_user($first, $last, $email, $contact_id);
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

####################################################################
// SF Prep Done, start Auth work
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

//Changes the authenticating URL depending on the staging enviroment
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
    log_entry($json);
    $url .= "?email=false";
    header("Location: $url");
}

?>
