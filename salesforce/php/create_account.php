<?php

require $_SERVER["DOCUMENT_ROOT"] . '/code/vendor/autoload.php';

$email = isset($_POST["email"]) ? $_POST["email"] : '';
if ( $email ) {
    $data = process_form($_POST);
    echo $data;
}

function get_account_form($data) {
    $form = autoCache("create_account_form", array($data), 300, "No");
    return $form;
}

function create_account_form($data) {

    $data += array(
        'message' => "To get started, let's see if you already have a Bethel Account.",
        'messageClass' => '',
        'fullname' => false,
        "buttonTitle" => 'Check Email',
        'problems' => true
    );

    $twig = makeTwigEnviron('/code/salesforce/twig');
    return $twig->render('create_account.html', array('data' => $data));
}

function process_form($data) {

    $data += array(
        'php_path' => $_SERVER['PHP_SELF']
    );

    $staging = strstr(getcwd(), "/staging");
    $loader = new Twig_Loader_Filesystem($_SERVER["DOCUMENT_ROOT"] . '/code/salesforce/twig');
    $twig = new Twig_Environment($loader);

    if ($staging){
        //$wsapi_url = 'https://wsapi.xp.bethel.edu/salesforce/register';
        $wsapi_url = 'https://e46b-173-165-237-157.ngrok-free.app/salesforce/register';
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
        'login_url' => ''
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

    if ($json['success'] == true) {
        if ( $json['account_recovery'] == true && $json['account']) {

            $name = $json['account']['first'];

            if ($json['account']['bethel'] == true){
                $message = '<h3>Welcome back ' . $name . '!</h3>To continue, log in with the username and password for your Bethel Community Account.';
                $data += array('bethel' => true);
            } else {
                $message = '<h3>Welcome back ' . $name . '!</h3>To continue, log in using this email address and your Bethel Account password.';
            }

            $data += array(
                'message' => $message,
                'messageClass' => '',
                'fullname' => false,
                'buttonTitle' => 'Log In',
                'php_path' => '',
                'org_id' => '',
                'noinput' => true
            );

        } else {
            $data += array(
                'message' => "We've emailed you a link to create your Bethel Account and password.<br /><br />Please check your inbox within the next few minutes.<br />If you don't get the email, please check your spam folder, or contact <a href='https://www.bethel.edu/its'>ITS</a>.<br /><br />After you create your account, you will be able to log in.",
                'messageClass' => 'alert',
                'fullname' => false,
                'buttonTitle' => 'Log In',
                'php_path' => '',
                'org_id' => '',
                'noinput' => true
            );
        }
    } else {
        $data += array(
            'message' => "<h3>Create an Account</h3>Before we continue, let's create a Bethel Account.<br />Please enter your first and last name.",
            'messageClass' => '',
            'fullname' => true,
            'buttonTitle' => 'Create Account',
            'php_path' => '',
            'org_id' => ''
        );
    }

    return $twig->render('account_form.html', array('data' => $data));
}
