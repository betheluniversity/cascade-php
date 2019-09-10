<?php
/**
 * Created by PhpStorm.
 * User: ejc84332
 * Date: 10/9/14
 * Time: 12:46 PM
 */

    function get_total_num_gifts(){
        $gifts = json_decode(file_get_contents("https://wsapi.bethel.edu/roar/total-num-gifts"));
        $results = $gifts->{'result'};

        $loader = new Twig_Loader_Filesystem($_SERVER["DOCUMENT_ROOT"] . '/code/roar-day/twig');
        $twig = new Twig_Environment($loader);

        return $twig->render('total-num-gifts.html', array(
            'results' => $results
        ));
    }

    echo autoCache('get_total_num_gifts', array(), $cache_time=60);
