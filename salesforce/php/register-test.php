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

    $email = 'e-jameson+test-staging@bethel.edu';
    $first = 'test';
    $last = 'test';
    $password = '';

    $search_email = '{' . 'e-jameson+test-staging@bethel.edu' . '}';

    $contact_response= $mySforceConnection->query("SELECT Email, Id FROM Contact WHERE Email = '$email'");
    $contact_records = $contact_response->{'records'};

    $user_response= $mySforceConnection->query("SELECT Email, Id FROM User WHERE Email = '$email'");
    $user_records = $user_response->{'records'};

    $has_user = sizeof($contact_records);
    if ($has_user > 0){
        $user_id = $user_records[0]->Id;
        $deleteResult = $mySforceConnection->delete(array($user_id));
        print_r($deleteResult);

    }
    $has_contact = sizeof($records);
    if ($has_contact > 0){
        $contact_id = $contact_records[0]->Id;
        $deleteResult = $mySforceConnection->delete(array($contact_id));
        print_r($deleteResult);

    }






} catch (Exception $e) {
    echo $mySforceConnection->getLastRequest();
    echo $e->faultstring;
}
