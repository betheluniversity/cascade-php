<?php
///**
// * Created by PhpStorm.
// * User: ejc84332
// * Date: 8/28/14
// * Time: 10:21 AM
// */
//
//// This file has been replaced by a cascade version: "/_blink-content/events-channel/blink-events-channel"
//
//    header('Content-type: application/xml');
//    require_once 'events_helper.php';
//    require $_SERVER["DOCUMENT_ROOT"] . '/code/vendor/autoload.php';
//
//    $month = date('n');
//    $year = date('Y');
//    $day = date('j');
//
//    // get events
//    $xml = get_event_xml();
//
//    //event array has each date as a key in m-d-y format
//    //$date = new DateTime($year . '-' . $month . "-" . $day);
//    $date = new DateTime(2015 . '-'. 4 . '-' . 1);
//    $key = $key = $date->format('Y-m-d');
//
//    $todays_events = $xml[$key];
//    $display_date = $date->format('F j, Y');
//
//    //twig version
//    $loader = new Twig_Loader_Filesystem($_SERVER["DOCUMENT_ROOT"] . '/code/events/twig');
//    $twig = new Twig_Environment($loader);
//    $twig->addFilter(new Twig_SimpleFilter('preg_replace','preg_replace'));
//    echo $twig->render('blink-events.html', array(
//        'month' => $month,
//        'year' => $year,
//        'day' => $day,
//        'xml' => $xml,
//        'date' => $date,
//        'key' => $key,
//        'todays_events' => $todays_events,
//        'display_date' => $display_date));
//?>
