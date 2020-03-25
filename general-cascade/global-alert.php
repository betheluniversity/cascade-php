<?php

$alert_file_path = $_SERVER["DOCUMENT_ROOT"] . "/_shared-content/www-mybethel-alerts/www-mybethel-alert.php";
if( file_exists($alert_file_path) ){
    $page = file_get_contents($alert_file_path);
    echo $page;
}