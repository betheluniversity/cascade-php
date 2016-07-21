<?php

// SOAP_CLIENT_BASEDIR - folder that contains the PHP Toolkit and your WSDL
// $USERNAME - variable that contains your Salesforce.com username (must be in the form of an email)
// $PASSWORD - variable that contains your Salesforce.ocm password
define("SOAP_CLIENT_BASEDIR", "toolkit/soapclient");
require_once (SOAP_CLIENT_BASEDIR.'/SforceEnterpriseClient.php');
require_once (SOAP_CLIENT_BASEDIR.'/SforceHeaderOptions.php');
require_once ('userAuth.php');

//session_start();
//$referer = $_SESSION["HTTP_REFERER"];
//$referer = explode('/', $referer);
// $referer : Array ( [0] => https: [1] => [2] => staging.bethel.edu [3] => _testing [4] => jmo [5] => basic ) Array
//$referer = $referer[3];


$mySforceConnection = new SforceEnterpriseClient();
$mySoapClient = $mySforceConnection->createConnection(SOAP_CLIENT_BASEDIR.'/enterprise.wsdl.xml');
$mylogin = $mySforceConnection->login($USERNAME, $PASSWORD);

function log_entry($message){
    error_log($message . "\n");
}


function SelectList ($client, $objectType, $fieldName, $selected = null) {
      $res = Array();
      $result = $client->describeSObject($objectType);
      foreach ($result->fields as $field) {
          if ($field->name == $fieldName) {
              foreach ($field->picklistValues as $value) {
                  $select = ($value->label == $selected) ? ' selected="selected" ':'';
                  $value = htmlspecialchars($value->label);
                  $res[$value] = $value;
              }
          }
      }
      return $res; 
 }

function LookupList($client, $objectType, $fieldName, $type){

      $res = Array();

      $query = "SELECT Id, Name, EnrollmentrxRx__High_School_City__c, EnrollmentrxRx__High_School_State__c, Record_Type__c from $fieldName";

  $options = new QueryOptions(2000);
  $client->setQueryOptions($options);

      $response = $client->query($query);

      if ($response->size > 0) {
        while (!$done) {
          foreach ($response->records as $record) {
             $object = get_object_vars($record);
             $obj_type = array_key_exists('Record_Type__c', $object) ? $object['Record_Type__c'] : '';

             if($obj_type == $type){
                 $name = $object['Name'];
                 $city = $object['EnrollmentrxRx__High_School_City__c'];
                 $state = $object['EnrollmentrxRx__High_School_State__c'];
                 //$res["$name, $city, $state"] = $name;
                 $res[$name] = "$name, $city, $state";
             }
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
  
   return $res;
}

$picklist = $_GET['picklist'];
$lookup = $_GET['lookup'];
$type = $_GET['type'];

if($picklist){
  $res = SelectList($mySforceConnection, 'contact', $picklist);
}elseif($lookup){
  $res = LookupList($mySforceConnection, 'contact', $lookup, $type);
}else{
  $res = "no params";
}

header('Content-Type: application/json');
echo json_encode($res);




