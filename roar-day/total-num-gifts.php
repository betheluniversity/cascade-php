<?php
/**
 * Created by PhpStorm.
 * User: ejc84332
 * Date: 10/9/14
 * Time: 12:46 PM
 */



    $gifts = json_decode(file_get_contents("https://wsapi.bethel.edu/roar/total-num-gifts"));
    $results = $gifts->{'results'};

    $loader = new Twig_Loader_Filesystem($_SERVER["DOCUMENT_ROOT"] . '/code/roar-day/twig');
    $twig = new Twig_Environment($loader);

    //print_r($gifts);
//    echo '<ol>';
//    foreach($gifts->{'result'} as $result){
//        echo "<li>$result[1], $result[2] gifts</li>";
//    }
//    echo '</ol>';

    //twig version
    //todo test then delete above version
    echo $twig->render('total-num-gifts.html', array(
        'results' => $results
    ));
