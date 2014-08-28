<?php

echo "<pre>";
// SOAP_CLIENT_BASEDIR - folder that contains the PHP Toolkit and your WSDL
// $USERNAME - variable that contains your Salesforce.com username (must be in the form of an email)
// $PASSWORD - variable that contains your Salesforce.ocm password
define("SOAP_CLIENT_BASEDIR", "toolkit/soapclient");
require_once (SOAP_CLIENT_BASEDIR.'/SforceEnterpriseClient.php');
require_once (SOAP_CLIENT_BASEDIR.'/SforceHeaderOptions.php');
require_once ('userAuth.php');
require_once ('userAuth.php');
try {
    $mySforceConnection = new SforceEnterpriseClient();
    $mySoapClient = $mySforceConnection->createConnection(SOAP_CLIENT_BASEDIR.'/enterprise.wsdl.xml');
    $mylogin = $mySforceConnection->login($USERNAME, $PASSWORD);

    $email = $_POST["email"];
    $first = $_POST["first_name"];
    $last = $_POST["last_name"];
    $password = $_POST["password"];
    $cpassword = $_POST["c_password"];

    if( $password != $cpassword)
    {
        session_start();
        $_SESSION['error_msg'] = "<p>Please make sure that the passwords entered are identical, and try again.</p>";
        session_write_close();
        header( 'Location: http://staging.bethel.edu/admissions/apply/' ) ;
        exit;
    }

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

        //Check if the account is frozen.
        $id = $records[0]->{'Id'};
        $userAccount = true;

        $response = $mySforceConnection->query("SELECT Id, IsFrozen FROM UserLogin WHERE UserId = '$id'");
        $frozenId = $response->{records}[0]->IsFrozen;
        $sObject->IsFrozen = 0;

        //commit the update
        $response = $mySforceConnection->update(array ($sObject), 'UserLogin');


        if( $frozenId == 0 )
            $is_frozen = false;
        else
            $is_frozen = true;

        $sObject = new stdclass();
        $sObject->userId = $id;
        $sObject->password = $password;

        // If it is not frozen, and there is already a user account. Then tell them to go in directly.
        if( !$is_frozen )
        {
            // send them back to the same page with an error message
            session_start();
            $_SESSION['error_msg'] = "<p>There is already an account with that email. Try logging in <a href='https://auth.xp.bethel.edu/auth/sf-portal-login.cgi'>here</a>.</p><p> Otherwise, try again or use a different email.</p>";
            session_write_close();

            header( 'Location: http://staging.bethel.edu/admissions/apply/' ) ;
            exit;
        }
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
    }

} catch (Exception $e) {
    echo $mySforceConnection->getLastRequest();
    echo $e->faultstring;
}

####################################################################
## CAS account creation.
####################################################################

if( $is_frozen)
    $reset = true;
else
    $reset = false;


$credentials = json_encode(array(
    "auth" => array(
        "username" => $BETHELAUTHUSERNAME,
        "passwd" => $BETHELAUTHPASSWD,
    ),
    "user" => array(
        "email" => $email,
        "passwd" => $password,
        "reset" => $reset
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


// Format the returned value.
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

print_r($fullArray);
exit;

// new Account or is frozen -- auto log in
if( $fullArray["status"] == "success" || $is_frozen)
{
    $_POST['username'] = $email;
    $_POST['password'] = $password;
    $url = 'https://auth.xp.bethel.edu/cas/login?service=https://auth.xp.bethel.edu/auth/sf-portal-login.cgi';

    header( 'Location: ' . $url ) ;
    exit;
}
else
{
    // Stay on the same page, since errors came up.
    $error_msg = "<p>We are sorry, but your register attempt has failed.</p>";
    if( !array_key_exists( "code", $fullArray) )
    {
        $error_msg .= "<p>Please try again later</p>";
    }
    else
    {
        if( $fullArray["code"] == 68 )
        {
            $error_msg .= "<p>There is already an account with that email. Try logging in <a href='https://auth.xp.bethel.edu/auth/sf-portal-login.cgi'>here</a>.</p><p> Otherwise, try again or use a different email.</p>";
        }
        elseif( $fullArray["code"] == 80)
        {
            $error_msg .= "<p>A password must be greater than 8 characters, include 1+ number, and include 1+ symbol.</p>";
        }
        else
        {
            $error_msg .= "<p>Please try again.</p><p>A password must be greater than 8 characters, include 1+ number, and include 1+ symbol.</p>";
        }
    }

    // try to log in again.

    session_start();
    $_SESSION['error_msg'] = $error_msg;
    session_write_close();

    header( 'Location: http://staging.bethel.edu/admissions/apply/' ) ;
    exit;
}












//####################################################################
//// Caleb's Send reset password email.
//// hook this up to a button or something.
//// Also, I don't know if the other end (after the email) works.
//####################################################################
//
//// Returns a random character string to store in the DB.
//function get_random_string($length){
//    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
//    $string = "";
//    for( $i = 0; $i < $length; $i++)
//        $string .= $characters[rand(1, $length)];
//
//    return $string;
//}
//
//// Also store in the DB here.
//function password_reset_option($email){
//    $randomString = get_random_string(32);
//
//    // Create connection
//    $con = mysqli_connect("localhost", "root", "", "salesforce");
//
//    if( !$con)
//    {
//        die('error');
//    }
//
//    $date = date("Y-m-d H:i:s", time());
////    echo $date;
//    $query = 'INSERT INTO forgot_password (UserID, `Key`, expDate) VALUES ("'.$email.'", "'.$randomString.'", "'.$date.'" )';
//    if(!mysqli_query($con,$query))
//        die('Error: ' . mysqli_error($con));
//
//    mysqli_close($con);
//
//
//    echo "<br />sent email";
//    send_email($randomString, $email);
//}
//
//function send_email($randomString, $email){
//    $to = $email;
//    $from = $email;
//    $body = '<html>
//         <head>
//         </head>
//         <body>
//            <p>Go to <a href="https://staging.bethel.edu/code/salesforce/php/password-reset-template.php?id=' . $randomString . '">this</a> link to reset your password.</a></p>
//         </body>
//        </html>';
//
//    $headers  = 'MIME-Version: 1.0' . "\r\n";
//    $headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
//
//// Modify this name to whatever you want to display.
//    $headers .= 'From: webmaster@example.com';
//
//// to, subject, message, headers
//    mail( $to, $from, $body, $headers);
//}

//$email = "ces55739@bethel.edu";
//password_reset_option($email);

echo "</pre>";

?>