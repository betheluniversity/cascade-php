<?php
/**
 * Created by PhpStorm.
 * User: ces55739
 * Date: 8/22/14
 * Time: 8:48 AM
 */

stagingWarning();
checkSecureContent($secure);


function stagingWarning(){
    if( strstr(getcwd(), "staging/public") )
    {
        echo "<div style='text-align:center; background:tomato;color:#fff;font-weight:500;padding:.7em;'>This page is a TESTING version.</div>";
    }
}

function checkSecureContent($Secure){
    if( $Secure == "Yes")
    {
        if(!isset($_SERVER['HTTP_X_FORWARDED_PROTO']) || $_SERVER['HTTP_X_FORWARDED_PROTO'] != "https")
        {
            $redirect = "https://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
            header("Location: $redirect");
        }
    }
}




?>