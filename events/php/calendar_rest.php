<?php
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


    $cache_name = $year.'_'.$month.'_CALENDAR_CACHE';
    $memcache_obj = memcache_connect("localhost", 11211);
    $memcache_data = $memcache_obj->get($cache_name);

    if (!$memcache_data){
        $data['grid'] = draw_calendar($month, $year);
        $memcache_obj->add($cache_name, $data['grid'], false, time()+ 1800);
    }else{
        $data['grid'] = $memcache_data;
    }

    $data['month_title'] = get_month_name($month) . ' ' .  $year;
    $data['next_title'] = "Next Month";
    $data['remote_user'] = $_SERVER['REMOTE_USER'];

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

//        $cache_name = 'CALENDAR_XML';
//        $memcache_obj = memcache_connect("localhost", 11211);
//        $memcache_data = $memcache_obj->get($cache_name);
//
//        if (!$memcache_data){
        $xml = get_event_xml();
//            file_put_contents('calendar_content.xml', $xml);
//            $memcache_obj->add($cache_name, $cache_name, false, time()+ 1800);
//        }else{
//            $xml = file_get_contents('calendar_content.xml');
//            echo $xml;
//        }
//
//        return $xml;

        $date = new DateTime($year . '-' . $month . "-" . $day);

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
    function get_event_xml(){
        ##Create a list of categories the calendar uses
        $xml = simplexml_load_file("/var/www/cms.pub/_shared-content/xml/calendar-categories.xml");
        $categories = array();
        $xml = $xml->{'system-page'};
        foreach ($xml->children() as $child) {
            if($child->getName() == "dynamic-metadata"){
                foreach($child->children() as $metadata){
                    if($metadata->getName() == "value"){
                        array_push($categories, (string)$metadata);
                    }
                    //$metadata;
                }
            }
        }
        //print_r($categories);
        $xml = simplexml_load_file("/var/www/cms.pub/_shared-content/xml/events.xml");
        $dates = array();
        $dates = traverse_folder($xml, $dates, $categories);
        return $dates;
    }
    function traverse_folder($xml, $dates, $categories){
        foreach ($xml->children() as $child) {
            $name = $child->getName();
            if ($name == 'system-folder'){
                $dates = traverse_folder($child, $dates, $categories);
            }elseif ($name == 'system-page'){
                $page_data = inspect_page($child, $categories);
                // Child is the xml in this case.
                // Only add the to the calendar if it is an event.
                $dataDefinition = $child->{'system-data-structure'}['definition-path'];
                if( $dataDefinition == "Event")
                {
                    $new_dates = add_event_to_array($dates, $page_data);
                    $dates = array_merge($dates, $new_dates);
                }
            }
        }
        return $dates;
    }
    function add_event_to_array($dates, $page_data){
        //Iterate over each Date in this event
        foreach ($page_data['dates'] as $date) {
            $start_date = $date->{'start-date'} / 1000;
            $end_date = $date->{'end-date'} / 1000;
            $specific_start = date("Y-m-d", $start_date  );
            $specific_end = date("Y-m-d", $end_date );

            $page_data['specific_start'] = $date->{'start-date'};
            $page_data['specific_end'] = $date->{'end-date'};
            $page_data['specific_all_day'] = $date->{'all-day'};

            if($specific_start == $specific_end){
                //Don't need a date range.
                $key = date("Y-m-d", $start_date);
                // Check if this date has events already
                if (isset($dates[$key])) {
                    array_push($dates[$key], $page_data );
                    //Otherwise add a new array with this event for this date.
                } else {
                    $dates[$key] = array($page_data);
                }
            }else{
                $page_data['specific_all_day'] = true;
                $start = date("Y-n-j", $start_date);
                // Add 1 day to $end so that the DatePeriod includes the last day in 'end-date'
                $end = date("Y-n-j", strtotime('+1 day', $end_date));
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
                        $dates[$key] = array($page_data);
                    }

                }
            }
        }
        return $dates;
    }
    function inspect_page($xml, $categories){
        //echo "inspecting page";
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
        $page_info["external-link"] = $ds->{'link'};
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
        if ($location == "none"){
            $location = "";
        }
        $page_info['location'] = $location;
        foreach ($xml->{'dynamic-metadata'} as $md){
            $name = $md->name;
            $options = array('general', 'offices', 'academic-dates', 'cas-departments', 'internal');
            foreach($md->value as $value ){
                if($value == "None"){
                    continue;
                }
                if (in_array($name,$options)){
                    //Is this a calendar category?
                    if (in_array($value, $categories)){
                        array_push($page_info['md'], $value . '-' . $name);
                    }else{
                        array_push($page_info['md'], 'other');
                    }
                }
            }
        }
        return $page_info;
    }