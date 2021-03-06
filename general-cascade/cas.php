<?php
/**
 * Created by PhpStorm.
 * User: ejc84332
 * Date: 10/6/14
 * Time: 9:47 AM
 */

require_once 'cas_config.php';
require_once $phpcas_path . '/CAS.php';
include_once $_SERVER["DOCUMENT_ROOT"] . "/code/wufoo/embed_preload.php";

//phpCAS::setDebug();
// Initialize phpCAS
phpCAS::client(CAS_VERSION_3_0, $cas_host, $cas_port, $cas_context);
phpCAS::setServerServiceValidateURL("https://auth-prod.bethel.edu/cas/serviceValidate");

// We need to set the service URL ourselves because of Varnish. The request is technically port 80 here,
// so phpCAS sents it to auth-prod.bethel.edu/cas with a service url of https://www.bethel.edu:80, which is not
//authorized to use CAS. Build the URL ourselves without a port and call setFixedServiceURL

$final_url = 'https://' . $_SERVER['SERVER_NAME'];

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
phpCAS::setCasServerCACert("/opt/webapps_certificates/" . $_SERVER['SERVER_NAME'] . "/DigiCertCA.crt");

if( strpos($require_auth,"Yes") !== false ){
    phpCAS::forceAuthentication();
    ##set cache header
    header("Cache-Control: no-store, no-cache, must-revalidate");
    ##set remove user variable
    $remote_user = phpCAS::getUser();
    setcookie('remote-user', $remote_user);

    // If it is faculty/staff only, make sure they have a faculty/staff role - otherwise exit 403
    if( $require_auth == 'Yes - Only Faculty/Staff' && $remote_user ){
        $url = "https://wsapi.bethel.edu/username/$remote_user/roles";
        $roles = fetchJSONFile($url, array(), $print=false, $method='GET');

        $has_faculty_or_staff_role = false;
        foreach( $roles as $role ){
            if( strpos($role['userRole'], 'STAFF') !== false or strpos($role['userRole'], 'FACULTY') !== false ) {
                $has_faculty_or_staff_role = true;
                break;
            }
        }
        if( !$has_faculty_or_staff_role){
            header("HTTP/1.0 403 Permission Denied", true, 403);
            require $_SERVER["DOCUMENT_ROOT"] . '/code/vendor/autoload.php';
            include($_SERVER["DOCUMENT_ROOT"] . '/_error/403.php');
            exit(403);
        }
    }
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


