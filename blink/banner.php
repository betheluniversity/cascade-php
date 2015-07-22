<?php
/**
 * Created by PhpStorm.
 * User: ejc84332
 * Date: 7/14/15
 * Time: 12:07 PM
 */

/*

7-14-15: ejc84332.
Because of the LogJob vulnerability, and the old-age of the RHEL4 box blink is installed on,
Browers are denying access to Blink. The solution was to route Blink through Varnish/Pound and let
that handle the SSL security. A side effect of that was the CPIP connector broke in Blink and banner channels
stopped functioning.

To fix this, Varnish strips out the blink URL wrapper so we just have the SSB URL. However, some of the parameters are
double-encoded, and Varnish 2 does not have a decode function. By sending the URLs to this file, they are decoded once.
Sending them to the SSB SSO handler will decode them a second time and show the page like normal.


*/
$staging = strstr(getcwd(), "staging/public");
if($staging){
    $url = "https://banner.xp.bethel.edu/ssomanager/c/SSB?pkg=" . $_GET['url'];
}else{
    $url = "https://banner.bethel.edu/ssomanager/c/SSB?pkg=" . $_GET['url'];
}

// logging
$current_time = date("m/d/Y h:i:s");
error_log("$current_time 1) Blink channel to Banner url: " + $_GET['url']);
error_log("$current_time 2) Submitted url: $url");

header( 'Location: ' . $url ) ;