<?php
/**
 * Created by PhpStorm.
 * User: ejc84332
 * Date: 11/5/14
 * Time: 3:29 PM
 */


function get_individual_courses($php_program){
    if($php_program == "CAPS")
        $php_program = "COPN";
    else
        $php_program = "GOPN";
    $url = "http://wsapi.bethel.edu/open-enrollment-courses/$php_program";
    $results = json_decode(file_get_contents($url));
    echo $results->data;

    $twig = makeTwigEnviron('/code/banner-blocks/twig');
    return $twig->render('individual_courses.html');
}

echo autoCache('get_individual_courses', array($php_program));


?>

