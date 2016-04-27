<?php
/**
 * Created by PhpStorm.
 * User: ces55739
 * Date: 4/27/16
 * Time: 10:17 AM
 */

function create_event_alert($schools, $offCampusLocation, $firstDate){
    // convert cascade timestamp to a normal timestamp, and then to a date
    $firstDate = Date((int)$firstDate/1000);
    $today = time();

    // if the school does not include Seminary San Diego.
    // if it is on campus
    // if the date is after today
    if( (sizeof($schools) == 0 || !in_array("Seminary San Diego", $schools)) && $offCampusLocation == '' && Date($firstDate) >= time()){
        $twig = makeTwigEnviron('/code/events/twig');
        $html = $twig->render('event-alert.html', array());

        echo $html;
    }
}