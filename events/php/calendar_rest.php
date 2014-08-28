<?php

    require_once 'events_helper.php';

    $month = $_GET['month'];
    $year = $_GET['year'];
    $day = $_GET['day'];


    if (is_null($month) || is_null($year) || is_null($day) ){
        $month = date('n');
        $year = date('Y');
        $day = date('j');
    }


    // Set the month and year if it isn't passed in GET

    $next = get_next_month($month, $year);
    $prev = get_prev_month($month, $year);

    $next_month = $next->format('n');
    $next_year = $next->format('Y');

    $prev_month = $prev->format('n');
    $prev_year = $prev->format('Y');

    $data = Array();
    $data['previous_title'] = "Previous Month";
    $data['next_month_qs'] = "month=$next_month&day=1&year=$next_year";
    $data['previous_month_qs'] = "month=$prev_month&day=1&year=$prev_year";
    $data['current_month_qs'] = "month=$month&day=1&year=$year";
    $data['grid'] = draw_calendar($month, $year);
    $data['month_title'] = get_month_name($month) . ' ' .  $year;
    $data['next_title'] = "Next Month";
    $data['remote_user'] = $_SERVER['REMOTE_USER'];
    $data['server'] = $_SERVER;

echo json_encode($data);

function get_prev_month($month, $year, $day=1){
    $date = new DateTime();
    $date->setDate($year, $month, $day);
    return $date->modify('-1 month');
}

function get_next_month($month, $year, $day=1){
    $date = new DateTime();
    $date->setDate($year, $month, $day);
    return $date->modify('+1 month');
}

function get_days_in_month($month, $year){
    return cal_days_in_month(CAL_GREGORIAN, $month, $year);
}

function get_first_weekday_in_month($month, $year, $day = 1){
    $date = new DateTime($year . '-' . $month . "-" . $day);
    return $date->format('N');
}

function get_month_name($monthNum){
    $dateObj   = DateTime::createFromFormat('!m', $monthNum);
    $monthName = $dateObj->format('F'); // March
    return $monthName;
}

/* draws a calendar */
function draw_calendar($month,$year, $day=1){

    /* draw table */
    $calendar = '';

    $xml = get_event_xml();

    $date = new DateTime($year . '-' . $month . "-" . $day);

    /* table headings */
    //$headings = array('Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday');
    //$calendar.= '<ul id="days-of-the-week"><li>'.implode('</li><li>',$headings).'</li></ul>';

    $classes = array(
        1 => 'sun',
        2 => 'mon',
        3 => 'tue',
        4 => 'wed',
        5 => 'thu',
        6 => 'fri',
        7 => 'sat',
    );

    /* days and weeks vars now ... */
    $running_day = date('w',mktime(0,0,0,$month,1,$year));
    $days_in_month = date('t',mktime(0,0,0,$month,1,$year));

    $days_in_this_week = 1;
    $day_counter = 0;

    /* row for week one */
    $calendar.= '<ul class="calendar-row">';

    /* print previous month days until the first of the current month */
    for($x = 0; $x < $running_day; $x++){
        //Go in the past $x many days in the past
        $back = ($running_day - $x);

        $back = '-' . ($back) . ' days';
        $last_month_date = date('j', strtotime($back, strtotime($year . '-' . $month . '-01')));
        $calendar.= '<li class="'. $classes[$days_in_this_week] . ' event not-current"><span>' . $last_month_date . '</span></li>';
        $days_in_this_week++;
    }

    /* This starts at the first day of the month on the appropriate day of the week*/
    for($list_day = 1; $list_day <= $days_in_month; $list_day++){
        $calendar.= '<li class="' . $classes[$days_in_this_week] . ' event">';
        /* add in the day number */
        $calendar.= '<span name="' . $list_day .  '">'. $list_day . '</span>';

        $date = new DateTime($year . '-' . $month . "-" . $list_day);
        $key = $date->format('Y-m-d');

        // Probably seperate this out.
        if (isset($xml[$key])){

            $calendar .= '<dl>';
            foreach($xml[$key] as $event){

                $start = $event['specific_start'];
                $end = $event['specific_end'];
                $all_day = $event['specific_all_day'];
                //echo "Your current time now is : " . gmdate("Y-m-d\TH:i:s\Z");
                if($event['published']){
                    $calendar .= '<div class="vevent">';
                    $calendar .= '<dt class="summary">';

                    if( $event['external-link'] != ""){
                        $calendar .= '<a href="' . $event['external-link'] . '">' . $event['title'] . '</a>';
                    }
                    else{
                        $calendar .= '<a href="//www.bethel.edu' . $event['path'] . '">' . $event['title'] . '</a>';
                    }

                    $calendar .= '</dt>';
                    $calendar .= '<dd>';
                    //Check really specifically because $all_day is an XML object still.
                    //So if($all_day) is always true
                    if($all_day == true){
                        $start = '';
                        $end = '';

                    }else{
                        $start_date = $date = new DateTime('now', new DateTimeZone('America/Chicago'));
                        $start_date->setTimestamp($start / 1000);
                        $start = $start_date->format("g:i a");
                        if (substr($start, -6, 3) == ':00'){
                            $start = $start_date->format("g a");
                        }
                        $end_date = $date = new DateTime('now', new DateTimeZone('America/Chicago'));
                        $end_date->setTimestamp($end / 1000);
                        $end = $end_date->format("g:i a");
                        if (substr($end, -6, 3) == ':00'){
                            $end = $end_date->format("g a");
                        }

                        //test
                    }
                    $calendar .= '<span class="event-description">';
                    if ($event['description']){
                        $calendar .= $event['description']. '</br> ';
                    }
                    if ($start && $end){
                        if ($start == $end)
                            $calendar .= $start . '<br>';
                        else
                            $calendar .= $start . '-' . $end . '<br>';
                    }
                    $calendar .= '<span class="location">' . $event['location'] . '</span>';
                    $calendar .= '</span>';
                    $calendar .= '<ul class="categories" style="display:none">';
                    foreach($event['md'] as $md){
                        $calendar .= '<li class="category" data-category="' . $md  . '">' . $md . '</li>';
                    }
                    $calendar .= '</ul>';
                    $calendar .= '</dd>';
                    $calendar .= '</div>';
                }
            }
            $calendar .= '</dl>';
        }


        /** QUERY THE DATABASE FOR AN ENulY FOR THIS DAY !!  IF MATCHES FOUND, PRINT THEM !! **/
        // $calendar.= sul_repeat('<p> </p>',2);

        $calendar.= '</li>';
        //Create a new row if this week is full
        if($running_day == 6){
            $calendar.= '</ul>';
            if(($day_counter+1) != $days_in_month){
                $calendar.= '<ul class="calendar-row">';
            }
            $running_day = -1;
            $days_in_this_week = 0;
        }
        $days_in_this_week++; $running_day++; $day_counter++;
    }

    // keep track of this separate so it doesn't break the for loop
    $day_of_week = $days_in_this_week;
    if($days_in_this_week < 8 && $days_in_this_week != 1){
        for($x = 1; $x <= (8 - $days_in_this_week); $x++){
            $calendar.= '<li class="' . $classes[$day_of_week] . ' event not-current"><span>' . ($list_day++  - $days_in_month). '</span></li>';

        }
    }

    /* final row */
    $calendar.= '</ul>';

    /* all done, return result */
    return $calendar;
}