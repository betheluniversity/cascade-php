<?php

$staging = strstr(getcwd(), "staging/public");

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
    include_once 'cas.php';
}else{
    header("Cache-Control: public, must-revalidate, max-age=86400");
}