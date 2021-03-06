<?php
/**
 * Created by PhpStorm.
 * User: ejc84332
 * Date: 11/5/14
 * Time: 3:29 PM
 */


function howToApply($code, $option){
    $twig = makeTwigEnviron('/code/banner-blocks/twig');
    if($option == 4){
        return;
    }else{
        $call_program_code = str_replace("%", "%25", $code);
        if( $call_program_code && $option ) {
            $url = "https://wsapi.bethel.edu/capsgs-checklist/$call_program_code/$option";
            echo "<!-- $url -->";
            try{
                $results = json_decode(@file_get_contents($url));
            } catch(ErrorException $e) {
                $results = null;
            }

            if( $results && property_exists($results, 'data')){
                $results = explode("\n", $results->data);
                if ($results[0]) {
                    echo $twig->render('capsgs_checklist.html', array(
                        'results' => $results));
                }
            }
        }
    }
}
