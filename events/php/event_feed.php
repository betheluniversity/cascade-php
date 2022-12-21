<?php
/**
 * Created by PhpStorm.
 * User: ces55739
 * Date: 7/24/14
 * Time: 2:09 PM
 */

/////////////////////////////////////////////////////////////////////
// These are the variables passed over from Cascade.
/////////////////////////////////////////////////////////////////////
$eventFeedCategories;
$featuredEventOptions;
// index 0: url
// index 1: description (if("") use metadata description else use description)
// index 2: hide date (yes or no)
// index 3: final html of the event. Starts out equal to the null string.

$NumEvents;
$PriorToToday;
$AddFeaturedEvents;
$StartDate;
$EndDate;

/////////////////////////////////////////////////////////////////////
include_once $_SERVER["DOCUMENT_ROOT"] . "/code/php_helper_for_cascade.php";
include_once $_SERVER["DOCUMENT_ROOT"] . "/code/general-cascade/feed_helper.php";
include_once $_SERVER["DOCUMENT_ROOT"] . "/code/general-cascade/macros.php";
require $_SERVER["DOCUMENT_ROOT"] . '/code/vendor/autoload.php';

function create_event_feed($categories, $heading=""){
    // you should only cache the array of html, not the full data.
    $feed = autoCache("create_event_feed_logic", array($categories, $heading));
    return $feed;
}


// Create the Event Feed events.
function create_event_feed_logic($categories, $heading){
    $arrayOfEvents = get_xml($_SERVER["DOCUMENT_ROOT"] . "/_shared-content/xml/events.xml", $categories, "inspect_event_page");

    //////////////////////////////////////////
    // Turn all dates into individual events
    /////////////////////////////////////////
    $eventArrayWithMultipleEvents = array();
    global $PriorToToday;

    if( is_array($arrayOfEvents) ) {
        foreach ($arrayOfEvents as $event) {
            $dates = $event['dates'];
            // foreach date
            if ($dates) {
                foreach ($dates as $date) {
                    $newDate['start-date'] = $date->{'start-date'} / 1000;
                    $newDate['end-date'] = $date->{'end-date'} / 1000;
                    $newDate['all-day'] = $date->{'all-day'}->{'value'};
                    $newDate['outside-of-minnesota'] = $date->{'outside-of-minnesota'}->{'value'};
                    $newDate['time-zone'] = $date->{'time-zone'};

                    $isArtOrTheater = check_if_art_or_theatre($event);
                    $threeDaysSinceStart = ($date->{'start-date'} / 1000) + (3 * 24 * 60 * 60);


                    // This will hide all events prior to today.
                    if (time() > $date->{'end-date'} / 1000 && $PriorToToday != 'Show')
                        continue;

                    //hides gallery events three days after they begin.
                    if ($isArtOrTheater && time() > $threeDaysSinceStart) {
                        continue;
                    }

                    $newEvent = $event;
                    $newEvent['date'] = $newDate;
                    $newEvent['start-date'] = $newDate['start-date'];
                    $newEvent['end-date'] = $newDate['end-date'];
                    $newEvent['date-for-sorting'] = $date->{'start-date'} / 1000;


                    $newEvent['html'] = get_event_html($newEvent);

                    if (display_on_feed_events($newEvent)) {
                        array_push($eventArrayWithMultipleEvents, $newEvent);
                    }


                    // art exhibits and theatre productions only add 1 date.
                    // UPDATE: ITS-219128 removed this feature
                    /*if ($isArtOrTheater != 0) {
                        break;
                    }*/
                }
            }
        }
    }

    /////////////////////////////////////////

    $allEventArray = array();
    foreach( $eventArrayWithMultipleEvents as $event)
    {
        if($event['date']['start-date'] >= time()) {
            array_push( $allEventArray, $event);
        }

        // Checks for events that are more than a day long
        $lengthOfEvent = $event['end-date'] - $event['start-date'];
        // Convert timestamp so it can add a day per day of the event. It is converted back later
        $date = date("Y-m-d H:i:s", $event['start-date']);
        // While the event is longer than a day (86400 seconds), post event to screen
        while ($lengthOfEvent >= 86400) {
            $date = date('Y-m-d H:i:s', strtotime($date . ' +1 day'));
            $event['date']['start-date'] = strtotime($date);
            $event['html'] = get_event_html($event);
            $event['date-for-sorting'] = $event['date']['start-date'] / 1000;
            $lengthOfEvent -= 86400;
            
            if($event['date']['start-date'] >= time()) {
                array_push( $allEventArray, $event);
            }
        }
    }

    $sortedEvents = array_reverse(sort_by_date($allEventArray));
    // Only grab the first X number of events.
    global $NumEvents;

    $numEventsToFind = $NumEvents;

    $eventArray = array();

    foreach( $sortedEvents as $event)
    {
        array_push( $eventArray, $event['html']);
    }

    $eventArray = array_slice($eventArray, 0, $numEventsToFind, true);

    // FEATURED EVENTS
    global $featuredEventOptions;
    $featuredEvents = create_featured_array($featuredEventOptions);

    $numEvents = sizeof( $eventArray);

    // Print No upcoming events if there are no events.
    if( sizeOf( $eventArray) == 0){
        $eventsArray = array("<img class='d-flex mr-3 mb-3' loading='lazy' src='https://bethel-university.imgix.net/events/images/event-feed-fallback.jpg?w=300&amp;auto=format' alt='Events feed' /><p>See yourself at Bethel—join us for one of our in-person or virtual events!</p>");
    }
    $combinedArray = array($featuredEvents, $eventArray, $numEvents );
    return $combinedArray;
}

// A function to check if the event is art or theatre.
//  if so, only put it on the event feed once!
//  This should hopefully not crowd the event feed.
function check_if_art_or_theatre($event){
    foreach ($event['xml']->{'dynamic-metadata'} as $md){

        $name = $md->name;
        $options = array('general');

        foreach($md->value as $value ){
            if($value == "None" || $value == "none"){
                continue;
            }

            if (in_array($name,$options)){

                if ('Art Galleries' == $value || 'Johnson Gallery' == $value || 'Olson Gallery' == $value)
                {
                    return 1;
                }
                else if('Theatre' == $value){
                    return 1;
                }
            }

        }
    }
    return 0;
}

// Gathers the information of an event page
function inspect_event_page($xml, $categories){
    $page_info = array(
        "title" => $xml->title,
        "display-name" => $xml->{'display-name'},
        "published" => $xml->{'last-published-on'},
        "description" => $xml->{'description'},
        "path" => $xml->path,
        "date" => "",
        "date-for-sorting" => "",
        "dates" => array(),
        "md" => array(),
        "html" => "",
        "display-on-feed" => false,
        "external-link" => "",
        "image" => "",
        "xml" => $xml,
    );
    if( strpos($page_info['path'],"_testing") !== false) // just make "true"?
        return "";

    $ds = $xml->{'system-data-structure'};

    $options = array('general', 'offices', 'academic-dates', 'cas-departments', 'adult-undergrad-program', 'graduate-program', 'seminary-program', 'internal');
    $page_info['display-on-feed'] = match_metadata_articles($xml, $categories, $options);

    $dataDefinition = $ds['definition-path'];



    if( $dataDefinition == "Event")
    {
        $page_info["external-link"] = $ds->{'link'};
        ///////////////////////////////////////////
        // Dates
        ///////////////////////////////////////////
        $dates = $ds->{'event-dates'};
        $page_info['dates'] = $dates;


        ///////////////////////////////////////////
        // Get the location
        ///////////////////////////////////////////

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

        // Get the image.
        $page_info['image'] = $ds->{'image'}->path;


        ///////////////////////////////////
        // Featured Events
        ///////////////////////////////////
        global $featuredEventOptions;
        global $AddFeaturedEvents;
        // Check if it is a featured Event.
        // If so, get the featured event html.
        if ( $AddFeaturedEvents == "Yes"){
            foreach( $featuredEventOptions as $key=>$featuredEvent)
            {
                // Check if the url of the event = the url of the desired feature event.
                if( $page_info['path'] == $featuredEvent[0]){
                    $featuredEventOptions[$key][3] = get_featured_event_html( $page_info, $featuredEvent);
                }

            }
        }
        ///////////////////////////////////
    }

    return $page_info;
}

// Checks to see if the event falls between the range of
// Returns true to display event.
//   else returns false to get rid of the event.
function display_on_feed_events($page_info){
    global $StartDate;
    global $EndDate;

    //Check if the event falls between the given range.
    if( $StartDate != "" && $EndDate != "" ){
        $modifiedStartDate = $StartDate / 1000;
        $modifiedEndDate = $EndDate / 1000;
        // If the end date of the page comes after the start
        if( $modifiedStartDate < $page_info['date']['end-date'] && $page_info['date']['start-date'] < $modifiedEndDate ){
            return true;
        }
    }
    elseif( $StartDate != ""){
        $modifiedStartDate = $StartDate / 1000;
        if( $modifiedStartDate < $page_info['date']['end-date']){
            return true;
        }
    }
    elseif( $EndDate != ""){
        $modifiedEndDate = $EndDate / 1000;
        if( $page_info['date']['start-date'] < $modifiedEndDate ){
            return true;
        }
    }
    else
    {
        return true;
    }

    return false;
}

// Returns the featured Event html.
function get_featured_event_html($event, $featuredEventOptions){
    // todo: this neeeds to be refactored
    // Get the most recent start/enddate pair.
    //  Get it in the form of a date object with start/end/all-day
    $dates = $event['dates'];
    $dateToUse = array();
    $firstDate = null;
    $lastDate = null;
    foreach( $dates as $date )
    {
        if( (($firstDate->{'end-date'} / 1000) > ($date->{'end-date'} / 1000)) || $firstDate == null)
            $firstDate = $date;
        if( (($lastDate->{'end-date'} / 1000) < ($date->{'end-date'} / 1000)) || $lastDate == null)
            $lastDate = $date;
    }

    if( ($lastDate->{'end-date'} / 1000) < time() )
        return '';

    $title = $event['title'];
    $path = convert_path_to_link($event);

    if( $featuredEventOptions[2] == "No"){
        if( sizeof($dates) > 1)
        {
            $formattedDate = date("l, F j", $firstDate->{'start-date'}/1000)." - ".date("l, F j", $lastDate->{'end-date'}/1000 );
        }
        else{
            $formattedDate = format_featured_event_date($firstDate);
        }
        $date = $formattedDate;
    }

    if( $featuredEventOptions[1] != "" )
        $description = $featuredEventOptions[1];
    elseif( $event['description'] != "")
        $description = $event['description'];

    $description = strip_tags($description, '<a><br><hr>');

    // Only display it if it has an image.
    if( $event['image'] != "" && $event['image'] != "/"){
        $twig = makeTwigEnviron('/code/events/twig');
        $html = $twig->render('get_featured_event_html.html', array(
            'thumborURL'=> srcset($event['image'], $print=false, $lazy=true, "", $description),
            'path' => $path,
            'title' => $title,
            'date' => $date,
            'description' => $description));

        return $html;
    }
    else
        echo "<!-- skipping featured event -- event has no image -->";
    return null;

}

// returns the html of the event.
function get_event_html( $event){

    $title = $event['title'];
    $start = $event['date']['start-date'];
    $end = $event['date']['end-date'];

    $twig = makeTwigEnviron('/code/events/twig');
    $twig->addFilter(new Twig_SimpleFilter('convert_path_to_link','convert_path_to_link'));
    $twig->addFilter(new Twig_SimpleFilter('format_fancy_event_date','format_fancy_event_date'));
    $twig->addFilter(new Twig_SimpleFilter('get_month_shorthand_name','get_month_shorthand_name'));
    $twig->addFilter(new Twig_SimpleFilter('get_timezone_shorthand','get_timezone_shorthand'));
    $html = $twig->render('get_event_html.html', array(
        'title' => $title,
        'event' => $event,
        'start' => $start,
        'end' => $end));

    return $html;
}



// Returns a Date that is formatted correctly for a Featured Event
// Both $startdate and $endDate are timestamps
function format_featured_event_date( $date ){
    // todo: these look different than the function below.
    $startDate = $date->{'start-date'} / 1000;
    $endDate = $date->{'end-date'} / 1000;
    $allDay = $date->{'all-day'};

    // If it spans multiple days, do not display a time.
    // if all day, do not display a time.
    if( date("m/d/Y", $startDate) != date("m/d/Y", $endDate) ){
        return date("F j, Y", $startDate)." - ".date("F j, Y", $endDate);
    }

    // if it is all day
    if( $allDay == "Yes" ){
        return date("F j, Y", $startDate);
    }

    // if it is normal.
    // if 12:00 pm, change to noon
    if( date("g:i a", $startDate) == "12:00 pm"){
        return date("F j, Y |", $startDate)." noon";
    }
    else{
        $returnedDate = date("F j, Y | g:i a", $startDate);
        // Change am/pm to a.m./p.m.
        $returnedDate = str_replace("am", "a.m.", $returnedDate);
        $returnedDate = str_replace("pm", "p.m.", $returnedDate);

        // format 7:00 to 7
        $returnedDate = str_replace(":00", "", $returnedDate);
    }
    return $returnedDate;
}

// Returns a formatted date correctly for an event.
// Both $startdate and $endDate are timestamps
function format_fancy_event_date( $date){
    $startDate = $date['start-date'];
    $endDate = $date['end-date'];
    $allDay = $date['all-day'];

    $date = "";
    if( $startDate != "")
    {

        // Start with this date.
        $date = date("g:i a", $startDate);

        // If all day, do not display a time.
        if( $allDay == "Yes" ){
            return "";
        }

        // if 12:00 pm, change to noon
        elseif( $date == "12:00 pm"){
            return "Noon";
        }
        else{
            // Change am/pm to a.m./p.m.
            $date = str_replace("am", "a.m.", $date);
            $date = str_replace("pm", "p.m.", $date);

            // format 7:00 to 7
            return str_replace(":00", "", $date);
        }
    }
    // Dummy return. it should never get here
    return $date;
}

// Returns the correct link to be used.
function convert_path_to_link( $event){
    if( $event["external-link"] != "" )
        return $event["external-link"];
    return "https://www.bethel.edu".$event['path'];
}

// Returns the correct shorthand for each event date
function get_month_shorthand_name( $month){
    $month = strtoupper($month);
    if( $month == "JULY")
        return "JULY";
    elseif( $month == "JUNE")
        return "JUNE";
    elseif( $month == "SEPTEMBER")
        return "SEPT";
    else{
        return substr($month, 0, 3);
    }
}

function get_timezone_shorthand( $date ){
    if( $date['outside-of-minnesota'] == 'Yes' )
        if( $date['time-zone'] == 'Hawaii-Aleutian Time')
            return 'HT';
        elseif( $date['time-zone'] == 'Alaska Time')
            return 'AT';
        elseif( $date['time-zone'] == 'Pacific Time')
            return 'PT';
        elseif( $date['time-zone'] == 'Mountain Time')
            return 'MT';
        elseif( $date['time-zone'] == 'Central Time')
            return 'CT';
        elseif( $date['time-zone'] == 'Eastern Time')
            return 'ET';
    return '';
}

?>


