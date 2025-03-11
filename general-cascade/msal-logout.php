<?php
// filepath: /var/www/staging/code/general-cascade/msal-logout.php

session_start();

// Unset all session values
$_SESSION = array();

// Destroy the session
session_destroy();

// Redirect to the Microsoft logout endpoint
$logoutUrl = 'https://login.microsoftonline.com/common/oauth2/v2.0/logout?' . http_build_query([
    'post_logout_redirect_uri' => 'https://www.bethel.edu'
]);

header('Location: ' . $logoutUrl);
exit();
?>