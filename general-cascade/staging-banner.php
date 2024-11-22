<?php

$live_url = "https://www.bethel.edu"; // Example value

// Check if REQUEST_URI is set
$requestUri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '/';

// Render the Twig template
try {
    echo $twig->render('staging-banner.html', array(
        'staging' => $staging,
        'cms_url' => $cms_url,
        'page_path' => $requestUri,
        'liveURL' => rtrim("https://www.bethel.edu", '/') . '/' . ltrim($requestUri, '/')
    ));
} catch (Exception $e) {
    // Handle the error (log it, display a message, etc.)
    echo 'Error rendering template: ' . $e->getMessage();
}
?>