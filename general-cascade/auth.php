<?php

if (!isset($check_auth)) {
    $check_auth = "No";
}
if (!isset($require_auth)) {
    $require_auth = "No";
}

if (!isset($auth_type)) {
    $auth_type = "CAS";
}

if ( $auth_type == "Microsoft" ) {
    include_once 'msal.php';
    $_SESSION['post-login-redirect'] = "https://$_SERVER[HTTP_HOST]/" . $canonical_url;
    $redirectUri = "https://$_SERVER[HTTP_HOST]/code/general-cascade/msal.php";
    phpMSAL::setRedirectUri($redirectUri);
} else {
    include_once 'cas.php';
}

if( strpos($require_auth,"Yes") !== false ){
    ##set cache header
    header("Cache-Control: no-store, no-cache, must-revalidate");

    if ( $auth_type == "Microsoft" ) {
        $authenticated = phpMSAL::forceAuthentication();
        $remote_user = phpMSAL::getUsername();
    } else {
        $authenticated = phpCAS::forceAuthentication();
        $remote_user = phpCAS::getUser();
    }

    // If it is faculty/staff only, make sure they have a faculty/staff role - otherwise exit 403
    if( $require_auth == 'Yes - Only Faculty/Staff' && $remote_user ){
        if ( $auth_type == "Microsoft" ) {
            $groups = phpMSAL::getUserGroups();
            $roles = array();
            foreach ($groups as $group) {
                $roles[] = array('userRole' => $group);
            }
        } else {
            $url = "https://wsapi.bethel.edu/username/$remote_user/roles";
            $roles = fetchJSONFile($url, array(), $print=false, $method='GET');
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
    if ( $auth_type == "Microsoft" ) {
        $authenticated = phpMSAL::checkAuthentication();
        $remote_user = phpMSAL::getUsername();
    } else {
        $authenticated = phpCAS::checkAuthentication();
        $remote_user = phpCAS::getUser();
    }
}

if($authenticated){
    setcookie('remote-user', $remote_user);
}else{
    $remote_user = null;
}