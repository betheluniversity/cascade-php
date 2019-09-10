<?php
/**
 * Created by PhpStorm.
 * User: ejc84332
 * Date: 7/27/15
 * Time: 1:11 PM
 */


function homecoming_guest_list($class_year){
    $url = "https://wsapi.bethel.edu/admissions/homecoming-guest-list/" . rawurlencode($class_year);
    $list = json_decode(file_get_contents($url));
    $names_list = Array();
    foreach($list->result as $key => $value){
        foreach($value as $names){
            $names = explode("Name:", $names);
            $names = explode(", ", $names[1]);

            // put into comma-list with "and" before the last item.
            $last  = array_slice($names, -1);
            $first = join(', ', array_slice($names, 0, -1));
            $both  = array_filter(array_merge(array($first), $last), 'strlen');
            $names = join(' and ', $both);

            array_push($names_list, $names);
        }
    }
    $twig = makeTwigEnviron('/code/banner-blocks/twig/');
    return $twig->render('homecoming-guest-list.html', array(
        'names_list' => $names_list)
    );
}