<?php

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

echo "<pre>";

// SOAP_CLIENT_BASEDIR - folder that contains the PHP Toolkit and your WSDL
// $USERNAME - variable that contains your Salesforce.com username (must be in the form of an email)
// $PASSWORD - variable that contains your Salesforce.ocm password
define("SOAP_CLIENT_BASEDIR", "toolkit/soapclient");
require_once (SOAP_CLIENT_BASEDIR.'/SforceEnterpriseClient.php');
require_once (SOAP_CLIENT_BASEDIR.'/SforceHeaderOptions.php');
require_once ('userAuth.php');
    $mySforceConnection = new SforceEnterpriseClient();
    $mySoapClient = $mySforceConnection->createConnection(SOAP_CLIENT_BASEDIR.'/enterprise.wsdl.xml');
    $mylogin = $mySforceConnection->login($USERNAME, $PASSWORD);
    $email = "a+b@gmail.com";

    $email = escapeEmail($email);

//    $sObject = new stdclass();
//    $sObject->FirstName = "z";
//    $sObject->LastName = "z";
//    $sObject->Email = "a+b@gmail.com";
//    $createResponse = $mySforceConnection->create(array($sObject), 'Contact');
//    print_r($createResponse);
//    $contact_id = $createResponse[0]->id;

    $search_email = '{' . $email . '}';
    // search for a Contact with this email?
    $response = $mySforceConnection->search("find $search_email in email fields returning contact(email, firstname, lastname, id)");
    $records = $response->{'searchRecords'};
    print_r($records);
echo "</pre>";
?>