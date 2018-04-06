<?php


ini_set("soap.wsdl_cache_enabled", "0");
session_start();

// SOAP_CLIENT_BASEDIR - folder that contains the PHP Toolkit and your WSDL
// $USERNAME - variable that contains your Salesforce.com username (must be in the form of an email)
// $PASSWORD - variable that contains your Salesforce.ocm password
define("SOAP_CLIENT_BASEDIR", "toolkit/soapclient");
// Importing Remote Based Assets
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
    error_log("--------------------------------------------------------------------------------------------------------------------" . "\n", 3, getcwd()."/register.log");
    error_log('[' . date("D M j H:i:s Y",time()) . '] ' . $message . "\n", 3, getcwd()."/register.log");
}

//Searches for a Contact with this email
function search_for_contact($email){
    global $mySforceConnection;
    $response = $mySforceConnection->query("SELECT Email, Id FROM Contact WHERE Email = '$email'");
    $records = $response->{'records'};
    $output = print_r($response,1);
    log_entry('contact search : ' . $output);
    $has_contact = sizeof($records);
    return $records;
}

//Creates a new SalesForceContact
function create_new_contact($first, $last, $email){
    global $mySforceConnection;

    $sObject = new stdclass();
    $sObject->FirstName = $first;
    $sObject->LastName = $last;
    $sObject->Email = $email;
    
    try{
        $createResponse = $mySforceConnection->create(array($sObject), 'Contact');
    }catch(Exception $e){
        log_entry("failed to create contact");
        $subject = $e->getMessage();
        log_entry($subject);
        mail('web-development@bethel.edu',$subject,$subject,"From: $from\n");
        return null;
    }

    $contact_id = $createResponse[0]->id;
    $output = print_r($createResponse,1);
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
    return $user_id;
}

//Update contact referer site
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

//Add referer to the contact via SforceConnection
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

function create_sf_source($contact_id, $user_email, $mail_to, $mail_from){
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
    global $mySforceConnection;

    $responseGood = false;
    $user_app = false;
    try {
        $response = $mySforceConnection->query("SELECT EnrollmentrxRx__Active_Enrollment_Opportunity__c FROM Contact WHERE Id = '$contact_id'");
        $user_app = $response->{'records'}[0]->EnrollmentrxRx__Active_Enrollment_Opportunity__c;
    } catch (Exception $errorMessage) {
        $subject = "failed to create source for email: $user_email due to not getting active application";
        log_entry('failed to get active app when creating source');
        mail($mail_to,$subject,$errorMessage["errors"][0]["message"],"From: $mail_from\n");
    }

    if( $user_app ){
        $sObject = new stdclass();
        $sObject->Application_ID__c = $user_app;
        $sObject->Marketing_Type__c = $utm_campaign;
        $sObject->Marketing_Detail__c = ucwords(str_replace('_', ' ', $utm_source));
        $sObject->Medium__c = ucwords(str_replace('_', ' ', $utm_medium));
        $sObject->Source_Detail__c = 'Application';

        for($i = 0; $i < 1; $i++) {
            try {
                // Attempts to create a new user
                $createResponse = $mySforceConnection->create(array($sObject), 'Source__c');
                if( $createResponse[0]->{'success'} == 1 ){
                    // If an exception has not occurred the responseGood changes to true
                    $responseGood = true;
                    break;
                } else {
                    // make sure the response is set to false
                    $responseGood = false;
                }
            } catch (Exception $errorMessage) {
                $subject = "failed to create source for email: $user_email";
                log_entry('failed to create source');
                mail($mail_to, $subject, $errorMessage["errors"][0]["message"], "From: $mail_from\n");
                $responseGood = false;
                break;
            }
        }
    }

    return $responseGood;
}

//Setting Variables, as well as declaring the enviroment
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
    global $errorMessage;
    //Creates variable for contact id
    $contact_id = "";
    //Searches for contact with email
    $contact_records = search_for_contact($email);
    if (sizeof($contact_records) > 0){
        //We found it, get the id of the first (email is unique, so only one result)
        $contact_id = $contact_records[0]->Id;
        
    }else{
        //Did not find the Contact ID, thus creates contact
        log_entry('no contact found. Creating...');
        //Create one and save the id
        $contact_id = create_new_contact($first, $last, $email);
    }

    //If contact ID is not created (hit an expection in 'create_new_contact' method, returns null)
    if ($contact_id == ""){
        //Sends email, regarding the failing of contact ID Generation
        $url .= "?cid=false";
        $subject = "failed to find or create contact id for email $email";
        log_entry($subject);
        mail($mail_to,$subject,$errorMessage["errors"][0]["message"],"From: $from\n");
        header("Location: $url");
        exit;
    //If contact_id was created, logs the contact_id
    }else{
        log_entry("contact id is : " . $contact_id);
    }

    //Very similar block in comparison with block above
    //Creates variable to store user_id
    $user_id = "";
    //Searches for user based upon email
    $user_records = search_for_user($email);
    if (sizeof($user_records) > 0){
        //Contact already has a user, go to account recovery page. (Or login?)
        $user_id = $user_records[0]->Id;
        log_entry('found user_id');
    }
    //If  user was not found, Create one
    else{
        log_entry('No user found. Creating...');
        $user_id = create_new_user($first, $last, $email, $contact_id);
    }

    //If user is not created, it will return nothing
    if ($user_id == ""){
        //Sends an email notating the error
        $url .= "?uid=false";
        $subject = "failed to find or create user id for email $email with cid=$contact_id";
        mail($mail_to,$subject,$errorMessage["errors"][0]["message"],"From: $from\n");
        log_entry($subject);
        header("Location: $url");
        exit;
    //If it created the user ID without error, it logs it.
    }else{
        log_entry("user id is : " . $user_id);
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
## Create Source from cookies
####################################################################
create_sf_source($contact_id, $email, $mail_to, $mail_from);

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
    $url .= "?email=false";
    header("Location: $url");
}

?>
