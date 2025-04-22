<?php

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
// Unset all session values
$_SESSION = array();

// Save the desired redirect URL after logout
$redirect = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : "https://$_SERVER[HTTP_HOST]";

// Remove cookies by setting their expiration time to a past date
if (isset($_COOKIE['remote-user'])) {
    setcookie('remote-user', '', time() - 3600, '/');
}

if (isset($_COOKIE['cal-user'])) {
    setcookie('cal-user', '', time() - 3600, '/');
}

// Destroy the session
session_destroy();

// Redirect to the Microsoft logout endpoint
$logoutUrl = 'https://login.microsoftonline.com/common/oauth2/v2.0/logout?' . http_build_query([
    'post_logout_redirect_uri' => $redirect,
]);

header('Location: ' . $logoutUrl);
?>