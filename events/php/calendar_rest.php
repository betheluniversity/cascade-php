<?php

    $month = $_GET['month'];
    $year = $_GET['year'];

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
    $headings = array('Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday');
    $calendar.= '<ul id="days-of-the-week"><li>'.implode('</li><li>',$headings).'</li></ul>';

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
        $calendar.= '<span>'.$list_day.'</span>';

        $date = new DateTime($year . '-' . $month . "-" . $list_day);
        $key = $date->format('Y-m-d');

        // Probably seperate this out.
        if (isset($xml[$key])){
            $calendar .= '<dl>';
            foreach($xml[$key] as $event){
                if($event['published']){
                    $calendar .= '<div class="vevent">';
                        $calendar .= '<dt class="summary">';
                        $calendar .= '<a href="//staging.bethel.edu/' . $event['path'] . '">' . $event['title'] . '</a>';
                        $calendar .= '</dt>';
                        $calendar .= '<dd>';
                            // Star time calculation
                            $start = gmdate("g:i a", $event['start'] / 1000);
                            $calendar .= '<span class="event-description">';
                            $calendar .= $event['title'] . '<br/>';
                            if ($event['description']){
                                $calendar .= $event['description']. '</br> ';
                            }
                            $calendar .= $start . '<br>';
                                $calendar .= '<span class="location">' . $event['location'] . '</span>';
                                // What does this do ?$calendar .= '<span class="dtstart">2014-06-19T00:00:00-05:00</span>';
                            $calendar .= '</span>';
                            $calendar .= '<ul class="categories" style="display:none">';
                                foreach($event['md'] as $md){
                                    $calendar .= '<li class="category">' . $md . '</li>';
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


function get_event_xml(){

    $xml = simplexml_load_file("/var/www/staging/public/_shared-content/xml/events.xml");
    $dates = array();
    $dates = traverse_folder($xml, $dates);
    return $dates;

}

function traverse_folder($xml, $dates){

    foreach ($xml->children() as $child) {

        $name = $child->getName();

        if ($name == 'system-folder'){
            $dates = traverse_folder($child, $dates);
        }elseif ($name == 'system-page'){
            $page_data = inspect_page($child);
            $new_dates = add_event_to_array($dates, $page_data);
            $dates = array_merge($dates, $new_dates);
        }
    }

    return $dates;
}

function add_event_to_array($dates, $page_data){

    //Iterate over each Date in this event
    foreach ($page_data['dates'] as $date) {
        $start = gmdate("Y-n-j", $date->{'start-date'} / 1000);
        // Add 1 day to $end so that the DatePeriod includes the last day in 'end-date'
        $end = gmdate("Y-n-j", strtotime('+1 day', $date->{'end-date'} / 1000));
        // Create a date period for each of the dates this event-date spans.
        // This will put it on the calendar each day.
        $period = new DatePeriod(
            new DateTime($start),
            new DateInterval('P1D'),
            new DateTime($end)
        );

        // Add a listng to the array for each event / event date
        foreach ($period as $date) {
            $key = $date->format('Y-m-d');
            // Check if this date has events already
            if (isset($dates[$key])) {
                array_push($dates[$key], $page_data);
                //Otherwise add a new array with this event for this date.
            } else {
                $new_value = array($page_data);
                $dates[$key] = $new_value;
            }
        }
    }
    return $dates;
}

function inspect_page($xml){
    $page_info = array(
        "title" => $xml->title,
        "display-name" => $xml->{'display-name'},
        "published" => $xml->{'last-published-on'},
        "description" => $xml->{'description'},
        "path" => $xml->path,
        "dates" => array(),
        "md" => array(),
    );

    $ds = $xml->{'system-data-structure'};

    // Add the dates
    $dates = $ds->{'event-dates'};
    foreach ($dates as $date){
        array_push($page_info['dates'], $date);
    }

    // Get the location
    $loc = $ds->location;
    if($loc == 'On campus' || $loc == "On Campus"){

        $location = $loc = $ds->{'on-campus-location'};
    }else{
        $location = $loc = $ds->{'off-campus-location'};
    }
    $other = $ds->{'other-on-campus'};
    if ($other[0]){
        $location = $other;
    }
    $page_info['location'] = $location;

    foreach ($xml->{'dynamic-metadata'} as $md){

        $name = $md->name;
        $options = array('general', 'offices', 'academic-dates', 'cas-departments', 'internal');

        if (in_array($name,$options)){
            //$page_info['md'] = array_merge($page_info['md'], $md->value);
            foreach($md->value as $value ){
                if ($value != "None"){
                    array_push($page_info['md'], $value);
                }
            }
        }
    }

    return $page_info;
}