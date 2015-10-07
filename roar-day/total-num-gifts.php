<?php
/**
 * Created by PhpStorm.
 * User: ejc84332
 * Date: 10/9/14
 * Time: 12:46 PM
 */



    $gifts = json_decode(file_get_contents("http://wsapi.bethel.edu/roar/total-num-gifts"));
    $results = $gifts->{'result'};

    $loader = new Twig_Loader_Filesystem($_SERVER["DOCUMENT_ROOT"] . '/code/roar-day/twig');
    $twig = new Twig_Environment($loader);

    echo $twig->render('total-num-gifts.html', array(
        'results' => $results
    ));
