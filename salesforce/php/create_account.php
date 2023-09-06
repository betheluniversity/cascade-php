<?php

require $_SERVER["DOCUMENT_ROOT"] . '/code/vendor/autoload.php';

$email = isset($_POST["email"]) ? $_POST["email"] : '';
if ( $email ) {
    $data = process_form($_POST);

    $loader = new Twig_Loader_Filesystem($_SERVER["DOCUMENT_ROOT"] . '/code/salesforce/twig');
    $twig = new Twig_Environment($loader);
    echo $twig->render('create_account_form.html', array('data' => $data));
}

function get_account_form($data) {
    $form = autoCache("create_account_form", array($data), 300, "No");
    return $form;
}

function create_account_form($data) {

    $twig = makeTwigEnviron('/code/salesforce/twig');

    if ( $data["bca_username"] ) {
            $data += array(
                'message' => "Getting your Bethel Account...",
                'messageClass' => '',
                'fullname' => false,
                "buttonTitle" => "<img src='https://www.bethel.edu/cdn/images/load.gif' style='display: block; height: 48px; margin: 0 12px; padding: 10px;pointer-events: none;'/>",
                'nobutton' => true,
                'problems' => true
            );
    } else {
        $data += array(
            'message' => "To get started, let's see if you already have a Bethel Account.",
            'messageClass' => '',
            'fullname' => false,
            "buttonTitle" => 'Check Email',
            'problems' => true
        );
    }

    return $twig->render('create_account.html', array('data' => $data));
}

function process_form($data) {

    $data += array(
        'php_path' => $_SERVER['PHP_SELF']
    );

    $staging = strstr(getcwd(), "/staging");

    if ($staging){
        //$wsapi_url = 'https://c056-97-116-115-179.ngrok-free.app/salesforce/register';
        $wsapi_url = 'https://wsapi.xp.bethel.edu/salesforce/register';
    }else{
        $wsapi_url = 'https://wsapi.bethel.edu/salesforce/register';
    }

    $first = isset($data["first"]) ? $data["first"] : '';
    $last = isset($data["last"]) ? $data["last"] : '';
    $email = isset($data["email"]) ? $data["email"] : '';

    $redir = isset($data["redir"]) ? $data["redir"] : '';
    $programCode = isset($data["programCode"]) ? $data["programCode"] : '';
    $quickCreate = isset($data["quickCreate"]) ? $data["quickCreate"] : '';

    // prep UTM data
    $utm_source = '';
    $utm_medium = '';
    $utm_campaign = '';

    if ( $_COOKIE['utm_source'] ) {
        $utm_source = ucwords(str_replace('_', ' ', $_COOKIE['utm_source']));
    }
    if ( $_COOKIE['utm_medium'] ) {
        $utm_medium = ucwords(str_replace('_', ' ', $_COOKIE['utm_medium']));
    }
    if ( $_COOKIE['utm_campaign'] ) {
        $utm_campaign = $_COOKIE['utm_campaign'];
    }

    //if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    //    $data += array(
    //        'validateMessage' => 'Please enter a valid email address.'
    //    );
    //    return $twig->render('account_form.html', array('data' => $data));
    //}

    $allowed_params = isset($data["allowed_params"]) ? $data["allowed_params"] : '';
    $allowed_params = str_replace(' ', '', $allowed_params);
    $allowed_params = array_values(preg_split("/\,/", $allowed_params));

    $params = isset($data["params"]) ? $data["params"] : Array();
    foreach ($params as $key => $value) {
        if (!in_array($key, $allowed_params)) {
            unset($params[$key]);
        }
    }
    $data['params'] = $params;

    $login_url = isset($data["login_url"]) ? $data["login_url"] : '';
    if ($staging) {
        if (str_contains($login_url, 'www.bethel.edu')) {
            $login_url = str_replace('www.bethel.edu', 'staging.bethel.edu', $login_url);
        }
    }

    $query = '';
    if ($params) {
        $http_query = http_build_query($params);
        if ($http_query) {
            $query = '?' . $http_query;
        }
    }

    $iam_redirect = $login_url;
    $iam_redirect.= $query;
    
    $payload = array(
        "email" => $email,
        "first_name" => $first,
        "last_name" => $last,
        'utm_source' => $utm_source,
        'utm_medium' => $utm_medium,
        'utm_campaign' => $utm_campaign,
        'program_code' => $programCode,
        'quick_create' => $quickCreate,
        'redir' => $redir,
        'login_url' => $iam_redirect
    );
    
    $json_payload = json_encode($payload);
    $options = array(
        'http' => array(
            'header'  => "Content-type: application/json",
            'method'  => 'POST',
            'content' => $json_payload,
        ),
    );
    
    $context  = stream_context_create($options);
    
    // Here is the returned value
    $result = file_get_contents($wsapi_url, false, $context);
    $json = json_decode($result, true);

    $data['email'] = strtolower($email);

    if ($json['success'] == true) {

        $contact_id = isset($json["contact_id"]) ? $json["contact_id"] : '';
        if ($contact_id) {
            $params +=  array(
                'cid' => $contact_id
            );
        }

        if ( $json['account_recovery'] == true && $json['account']) {

            // Add cid to login url params if allowed
            if ( in_array('cid', $allowed_params) ) {
                $query = '';
                $http_query = http_build_query($params);
                if ($http_query) {
                    $query = '?' . $http_query;
                }
            }

            $auto_login = isset($data["auto_login"]) ? $data["auto_login"] : 'false';
            if ( $auto_login == 'true' ) {
                $url = $login_url . $query;
                echo '<script>window.top.location.href = "' . $url . '";</script>';

                $data += array(
                    'message' => "To get started, let's see if you already have a Bethel Account.",
                    'messageClass' => '',
                    'fullname' => false,
                    "buttonTitle" => 'Check Email',
                    'problems' => true,
                    'noinput' => false
                );
            } else {
                $name = $json['account']['first'];

                if ($json['account']['bethel'] == true){
                    $message = '<h3>Welcome back ' . $name . '!</h3>To continue, log in using your Bethel Community Account username and password.';
                    $data += array('bethel' => true);
                } else {
                    $message = '<h3>Welcome back ' . $name . '!</h3>To continue, log in using the email address below and your Bethel Account password.';
                }

                $data['bca_username'] = '';
                $data += array(
                    'message' => $message,
                    'messageClass' => '',
                    'buttonTitle' => 'Log In',
                    'login_url' => $login_url,
                    'php_path' => '',
                    'noinput' => true
                );
            }

        } else {

            $confirm_redirect = isset($data["confirm_redirect"]) ? $data["confirm_redirect"] : '';
            if ($staging) {
                if (str_contains($confirm_redirect, 'www.bethel.edu')) {
                    $confirm_redirect = str_replace('www.bethel.edu', 'staging.bethel.edu', $confirm_redirect);
                }
            }

            if ( $confirm_redirect ) {

                $confirm_redirect_params = isset($data["confirm_redirect_params"]) ? $data["confirm_redirect_params"] : '';
                $confirm_redirect_params = str_replace(' ', '', $confirm_redirect_params);
                $confirm_redirect_params = array_values(preg_split("/\,/", $confirm_redirect_params));

                // Add cid to confirm redirect params if allowed
                if ( in_array('cid', $confirm_redirect_params) ) {
                    $query = '';
                    $http_query = http_build_query($params);
                    if ($http_query) {
                        $query = '?' . $http_query;
                    }
                }

                $url = $confirm_redirect . $query;
                echo '<script>window.top.location.href = "' . $url . '";</script>';
                
                $data += array(
                    'message' => "To get started, let's see if you already have a Bethel Account.",
                    'messageClass' => '',
                    'fullname' => false,
                    "buttonTitle" => 'Check Email',
                    'problems' => true,
                    'noinput' => false
                );
            } else {
                $data['bca_username'] = '';

                $data += array(
                    'message' => "We've emailed you a link to create your Bethel Account and password.<br /><br />Please check your inbox within the next few minutes.<br />If you don't get the email, please check your spam folder, try again, then contact <a href='https://www.bethel.edu/its'>ITS</a>.<br /><br />After you create your account, you will be able to log in.",
                    'messageClass' => 'alert',
                    'buttonTitle' => 'Log In',
                    'login_url' => $login_url,
                    'php_path' => '',
                    'noinput' => true,
                    'tryagain' => true
                );
            }
        }
    } else {
        $data['bca_username'] = '';
        $data += array(
            'message' => "<h3>Create an Account</h3>Before we continue, let's create a Bethel Account.<br />Please enter your first and last name.",
            'messageClass' => '',
            'fullname' => true,
            'buttonTitle' => 'Create Account',
            'login_url' => $login_url,
            'php_path' => ''
        );
    }

    return $data;
}
