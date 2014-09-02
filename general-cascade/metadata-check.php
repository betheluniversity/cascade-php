<?php

$staging = strstr(getcwd(), "staging/public");

if( $staging ){
    echo "<div style='text-align:center; background:tomato;color:#fff;font-weight:500;padding:.7em;'>This page is a TESTING version.</div>";
}

// https not workign on staging at the moment
if( $secure == "Yes" && !$staging){
    if(!isset($_SERVER['HTTP_X_FORWARDED_PROTO']) || $_SERVER['HTTP_X_FORWARDED_PROTO'] != "https")
    {
        $redirect = "https://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
        header("Location: $redirect");
    }
}

if ($require_auth == "Yes"){

}