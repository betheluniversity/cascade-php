<?php

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

    $email = 'zztop@gmail.com';
    $search_email = '{' . $email . '}';
    echo "<pre>";
        $response = $mySforceConnection->search("find $search_email in email fields returning contact(email, firstname, lastname, id)");
        $records = $response->{'searchRecords'};
        print_r($records);
        echo "------";
        $response = $mySforceConnection->search("find $search_email in email fields returning user(email, firstname, lastname, id)");
        $records = $response->{'searchRecords'};
        print_r($records);

////Id of the User.
//$id = "005L0000001GZMsIAO";
////Get the corresponding UserLogin ID
//$response = $mySforceConnection->query("SELECT Id, IsFrozen FROM UserLogin WHERE UserId = '$id'");
//$frozenId = $response->{records}[0]->Id;
////Update to set frozen to false (0)
//$sObject1 = new stdclass();
//$sObject1->Id = $frozenId;
//$sObject1->IsFrozen = 0;
////commit the udpate
//$response = $mySforceConnection->update(array ($sObject1), 'UserLogin');
echo "</pre>";
?>