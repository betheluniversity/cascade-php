<?php
session_start();
$staging = strstr(getcwd(), "staging/public");

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

//prepare a URL for returing
if ($staging){
    $url = 'http://staging.bethel.edu/admissions/apply/';
}else{
    $url = 'http://apply.bethel.edu/';
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

    $email = 'e-jameson%2Btest-referrer@bethel.edu';
    $email = 'ericjamesjameson@gmail.com';

    $response = $mySforceConnection->query("SELECT Email, Id, referrer_site__c FROM Contact WHERE Email = '$email'");
    $records = $response->{'records'};
    $contact_id = $records[0]->Id;

    $sObject = new stdclass();
    $sObject->Contact__c = $contact_id;
    $sObject->Referrer_Type__c = "RFI";
    $sObject->Referer_URL__c = $_SESSION['interesting_referer'];

    try {
        $createResponse = $mySforceConnection->create(array($sObject), 'Referrer__c');
    }catch (Exception $e){
        echo $e;
    }

$output = print_r($createResponse,1);
echo $output;
echo "</pre>";
?>
