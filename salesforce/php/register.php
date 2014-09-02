<?php
$staging = strstr(getcwd(), "staging/public");

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
    $password = $_POST["password"];

    $search_email = '{' . $email . '}';
    // search for a Contact with this email?
    $response = $mySforceConnection->search("find $search_email in email fields returning contact(email, firstname, lastname, id)");
    $records = $response->{'searchRecords'};

    $has_contact = sizeof($records);
    if ($has_contact > 0){
        //We found it, get the id of the first (email is unique, so only one result)
        $contact_id = $records[0]->id;
    }else{
        //Create one and save the id
        $sObject = new stdclass();
        $sObject->FirstName = $first;
        $sObject->LastName = $last;
        $sObject->Email = $email;
        $createResponse = $mySforceConnection->create(array($sObject), 'Contact');

        $contact_id = $createResponse[0]->id;
    }

    // test for existing user
    $response = $mySforceConnection->search("find $search_email in email fields returning user(email, firstname, lastname, id)");
    $records = $response->{'searchRecords'};
    $has_user = sizeof($records);
    if ($has_user > 0){
        //Contact already has a user, go to account recovery page. (Or login?)
        $user_id = $records[0]->{'Id'};
    }
    else{
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
        $user_id = $createResponse[0]->id;
    }
} catch (Exception $e) {
    echo $mySforceConnection->getLastRequest();
    echo $e->faultstring;
}

// Check for frozen account.
try{
    $response = $mySforceConnection->query("SELECT Id, UserId, IsFrozen FROM UserLogin WHERE UserId = '$user_id'");
    $is_frozen = $response->{'records'}[0]->{'IsFrozen'};
    $frozen_id = $response->{'records'}[0]->{'Id'};
}catch (SoapFault $e){
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
## CAS account creation.
####################################################################
$credentials = array(
                    "auth" => array(
                        "username" => $BETHELAUTHUSERNAME,
                        "passwd" => $BETHELAUTHPASSWD,
                    ),
                    "user" => array(
                        "email" => $email,
                        "passwd" => $password,
                    )
                );

if ($is_frozen){
    //reset the password if it was frozen so the auth account is reset
    $credentials['user']['reset'] = 'true';
}
$credentials = json_encode($credentials);

if ($staging){
    $url = 'https://auth.xp.bethel.edu/auth/email-account-management.cgi';
}else{
    $url = 'https://auth.bethel.edu/auth/email-account-management.cgi';
}
$options = array(
    'http' => array(
        'header'  => "Content-type: application/json",
        'method'  => 'POST',
        'content' => $credentials,
    ),
);
$context  = stream_context_create($options);

// Here is the returned value
$result = file_get_contents($url, false, $context);

// remove the '{' and '}' from beginning/end of string.
$result = substr($result, 1, -1);
$array = explode(",", $result );

$fullArray = array();

foreach( $array as $option){
    $options = explode(":", $option);
    // remove the "'s
    $options[0] = substr($options[0], 1, -1);
    if( $options[1][0] == "\"")
        $options[1] = substr($options[1], 1, -1);

    $fullArray[$options[0]] = $options[1];
}

/* $fullArray is in the form:
    Array
    (
        ["status"] => error
        ["message"] => Duplicate account - alternate address
        ["code"] => 68
    )
*/
if( $fullArray["status"] == "success" || $is_frozen)
{
//     Redirect to the login page.
    session_start();
    $_SESSION['username'] = $email;
    $_SESSION['password'] = $password;
    session_write_close();
    header( 'Location: /code/salesforce/php/register-login.php' );
}
else
{
    // Stay on the same page, since errors came up.
    $error_msg = "We are sorry, but your register attempt has failed.<br />";
    if( !array_key_exists( "code", $fullArray) )
    {
        $error_msg .= "Please try again later";
    }
    else
    {
        if( $fullArray["code"] == 68 )
        {
            $error_msg .= "There is already an account with that email. Try logging in.<br /> Otherwise, try again or use a different email.";
            //header( 'Location: http://staging.bethel.edu/_testing/salesforce-go-to-button-test?register_attempt=duplicate' ) ;

            // https://auth.bethel.edu/cas/login?service=https://auth.xp.bethel.edu/auth/sf-portal-login.cgi
            // do a post request with ^^ url
        }
        elseif( $fullArray["code"] == 80)
        {
            $error_msg .= "A password must be greater than 8 characters, include 1+ number, and include 1+ symbol.";
        }
        else
        {
            $error_msg .= "Please try again.<br />A password must be greater than 8 characters, include 1+ number, and include 1+ symbol.";
        }
    }

    // try to log in again.
    session_start();
    $_SESSION['email'] = $email;
    $_SESSION['first'] = $first;
    $_SESSION['last'] = $last;
    $_SESSION['error_msg'] = $error_msg;
    session_write_close();
    if( $staging ){
        header( 'Location: http://staging.bethel.edu/admissions/apply' );
    }else{
        header( 'Location: https://apply.bethel.edu/' );
    }
}
?>