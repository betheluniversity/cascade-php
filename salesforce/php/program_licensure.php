<?php

function programLicensure($code){
    $twig = makeTwigEnviron('/code/salesforce/twig');
    error_log($code, 0);
    $call_program_code = str_replace("%", "%25", $code);
    error_log($call_program_code, 0);

    // Get the prod or staging WSAPI URL
    $staging = strstr(getcwd(), "/staging");
    if ($staging){
        $wsapi_url = 'https://wsapi.xp.bethel.edu';
    }else{
        $wsapi_url = 'https://wsapi.bethel.edu';
    }
    $wsapi_url .= '/salesforce/state-licensures/' . $call_program_code;

    echo "<!-- $wsapi_url -->";

    try {
        $results = json_decode(@file_get_contents($wsapi_url), true);
    } catch(ErrorException $e) {
        $results = null;
    }

    if ($results) {
        echo $twig->render('program_licensure.html', array(
            'results' => $results));
    } else {
        echo "No Data Available.";
    }
}
