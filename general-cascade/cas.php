<?php
/**
 * Created by PhpStorm.
 * User: ejc84332
 * Date: 10/6/14
 * Time: 9:47 AM
 */

require_once 'cas_config.php';
require_once $phpcas_path . '/CAS.php';

//phpCAS::setDebug();
// Initialize phpCAS
phpCAS::client(CAS_VERSION_3_0, $cas_host, $cas_port, $cas_context);
phpCAS::setServerServiceValidateURL("https://auth.bethel.edu/cas/serviceValidate");

// We need to set the service URL ourselves because of Varnish. The request is technically port 80 here,
// so phpCAS sents it to auth.bethel.edu/cas with a service url of https://www.bethel.edu:80, which is not
//authorized to use CAS. Build the URL ourselves without a port and call setFixedServiceURL

if($staging){
    $final_url = 'https://staging.bethel.edu';
}else{
    $final_url = 'https://www.bethel.edu';
}

$request_uri	= explode('?', $_SERVER['REQUEST_URI'], 2);
$final_url		.= $request_uri[0];

//Clear out ?ticket="" from the URL, if it exists
if (isset($request_uri[1]) && $request_uri[1]) {
    $query_string= _removeParameterFromQueryString('ticket', $request_uri[1]);
    // If the query string still has anything left,
    // append it to the final URI
    if ($query_string !== '') {
        $final_url	.= "?$query_string";
    }
}
//Set the service URL and CA cert
phpCAS::setFixedServiceURL($final_url);
phpCAS::setCasServerCACert("/etc/pki/tls/certs/gd_bundle.crt");

if($require_auth == "Yes"){
    phpCAS::forceAuthentication();
    ##set cache header
    header("Cache-Control: no-store, no-cache, must-revalidate");
    ##set remove user variable
    $remote_user = phpCAS::getUser();
    setcookie('remote-user', $remote_user);
}else if($check_auth == "Yes"){
    if(phpCAS::checkAuthentication()){
        $remote_user = phpCAS::getUser();
        setcookie('remote-user', $remote_user);
    }else{
        $remote_user = null;
    }
}

function _removeParameterFromQueryString($parameterName, $queryString)
{
    $parameterName	= preg_quote($parameterName);
    return preg_replace(
        "/&$parameterName(=[^&]*)?|^$parameterName(=[^&]*)?&?/",
        '', $queryString
    );
}


