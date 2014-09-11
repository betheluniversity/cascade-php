<?php

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

    $email = 'aaronwald@outlook.com';
    $first = "Aaron";
    $last = "Wald";
    $search_email = '{' . escapeEmail($email) . '}';

    $id = "005L0000001GtiU";
    try{
        $response = $mySforceConnection->search("find $search_email in email fields returning contact(email, firstname, lastname, id)");
        $records = $response->{'searchRecords'};
        $contact_id = $records[0]->Id;
        if (!$contact_id){
            $url .= "?cid=false";
            header("Location: $url");
        }

        //$response = $mySforceConnection->search("find $search_email in email fields returning user(email, firstname, lastname, id)");
        //$response = $mySforceConnection->query("SELECT Id, UserId, IsFrozen FROM UserLogin WHERE UserId = '$id'");
        //$is_frozen = $response->{'records'}[0]->{'IsFrozen'};
    }catch (SoapFault $e){
        //It fails if there is no record (never frozen)
        $is_frozen = false;
    }
    // test for existing user
    $response = $mySforceConnection->search("find $search_email in email fields returning user(email, firstname, lastname, id)");
    $records = $response->{'searchRecords'};
    $has_user = sizeof($records);
    if ($has_user > 0){
        //Contact already has a user, go to account recovery page. (Or login?)
        $user_id = $records[0]->Id;
    }
    else{
        $user_id = false;
    }
    // Create user account based on contact info.
    if (!$has_user){
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
        $sObject->ProfileId = $PORTALUSERID; // profile id?
        $sObject->ContactId = $contact_id;
        $sObject->LanguageLocaleKey = "en_US";
        $createResponse = $mySforceConnection->create(array($sObject), 'User');
        $user_id = $createResponse[0]->Id;
    }
    echo "<pre>";
    print_r($response);
    echo $contact_id;
    echo "\n";
    echo $user_id;
    if($is_frozen){
        echo 'frozen';
    }else{
        echo 'not frozen';
    }


//        $response = $mySforceConnection->search("find $search_email in email fields returning contact(email, firstname, lastname, id)");
//        $records = $response->{'searchRecords'};
//        print_r($records);
//        echo "------";
//        $response = $mySforceConnection->search("find $search_email in email fields returning user(email, firstname, lastname, id)");
//        $records = $response->{'searchRecords'};
//        print_r($records);

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
