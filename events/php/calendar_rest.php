<?php


use Twig\Extension\CoreExtension;

include_once $_SERVER["DOCUMENT_ROOT"] . "/code/general-cascade/macros.php";
require $_SERVER["DOCUMENT_ROOT"] . '/code/vendor/autoload.php';

error_log("Start Run\n------------------------------\n", 3, '/tmp/calendar.log');
$total_time_start = microtime(true);

$month = null;
$year = null;
if( array_key_exists('month',$_GET) )
    $month = $_GET['month'];
if( array_key_exists('year',$_GET) )
    $year = $_GET['year'];
if (is_null($month) || is_null($year)){
    $month = date('n');
    $year = date('Y');
}
$data = autoCache("build_calendar_data", array($month, $year));

echo $data;

/**
 * @param $month
 * @param $year
 * @return false|string
 */
function build_calendar_data($month, $year){
    include_once $_SERVER["DOCUMENT_ROOT"] . "/code/general-cascade/macros.php";
    // Set the month and year if it isn't passed in GET
    $next = get_next_month($month, $year);
    $prev = get_prev_month($month, $year);
    $next_month = $next->format('n');
    $next_year = $next->format('Y');
    $prev_month = $prev->format('n');
    $prev_year = $prev->format('Y');
    $data = Array();
    $data['previous_title'] = "Previous Month";
    $data['next_month_qs'] = "month=$next_month&year=$next_year";
    $data['previous_month_qs'] = "month=$prev_month&year=$prev_year";
    $data['current_month_qs'] = "month=$month&year=$year";

    $data['grid'] = draw_calendar($month, $year);
    $data['month_title'] = get_month_name($month) . ' ' . $year;
    $data['next_title'] = "Next Month";

    if( array_key_exists('REMOTE_USER', $_SERVER))
        $data['remote_user'] = $_SERVER['REMOTE_USER'];
    else {
        $data['remote_user'] = null;
    }

//    $total_time_end = microtime(true);
//    $time = $total_time_end - $total_time_start;
    return json_encode($data);
}

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
    $draw_calendar_time_start = microtime(true);
    $get_xml_start_time = microtime(true);
    
    $calendar = '';
    $xml = autoCache("get_event_xml", array());

    $after_xml_time_start = microtime(true);
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
    for($x = 0; $x < $running_day; $x++) {
        //Go in the past $x many days in the past
        $back = ($running_day - $x);
        $back = '-' . ($back) . ' days';
        $last_month_date = date('j', strtotime($back, strtotime($year . '-' . $month . '-01')));
        $calendar .= '<li class="' . $classes[$days_in_this_week] . ' event not-current"><span>' . $last_month_date . '</span></li>';
        $days_in_this_week++;
    }

    $twig = makeTwigEnviron('/code/events/twig');
    $twig->getExtension('Twig_Extension_Core')->setTimezone('America/Chicago');
    $calendar .= $twig->render('calendar_rest.html',array(
        'running_day' => $running_day,
        'days_in_month' => $days_in_month,
        'days_in_this_week' => $days_in_this_week,
        'day_counter' => $day_counter,
        'classes' => $classes,
        'xml' => $xml,
        'year' => $year,
        'month' => $month));

    /* all done, return result */
    $draw_calendar_time_end = microtime(true);

    $draw_calendar_time = $draw_calendar_time_end - $draw_calendar_time_start;
    $after_xml_time = $draw_calendar_time_end - $after_xml_time_start;
    error_log("After XML draw_calendar in $after_xml_time seconds\n", 3, '/tmp/calendar.log');
    error_log("Full draw_calendar in $draw_calendar_time seconds\n", 3, '/tmp/calendar.log');
    return $calendar;
}

function get_event_xml(){

    ##Create a list of categories the calendar uses
//        $xml = simplexml_load_file($_SERVER["DOCUMENT_ROOT"] . "/_shared-content/xml/calendar-categories.xml");
    $xml = autoCache("simplexml_load_file", array($_SERVER["DOCUMENT_ROOT"] . "/_shared-content/xml/calendar-categories.xml"));
    $categories = array();
    $xml = $xml->{'system-page'};
    foreach ($xml->children() as $child) {
        if($child->getName() == "dynamic-metadata"){
            foreach($child->children() as $metadata){
                if($metadata->getName() == "value"){
                    array_push($categories, (string)$metadata);
                }
            }
        }
    }
//        $xml = simplexml_load_file($_SERVER["DOCUMENT_ROOT"] . "/_shared-content/xml/events.xml");
    $xml = autoCache("simplexml_load_file", array($_SERVER["DOCUMENT_ROOT"] . "/_shared-content/xml/events.xml"));
    $event_pages = $xml->xpath("//system-page[system-data-structure[@definition-path='Event']]");
    $dates = array();
    $datePaths = array();
    foreach($event_pages as $child ){
        $page_data = inspect_page($child, $categories);
        if (!$page_data["hide-from-calendar"]){
            $dates = add_event_to_array($dates, $page_data, $datePaths);
        }
    }
    return $dates;
}


function add_event_to_array($dates, $page_data, &$datePaths) {
    //Iterate over each Date in this event
    foreach ($page_data['dates'] as $date) {
        $start_date = (int)($date['start-date']) / 1000;
        $end_date = (int)($date['end-date']) / 1000;
        $specific_start = date("Y-m-d", $start_date  );
        $specific_end = date("Y-m-d", $end_date );

        $page_data['time_string'] = $date['time-string'];
        $page_data['specific_start'] = $date['start-date'];
        $page_data['specific_end'] = $date['end-date'];
        $page_data['specific_all_day'] = $date['all-day'];
        $page_data['specific_need_time_zone'] = $date['outside-of-minnesota'];
        if($page_data['specific_need_time_zone'] == true) {
            $time_zone = $date['time-zone'];
            if ($time_zone == "Hawaii-Aleutian Time") {
                $page_data['specific_time_zone'] = "HT";
            } elseif ($time_zone == "Alaska Time") {
                $page_data['specific_time_zone'] = "AT";
            } elseif ($time_zone == "Pacific Time") {
                $page_data['specific_time_zone'] = "PT";
            } elseif ($time_zone == "Mountain Time") {
                $page_data['specific_time_zone'] = "MT";
            } elseif ($time_zone == "Eastern Time") {
                $page_data['specific_time_zone'] = "ET";
            } else {
                $page_data['specific_time_zone'] = "CT";
            }
        } else {
            $page_data['specific_time_zone'] = "";
        }

        if($specific_start == $specific_end){
            //Don't need a date range.
            $key = date("Y-m-d", $start_date);
            add_page_to_day($dates[$key], $page_data, $datePaths[$key]);
        }
        // range of dates
        else{
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
            $foreach_start_time = microtime(true);
            foreach ($period as $inner_date) {
                $key = $inner_date->format('Y-m-d');
                add_page_to_day($dates[$key], $page_data, $datePaths[$key]);
            }

        }

    }
    return $dates;
}

/** Adds an event page to a given day.
 * Also accounts for empty days and duplicate events.
 *
 * @param $day Array The array of event pages associated with a particular day
 * @param $page Array Represents the information about a given page, ultimately set by inspect_page()
 * @param $dayPaths Array The array of event page links associated with a particular day, for preventing duplicates.
 * @return void Void because the method acts on the arrays themselves.
 */
function add_page_to_day(&$day, $page, &$dayPaths)
{
    if (isset($day)) {
        $findPathKey = array_search($page["path"], $dayPaths);
        if($findPathKey === FALSE) {
            $day[] = $page;
            $dayPaths[] = $page["path"];
        } else {
            $removeTrailingSpace = trim($day[$findPathKey]['time_string']);
            $day[$findPathKey]['time_string'] = "$removeTrailingSpace, {$page['time_string']}".PHP_EOL;
        }
    } else {
        $day = array($page);
        $dayPaths = array($page['path']);
    }
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
        "hide-from-calendar" => false,
        "time-string" => ''
    );
    $ds = $xml->{'system-data-structure'};
    $page_info["externallink"] = $ds->{'link'};

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
        $options = array('general', 'offices', 'academic-dates', 'cas-departments', 'internal', 'hide-from-calendar');
        foreach($md->value as $value ){
            if($value == "None"){
                continue;
            }
            if (in_array($name,$options)){
                if ($name == 'hide-from-calendar'){
                    if ($value == "Yes"){
                        $page_info["hide-from-calendar"] = true;
                    }
                } else {
                    //Is this a calendar category?
                    if (in_array($value, $categories)) {
                        array_push($page_info['md'], $value . '-' . $name);
                    } else {
                        array_push($page_info['md'], 'other');
                    }
                }
            }
        }
    }
    // convert any XML to array
    $final_page_info = array();
    foreach($page_info as $k => $v){
        if($k != "dates" && $k != "md"){
            $final_page_info[$k] = (string)$v;
        }else if($k == "dates"){
            $dates = array();
            foreach($v as $date_k => $date_v){
                $date_v = array(
                    "start-date" => (string)$date_v->{'start-date'},
                    "end-date" => (string)$date_v->{'end-date'},
                    "all-day" => (string)$date_v->{'all-day'},
                    "outside-of-minnesota" => (string)$date_v->{'outside-of-minnesota'},
                    "timezone" => (string)$date_v->{'timezone'},

                    //The time-string is generated using the information from the XML but is not a part of the XML directly.
                    "time-string" => form_time_string((string)$date_v->{'start-date'},
                                                    (string)$date_v->{'end-date'},
                                                    (string)$date_v->{"outside-of-minnesota"},
                                                    (string)$date_v->{"timezone"})
                );
                $dates[$date_k] = $date_v;
            }
            $final_page_info[$k] = $dates;
        }else{
            $final_page_info[$k] = $v;
        }
    }
    return $final_page_info;
}


/** Creates and formats a string representing the time an event takes place, for easy display on the calendar.
 * @param $start string beginning of a single instance ("sub-event") of the event
 * @param $end string ending of a single instance ("sub-event") of the event
 * @param $outsideMN string if the event is outside Minnesota
 * @param $timeZone string the time zone, for foreign events.
 * @return string easily-displayable event time string. Does not include the actual date.
 */
function form_time_string($start, $end, $outsideMN, $timeZone){
    $start_date = (int) $start/1000;
    $end_date = (int) $end/1000;
    $specific_start = date("g:i a", $start_date);
    $specific_end = date("g:i a", $end_date);
    $find = array(":00", "m", "12 p.m.", "Noon", "12 a.m.");
    $replace = array("", ".m.", "Noon", "noon", "midnight");
    $r_start = str_replace($find, $replace, $specific_start);
    $r_end = str_replace($find, $replace, $specific_end);

    $tz_ret = "";
    if($outsideMN){
        $tz_ret = " ($timeZone)";
    }

    if($r_start == $r_end){
        if($r_start == "midnight"){
            return "";
        }
        return "$r_start$tz_ret".PHP_EOL;
    }

    return "$r_start-$r_end$tz_ret".PHP_EOL;
}