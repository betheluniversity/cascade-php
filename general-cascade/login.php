<?php

session_start();

// Check if the user is authenticated
if (!isset($_SERVER['OIDC_CLAIM_sub'])) {

    // Store the original request URI
    $redirect = $_GET['redirect'];
    $_SESSION['redirect'] = $redirect;

    // Handle Microsoft Auth
    $require_auth = 'Yes';
    $auth_type = 'Microsoft';
    $canonical_url = $redirect;
    require_once 'auth.php';
}

// User is authenticated, handle the original request
if (isset($_SESSION['redirect'])) {
    $redirect = $_SESSION['redirect'];
    unset($_SESSION['redirect']);
    header('Location: ' . $redirect);
}

// Default behavior if no original request URI is set
header('Location: https://www.bethel.edu');
?>