<?php

session_start();

// Check if the user is authenticated
if (!isset($_SERVER['OIDC_CLAIM_sub'])) {

    // Store the original request URI
    $request_uri = $_GET['request_uri'];
    $_SESSION['request_uri'] = $request_uri;

    // Handle Microsoft Auth
    $require_auth = 'Yes';
    $auth_type = 'Microsoft';
    $canonical_url = $request_uri;
    require_once 'auth.php';
    exit();
}

// User is authenticated, handle the original request
if (isset($_SESSION['request_uri'])) {
    $request_uri = $_SESSION['request_uri'];
    unset($_SESSION['request_uri']);
    header('Location: ' . $request_uri);
    exit();
}

// Default behavior if no original request URI is set
header('Location: https://www.bethel.edu');
exit();
?>