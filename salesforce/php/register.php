<?php

echo "<pre>";

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
        $id = $records[0]->id;

    }else{ // change back to else after testing

        //Create one and save the id
        $sObject = new stdclass();
        $sObject->FirstName = $first;
        $sObject->LastName = $last;
        $sObject->Email = $email;
        $createResponse = $mySforceConnection->create(array($sObject), 'Contact');

        $id = $createResponse[0]->id;
    }

    // test for existing user here
    $response = $mySforceConnection->search("find $search_email in email fields returning user(email, firstname, lastname, id)");
    $records = $response->{'searchRecords'};
    $has_user = sizeof($records);
    if ($has_user > 0){
        //Contact already has a user, go to account recovery page. (Or login?)
        $id = $records[0]->{'Id'};
        $sObject = new stdclass();
        $sObject->userId = $id;
        $sObject->password = $password;

        $userAccount = true;
    }
    else{
        $userAccount = false;
    }

    // Create user account based on contact info.
    if (!$userAccount){

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
        $sObject->ProfileId = "00eL0000000QUJb"; // profile id?
        $sObject->ContactId = $id;
        $sObject->LanguageLocaleKey = "en_US";
        $createResponse = $mySforceConnection->create(array($sObject), 'User');

        //now set their password
//        $sObject = new stdclass();
//        $sObject->userId = $uid;
//        $sObject->password = $password;
//        $setPasswordResponse = $mySforceConnection->setPassword($uid, $password);
    }

} catch (Exception $e) {
    echo $mySforceConnection->getLastRequest();
    echo $e->faultstring;
}

####################################################################
## CAS account creation.
####################################################################

$credentials = json_encode(array(
                    "auth" => array(
                        "username" => $BETHELAUTHUSERNAME,
                        "passwd" => $BETHELAUTHPASSWD,
                    ),
                    "user" => array(
                        "email" => $email,
                        "passwd" => $password,
                        "reset" => "true"
                    )
                ));
$url = 'https://auth.xp.bethel.edu/auth/email-account-management.cgi';
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
echo "--------------------------------<br />";
print_r($fullArray);
echo "--------------------------------<br />";

if( $fullArray["status"] == "success")
{
    // Redirect to the login page.
    header( 'Location: http://staging.bethel.edu/testing/salesforce-go-to-button-test?register_attempt=true' ) ;
    echo "success";
}
else
{
    // Stay on the same page, since errors came up.
    $error_msg = "We are sorry, but your login attempt has failed.<br />";
    if( !array_key_exists( "code", $fullArray) )
    {
        $error_msg .= "Try again later";
    }
    else
    {
        if( $fullArray["code"] == 68 )
        {
            // Redirect to the login page.
            header( 'Location: http://staging.bethel.edu/testing/salesforce-go-to-button-test?register_attempt=duplicate' ) ;
            exit;
        }
        $error_msg .= "Error Code - " . $fullArray["code"] . "<br />" . $fullArray["message"];
    }

    // try to log in again.

    session_start();
    $_SESSION['email'] = $email;
    $_SESSION['first'] = $first;
    $_SESSION['last'] = $last;
    $_SESSION['error_msg'] = $error_msg;
    session_write_close();

    header( 'Location: http://staging.bethel.edu/testing/salesforce-login-test' ) ;
}





####################################################################
// Caleb's Send reset password email.
// hook this up to a button or something.
// Also, I don't know if the other end (after the email) works.
####################################################################

// Returns a random character string to store in the DB.
function get_random_string($length){
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $string = "";
    for( $i = 0; $i < $length; $i++)
        $string .= $characters[rand(1, $length)];

    return $string;
}

// Also store in the DB here.
function password_reset_option($email){
    $randomString = get_random_string(32);

    // Create connection
    $con = mysqli_connect("localhost", "root", "", "salesforce");

    if( !$con)
    {
        die('error');
    }

    $date = date("Y-m-d H:i:s", time());
//    echo $date;
    $query = 'INSERT INTO forgot_password (UserID, `Key`, expDate) VALUES ("'.$email.'", "'.$randomString.'", "'.$date.'" )';
    if(!mysqli_query($con,$query))
        die('Error: ' . mysqli_error($con));

    mysqli_close($con);


    echo "<br />sent email";
    send_email($randomString, $email);
}

function send_email($randomString, $email){
    $to = $email;
    $from = $email;
    $body = '<html>
         <head>
         </head>
         <body>
            <p>Go to <a href="https://staging.bethel.edu/code/salesforce/php/password-reset-template.php?id=' . $randomString . '">this</a> link to reset your password.</a></p>
         </body>
        </html>';

    $headers  = 'MIME-Version: 1.0' . "\r\n";
    $headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";

// Modify this name to whatever you want to display.
    $headers .= 'From: webmaster@example.com';

// to, subject, message, headers
    mail( $to, $from, $body, $headers);
}

//$email = "ces55739@bethel.edu";
//password_reset_option($email);

echo "</pre>";

?>