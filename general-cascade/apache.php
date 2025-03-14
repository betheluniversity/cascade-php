<?php

$require_auth = 'Yes';
$auth_type = 'Microsoft';

if (isset($_GET['redirect'])) {
    $canonical_url = $_GET['redirect'];
    require_once 'auth.php';
}
header("Location: $canonical_url");
exit();
?>