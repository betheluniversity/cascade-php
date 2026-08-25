<?php

// Get the URL that called this script
$redirect_url = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : "https://$_SERVER[HTTP_HOST]";

// Always validate the server-side authentication session. The remote-user
// cookie can outlive that session and must not be used as proof of login.
$require_auth = 'Yes';
require_once 'auth.php';

// forceAuthentication() redirects unauthenticated users to Microsoft. If the
// user is already authenticated, return them to the page that initiated login.
header("Location: $redirect_url");
?>
