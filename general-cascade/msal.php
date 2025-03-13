<?php

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once $_SERVER["DOCUMENT_ROOT"] . '/code/config.php';

class phpMSAL {
    private static $clientId;
    private static $clientSecret;
    private static $authority;
    private static $scopes;
    private static $redirectUri = '';

    public static function init($config) {
        self::$clientId = $config['MSAL_CLIENT_ID'];
        self::$clientSecret = $config['MSAL_CLIENT_SECRET'];
        self::$authority = $config['MSAL_AUTHORITY'];
        self::$scopes = $config['MSAL_SCOPES'];
    }

    public static function setRedirectUri($redirectUri) {
        self::$redirectUri = $redirectUri;
    }

    public static function forceAuthentication() {
        $token_valid = self::checkAuthentication();
        if (!$token_valid) {
            self::authenticate();
        } else {
            return true;
        }
    }

    public static function checkAuthentication() {
        $jwt = self::getDecodedToken();
        if (!$jwt) {
            return false;
        }

        $now = time();
        return $now < $jwt['exp'];
    }
    
    public static function getUsername() {
        $jwt = self::getDecodedToken();
        if (!$jwt) {
            return null;
        }
        $username = $jwt['upn'];
        $groups = self::getUserGroups();
        return $username;
    }

    public static function getUserGroups() {
        if (!isset($_SESSION['access_token'])) {
            return null;
        }

        $accessToken = $_SESSION['access_token'];
        $graphUrl = 'https://graph.microsoft.com/v1.0/me/memberOf';

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $graphUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $accessToken,
        ]);

        $response = curl_exec($ch);
        curl_close($ch);

        $group_list = [];
        $groups = json_decode($response, true);
        $groups = $groups['value'] ?? [];
        foreach ($groups as $group) {
            $display_name = $group['displayName'] ?? '';
            if ($display_name) {
                $group_list[] = $display_name;
            }
        }
        return $group_list;
    }

    private static function getDecodedToken() {
        if (!isset($_SESSION['access_token'])) {
            return null;
        }
        $accessToken = $_SESSION['access_token'];
        $tokenParts = explode('.', $accessToken);
        $tokenPayload = base64_decode($tokenParts[1]);
        $jwt = json_decode($tokenPayload, true);
        return $jwt;
    }

    private static function authenticate() {
        $authUrl = self::$authority . '/oauth2/v2.0/authorize?' . http_build_query([
            'client_id' => self::$clientId,
            'response_type' => 'code',
            'redirect_uri' => self::$redirectUri,
            'response_mode' => 'query',
            'scope' => implode(' ', self::$scopes),
            'state' => '12345',
        ]);

        header('Location: ' . $authUrl);
        exit();
    }

    public static function handleRedirect() {
        if (isset($_GET['code'])) {
            $code = $_GET['code'];

            $tokenUrl = self::$authority . '/oauth2/v2.0/token';
            $postData = [
                'client_id' => self::$clientId,
                'scope' => implode(' ', self::$scopes),
                'code' => $code,
                'redirect_uri' => self::$redirectUri,
                'grant_type' => 'authorization_code',
                'client_secret' => self::$clientSecret,
            ];

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $tokenUrl);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            $response = curl_exec($ch);
            curl_close($ch);

            $token = json_decode($response, true);
            $_SESSION['access_token'] = $token['access_token'];

            $postLoginRedirect = $_SESSION['post-login-redirect'] ?? '/';
            unset($_SESSION['post-login-redirect']);
            header('Location: ' . $postLoginRedirect);
            exit();
        }
    }
}

phpMSAL::init($config);

if (isset($_GET['code'])) {
    $redirectUri = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]/code/general-cascade/msal.php";
    phpMSAL::setRedirectUri($redirectUri);
    phpMSAL::handleRedirect();
}
?>