<?php

// Get the URL that called this script
$redirect_url = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : "https://$_SERVER[HTTP_HOST]";

// Check if the user is authenticated
if (!isset($_COOKIE['remote-user'])) {
    // Handle Auth
    $require_auth = 'Yes';
    require_once 'auth.php';
}
?>