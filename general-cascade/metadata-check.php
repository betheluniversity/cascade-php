<?php

include_once $_SERVER["DOCUMENT_ROOT"] . "/code/general-cascade/macros.php";
require $_SERVER["DOCUMENT_ROOT"] . '/code/vendor/autoload.php';


$staging = strstr(getcwd(), "staging/public");
$soda = strstr(getcwd(), "soda");



if( $staging ){

    if ($cms_url){
        $testing = "<a href='$cms_url' style='color: white; text-decoration: underline;'>TESTING</a>";
    }else{
        $testing = "TESTING";
    }

    echo "<div style='text-align:center; background:tomato;color:#fff;font-weight:500;padding:.7em;'>This page is a $testing version.</div>";
}

if ($cms_url) {
    echo '<div id="cms_url" style="display:none">';
    echo $cms_url;
    echo '</div>';
}

if ($require_auth == "Yes" || $check_auth == "Yes"){
    header("Cache-Control: no-cache, must-revalidate");
    header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
    include_once 'cas.php';
}else{
    header("Cache-Control: public, must-revalidate, max-age=86400");
}

