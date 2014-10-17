<?php
/**
 * Created by PhpStorm.
 * User: ejc84332
 * Date: 10/6/14
 * Time: 9:47 AM
 */


require_once 'cas_config.php';
require_once $phpcas_path . '/CAS.php';

//phpCAS::setDebug('/var/www/staging/public/_testing/jmo/phpCAS.txt');
// Initialize phpCAS
phpCAS::client(CAS_VERSION_3_0, $cas_host, $cas_port, $cas_context);
phpCAS::setServerServiceValidateURL("https://auth.bethel.edu/cas/serviceValidate");

if($staging){
    $final_url = 'https://staging.bethel.edu';
}else{
    $final_url = 'https://www.bethel.edu';
}

$request_uri	= explode('?', $_SERVER['REQUEST_URI'], 2);
$final_url		.= $request_uri[0];

if (isset($request_uri[1]) && $request_uri[1]) {
    $query_string= _removeParameterFromQueryString('ticket', $request_uri[1]);
    // If the query string still has anything left,
    // append it to the final URI
    if ($query_string !== '') {
        $final_url	.= "?$query_string";
    }
}

phpCAS::setServerLoginURL('https://auth.bethel.edu/cas/login?service=' . $prefix . $_SERVER['REQUEST_URI']);
echo 'using ' . $final_url;
phpCAS::setFixedServiceURL($final_url);
//phpCAS::setNoCasServerValidation();
phpCAS::setCasServerCACert("/etc/pki/tls/certs/gd_bundle.crt");


if($require_auth == "Yes"){
    phpCAS::forceAuthentication();
    ##set cache header
    header("Cache-Control: no-store, no-cache, must-revalidate");
    ##set remove user variable
    $remote_user = phpCAS::getUser();
}else if($check_auth == "Yes"){
    if(phpCAS::checkAuthentication()){
        $remote_user = phpCAS::getUser();
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

