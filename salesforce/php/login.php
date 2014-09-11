<?php
/**
 * Created by PhpStorm.
 * User: ejc84332
 * Date: 7/31/14
 * Time: 3:07 PM
 */

define("SOAP_CLIENT_BASEDIR", "toolkit/soapclient");
require_once (SOAP_CLIENT_BASEDIR.'/SforceEnterpriseClient.php');
require_once (SOAP_CLIENT_BASEDIR.'/SforceHeaderOptions.php');
require_once ('userAuth.php');

$mySforceConnection = new SforceEnterpriseClient();
$mySoapClient = $mySforceConnection->createConnection(SOAP_CLIENT_BASEDIR.'/enterprise.wsdl.xml');
$mylogin = $mySforceConnection->login($USERNAME, $PASSWORD);


echo "Successful login. This will be the landing page.";
//echo "login here -- allow user to login to CAS?";
