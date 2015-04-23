<?php
/**
 * Created by PhpStorm.
 * User: ejc84332
 * Date: 11/5/14
 * Time: 3:29 PM
 */

<<<<<<< HEAD
=======
$twig = makeTwigEnviron('/code/banner-blocks/twig');

>>>>>>> twig
if($select_option == 4){
    return;
}else{
    $call_program_code = str_replace("%", "%25", $program_code);
    $url = "http://wsapi.bethel.edu/capsgs-checklist/$call_program_code/$select_option";
    echo "<!-- $url -->";
    $results = json_decode(file_get_contents($url));
    $results = explode("\n", $results->data);
    if($results[0]){
<<<<<<< HEAD
        echo "<ul>";
        foreach($results as $result){
            if( $result != "")
                echo "<li>$result</li>";
        }
        echo "</ul>";
=======
        echo $twig->render('capsgs_checklist.html', array(
            'results' => $results));
>>>>>>> twig
    }
}


