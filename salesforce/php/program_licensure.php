<?php

function programLicensure($code){
    $twig = makeTwigEnviron('/code/salesforce/twig');
    error_log($code, 0);
    $call_program_code = str_replace("%", "%25", $code);
    error_log($call_program_code, 0);

    $url = "https://wsapi.xp.bethel.edu/salesforce/state-licensures/$call_program_code";

    echo "<!-- $url -->";

    try {
        $results = json_decode(@file_get_contents($url), true);
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
