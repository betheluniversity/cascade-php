<?php

$require_auth = 'Yes';
$auth_type = 'Microsoft';

if (isset($_GET['redirect'])) {
    $apache_redirect = $_GET['redirect'];
    require_once 'auth.php';
}

?>