<?php

// Get the URL that called this script
$redirect = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : "https://$_SERVER[HTTP_HOST]";

// Check if the user is authenticated
if (!isset($_COOKIE['remote-user'])) {
    // Handle Auth
    $require_auth = 'Yes';
    $canonical_url = $redirect;
    require_once 'auth.php';
}
?>