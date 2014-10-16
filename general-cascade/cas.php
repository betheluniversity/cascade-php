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

