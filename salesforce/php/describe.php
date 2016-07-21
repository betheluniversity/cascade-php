<?php
/**
 * Created by PhpStorm.
 * User: ejc84332
 * Date: 5/21/16
 * Time: 2:25 PM
 */


define("SOAP_CLIENT_BASEDIR", "toolkit/soapclient");
require_once (SOAP_CLIENT_BASEDIR.'/SforceEnterpriseClient.php');
require_once (SOAP_CLIENT_BASEDIR.'/SforceHeaderOptions.php');
require_once ('userAuth.php');
$mySforceConnection = new SforceEnterpriseClient();
$mySoapClient = $mySforceConnection->createConnection(SOAP_CLIENT_BASEDIR.'/enterprise.wsdl.xml');
$mylogin = $mySforceConnection->login($USERNAME, $PASSWORD);

/* 1. Application Type
         API: EnrollmentrxRx__Program_Catalog__c
         Object: Program Catalog
2. Academic Program ["Major" if Application Type = Undergrad]
         API: EnrollmentrxRx__Program_Offered__c
         Object: Program Offered
3. Delivery and Location
         —not sure—I texted Lisa
4. When are you planning to start? [Term]
    API: EnrollmentrxRx__Term__c
         Object: Term


There is 2 - one is an old code and never got deleted the other should have the correct values
I think the label is seminary location and delivery
This field is current for sem
The other for caps GS is on the cohort object and it is 2 fields

*/



function getObjectValues($object){
    global $mySforceConnection;
    $obj = $mySforceConnection->describeSObject($object);
    $a = array();
    foreach($obj->fields as $field){
        array_push($a, $field->name);
    }
    return implode($a, ',');
}


function describeObject($object){
    global $mySforceConnection;
    $fields = getObjectValues($object);
    $query = "SELECT $fields from $object";
    $options = new QueryOptions(2000);
    $mySforceConnection->setQueryOptions($options);
    $response = $mySforceConnection->query($query);

    if ($response->size > 0) {
        while (!$done) {
            foreach ($response->records as $record) {
                $object = get_object_vars($record);
                print_r($object);
            }
            if ($response->done != true) {
                try {
                    $response = $client->queryMore($response->queryLocator);
                } catch (Exception $e) {
                    print_r($client->getLastRequest());
                    echo $e->faultstring;
                }
            } else {
                $done = true;
            }
        }
    }
}




$object = $_GET['object'];
echo "<pre>";
echo "describing $object...";
echo "</br>";
describeObject($object);
echo "</pre>";