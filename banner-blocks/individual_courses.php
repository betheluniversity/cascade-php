<?php
/**
 * Created by PhpStorm.
 * User: ejc84332
 * Date: 11/5/14
 * Time: 3:29 PM
 */


if($program == "CAPS")
    $program = "COPN";
else
    $program = "GOPN";
$url = "https://wsapi.bethel.edu/open-enrollment-courses/$program";
$results = json_decode(file_get_contents($url));
echo $results->data;

$twig = makeTwigEnviron('/code/banner-blocks/twig');
echo $twig->render('individual_courses.html');

?>

