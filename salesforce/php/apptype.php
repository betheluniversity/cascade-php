<?php
/**
 * Created by PhpStorm.
 * User: ejc84332
 * Date: 5/21/16
 * Time: 2:25 PM
 */
ini_set("soap.wsdl_cache_enabled", "0");

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

function describeObject($object){

}

function getObjectValues($object){
    global $mySforceConnection;
    $obj = $mySforceConnection->describeSObject($object);
    $a = array();
    foreach($obj->fields as $field){
        array_push($a, $field->name);
    }
    return implode($a, ',');
}

function LookupCatalog($client){

    $map = Array();
    $query = "SELECT Id, Name, EnrollmentrxRx__Description__c from EnrollmentrxRx__Program_Catalog__c";

    $response = $client->query($query);

    foreach ($response->records as $record) {
        $object = get_object_vars($record);
        $name = $object['Name'];
        $id = $object['Id'];
        if($name != 'Test'){
            $map[$id] = $name;
        }
    }
    return $map;
};


function LookupProgram($client, $schools)
{
    $json = array();

    foreach ($schools as $Id => $school) {
        $json[$school] = Array();
    }
    $obj = 'EnrollmentrxRx__Program_Offered__c';
    $fields = getObjectValues($obj);
    $query = "SELECT $fields from $obj";
    $response = $client->query($query);

    foreach ($response->records as $record) {
        $object = get_object_vars($record);
        $name = $object['Name'];
        $active = $object['Active_on_Application__c'];

        if ($active) {
            $catalog = $object['EnrollmentrxRx__Program_Catalog__c'];
            if ($name != 'Test') {
                // Catalog is an ID, need name
                $school = $schools[$catalog];
                if ($school != "Seminary") {
                    $ld = LocationDelivery($object);
                    array_push($json[$school], array('object' => $object, 'delivery' => $ld));
//                    return $json;
                }
            }
        }
    }
    return $json;
}

function TermNameFromId($id){
    // I think these are all unique, so return first
    global $mySforceConnection;
    $query = "SELECT Id, Name from EnrollmentrxRx__Term__c where Id='$id'";
    $response = $mySforceConnection->query($query);
    $records = $response->records;
    $object = get_object_vars($records[0]);
    if($object['Id'] == $id){
        return $object['Name'];
    }
}

function LookupCohortByMajor($major){
    global $mySforceConnection;
    $obj ='Cohort__c';
    $fields = getObjectValues($obj);
    $query = "SELECT $fields from $obj where Major_code__c ='$major'";
    $response = $mySforceConnection->query($query);
    $terms = array();
    foreach ($response->records as $record) {
        $object = get_object_vars($record);
        $campus = $object['Campus_Code__c'];
        $term_id = $object['Term__c'];
        $format = $object['Format__c'];
        $term = TermNameFromId($term_id);
//        if($term && $campus && $format){
        array_push($terms, array("campus" => $campus, "term" => $term, "format"=>$format));
//        }
    }
    return $terms;

}

function LocationDelivery($program){
    $major = $object['Major__c'];
    $cohort_delivery = LookupCohortByMajor($major);
    //$object['delivery'] = $cohort_delivery;

    return $cohort_delivery;
}

function LoadTerm($client){

    $obj ='EnrollmentrxRx__Term__c';
    $fields = getObjectValues($obj);
    $query = "SELECT $fields from $obj";

    $response = $client->query($query);
    foreach ($response->records as $record) {

        $object = get_object_vars($record);
        //exit(0);
//        $name = $object['Name'];
//        $el = $object['Term_eligibility_by_program__c'];
        //echo "name: $name --- Term_eligibility_by_program: $el";
        echo "</br>";
    }
}


echo "<pre>";
//
////
$schools = LookupCatalog($mySforceConnection);
$json = LookupProgram($mySforceConnection, $schools);
//$ld = LocationDelivery($json);


print_r($json);

//LoadTerm($mySforceConnection);
////print_r($json);
//print_r($json);
//print_r($json);
echo "</pre>";


//header('Content-Type: application/json');
//header('Access-Control-Allow-Origin: https://bethel.fluidreview.com', false);
//echo json_encode($json);

//foreach($json as $school => $programs){
//    echo "---------------------------";
//    echo "<br/>";
//    echo $school;
//    echo "<br/>";
//    echo "---------------------------";
//    echo "<br/>";
//    echo "<br/>";
//    foreach($programs as $key => $name) {
//        echo "$name";
//        echo "<br/>";
//    }
//}
