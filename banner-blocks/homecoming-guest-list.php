<?php
/**
 * Created by PhpStorm.
 * User: ejc84332
 * Date: 7/27/15
 * Time: 1:11 PM
 */


function homecoming_guest_list($class_year){
    $url = "http://wsapi.bethel.edu/admissions/homecoming-guest-list/" . rawurlencode($class_year);
    $list = json_decode(file_get_contents($url));
    $names_list = Array();
    foreach($list->result as $key => $value){
        foreach($value as $names){
            $names = explode("Name:", $names);
            array_push($names_list, $names[1]);
        }
    }
    $twig = makeTwigEnviron('/code/banner-blocks/twig/');
    return $twig->render('homecoming-guest-list.html', array(
        'names_list' => $names_list)
    );
}