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
        'utm_source' => '',
        'utm_medium' => '',
        'utm_campaign' => '',
        'program_code' => '',
        'quick_create' => '',
        'redir' => '',
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
        if ( $json['account_recovery'] == true && $json['account']) {

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
