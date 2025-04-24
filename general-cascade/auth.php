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

include_once 'msal.php';
$redirectUri = "https://$_SERVER[HTTP_HOST]/code/general-cascade/msal.php";
phpMSAL::setRedirectUri($redirectUri);

$remote_user = null;
if( strpos($require_auth,"Yes") !== false ){
    ##set cache header
    header("Cache-Control: no-store, no-cache, must-revalidate");

    $authenticated = phpMSAL::forceAuthentication($redirect_url);
    $remote_user = phpMSAL::getUsername();

    // If it is faculty/staff only, make sure they have a faculty/staff role - otherwise exit 403
    if( $require_auth == 'Yes - Only Faculty/Staff' && $remote_user ){
        $groups = phpMSAL::getUserGroups();
        $roles = array();
        foreach ($groups as $group) {
            $roles[] = array('userRole' => $group);
        }

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
    $authenticated = phpMSAL::checkAuthentication();
    $remote_user = phpMSAL::getUsername();
}

if ($authenticated) {
    setcookie('remote-user', $remote_user, 0, '/');
} else {
    // Force logout if not authenticated
    if (isset($_COOKIE['remote-user'])) {
        setcookie('remote-user', '', time() - 3600, '/');
        header("Location: https://$_SERVER[HTTP_HOST]/$redirect_url");
    }
}