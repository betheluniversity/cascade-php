<?php

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($check_auth)) {
    $check_auth = "No";
}
if (!isset($require_auth)) {
    $require_auth = "No";
}

if (!isset($redirect_url)) {
    $redirect_url = '/';
}

include_once 'cas.php';

$remote_user = null;
if( strpos($require_auth,"Yes") !== false ){
    ##set cache header
    header("Cache-Control: no-store, no-cache, must-revalidate");

    $authenticated = phpCAS::forceAuthentication();
    if($authenticated){
        $remote_user = phpCAS::getUser();
    }

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
    $authenticated = phpCAS::checkAuthentication();
    if($authenticated){
        $remote_user = phpCAS::getUser();
    }
}

if ($authenticated) {
    setcookie('remote-user', $remote_user, 0, '/');
}