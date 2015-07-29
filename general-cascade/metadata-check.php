<?php
$staging = strstr(getcwd(), "staging/public");
$soda = strstr(getcwd(), "soda");

if ($require_auth == "Yes" || $check_auth == "Yes"){
    header("Cache-Control: no-cache, must-revalidate");
    header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
    include_once 'cas.php';
}else{
    header("Cache-Control: public, must-revalidate, max-age=86400");
}

include_once $_SERVER["DOCUMENT_ROOT"] . "/code/general-cascade/macros.php";
require $_SERVER["DOCUMENT_ROOT"] . '/code/vendor/autoload.php';






$twig = makeTwigEnviron('/code/general-cascade/twig');

//echo $twig->render('metadata-check.html', array(
//    'staging' => $staging,
//    'cms_url' => $cms_url));



