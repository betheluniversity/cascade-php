<?php
/**
 * Created by PhpStorm.
 * User: ces55739
 * Date: 4/27/16
 * Time: 10:17 AM
 */

// This method is currently deactivated. Keep it around for the next time we need an alert.
function create_event_alert($schools, $offCampusLocation, $firstDate){
    // convert cascade timestamp to a normal timestamp, and then to a date
    $firstDate = Date((int)$firstDate/1000);
    $today = time();

    // if the school does not include Seminary San Diego.
    // if it is on campus
    // if the date is after today
    // if the date is before august 20
    if( (sizeof($schools) == 0 || !in_array("Seminary San Diego", $schools)) && $offCampusLocation == '' && Date($firstDate) >= time() && Date($firstDate) <= strtotime("2018-08-20")){
        $twig = makeTwigEnviron('/code/events/twig');
        $html = $twig->render('event-alert.html', array());

        echo $html;
    }
}