<?php
// SOAP_CLIENT_BASEDIR - folder that contains the PHP Toolkit and your WSDL
// $USERNAME - variable that contains your Salesforce.com username (must be in the form of an email)
// $PASSWORD - variable that contains your Salesforce.ocm password
echo "1";
define("SOAP_CLIENT_BASEDIR", "toolkit/soapclient");
echo '2';
require_once (SOAP_CLIENT_BASEDIR.'/SforceEnterpriseClient.php');
echo '3';
require_once (SOAP_CLIENT_BASEDIR.'/SforceHeaderOptions.php');
echo '4';
require_once ('userAuth.php');
echo '5'
try {
    echo 'before';
    $mySforceConnection = new SforceEnterpriseClient();
    $mySoapClient = $mySforceConnection->createConnection(SOAP_CLIENT_BASEDIR.'/enterprise.wsdl.xml');
    $mylogin = $mySforceConnection->login($USERNAME, $PASSWORD);
    echo 'ksdfksdj';

    $email = 'jsmith4@gmail.com';
    $first = "John";
    $last = "Smith";
    $password = "password123";
    $search_email = '{' . $email . '}';
    // search for a Contact with this email?
    $response = $mySforceConnection->search("find $search_email in email fields returning contact(email, firstname, lastname, id)");
    $records = $response->{'searchRecords'};
    print_r($records);

    $has_contact = sizeof($records);
    if ($has_contact > 0){
        //We found it, get the id of the first (email is unique, so only one result)
        $id = $records[0]->{'Id'};

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
        try{
            $setPasswordResponse = $mySforceConnection->setPassword($id, $password);

        }catch(Exception $e){
            header( 'Location: /code/salesforce/php/login.php' ) ;
        }
        $userAccount = true;
    }
    else{
        $userAccount = false;
    }

    if (!$userAccount){
        echo "here?";
        $sObject = new stdclass();
        //now create a User. If there is a user they have an account already.
        //  Now create a User object tied to this Contact
        $sObject->ContactId = $id;
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
        $uid = $createResponse[0]->id;
        //now set their password
        $sObject = new stdclass();
        $sObject->userId = $uid;
        $sObject->password = $password;
        $setPasswordResponse = $mySforceConnection->setPassword($uid, $password);

    }

} catch (Exception $e) {
    echo $mySforceConnection->getLastRequest();
    echo $e->faultstring;
}
?>