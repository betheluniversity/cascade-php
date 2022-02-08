<?php
/**
 * Created by PhpStorm.
 * User: ejc84332
 * Date: 11/5/14
 * Time: 3:29 PM
 */

function howToApply($code, $option){
    $twig = makeTwigEnviron('/code/salesforce/twig');
    $call_program_code = str_replace("%", "%25", $code);

    if ($option == 1) {
        $url = "https://wsapi.xp.bethel.edu/salesforce/program-prerequisites/$call_program_code";
    } elseif ($option == 3) {
        $url = "https://wsapi.xp.bethel.edu/salesforce/program-requirements/$call_program_code";
    } else {
        return;
    }

    echo "<!-- $url -->";

    try {
        $results = json_decode(@file_get_contents($url));
    } catch(ErrorException $e) {
        $results = null;
    }

    if ($results) {
        echo $twig->render('capsgs_checklist.html', array(
            'results' => $results));
    }
}
