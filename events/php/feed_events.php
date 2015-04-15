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

    $destinationName;

    /////////////////////////////////////////////////////////////////////






    // Create the Event Feed events.
    function create_event_feed(){
        global $destinationName;
        // Staging Site
        if( strstr(getcwd(), "staging/public") ){
            $destinationName = "staging";
        }
        else{ // Live site.
            $destinationName = "www";
        }

        // include helper
        include_once $_SERVER["DOCUMENT_ROOT"] . "/code/php_helper_for_cascade.php";

        // EVENT FEED
        global $eventFeedCategories;
        $categories = $eventFeedCategories;

        global $destinationName;
        // Dynamically get the correct xml.
        if( $destinationName == "staging/public" ){
//            $arrayOfEvents = autoCache("get_xml", array($_SERVER["DOCUMENT_ROOT"] . "/_shared-content/xml/events.xml", $categories),  'feed_events_staging');

        }
        else{
            $arrayOfEvents = get_xml($_SERVER["DOCUMENT_ROOT"] . "/_shared-content/xml/events.xml", $categories);
//            $arrayOfEvents = autoCache("get_xml", array($_SERVER["DOCUMENT_ROOT"] . "/_shared-content/xml/events.xml", $categories), 'feed_events_ww');
        }



        //////////////////////////////////////////
        // Turn all dates into individual events
        /////////////////////////////////////////
        $eventArrayWithMultipleEvents = array();
        global $PriorToToday;
        // foreach event.
        foreach( $arrayOfEvents as $event)
        {
            $dates = $event['dates'];
            // foreach date
            foreach($dates as $date)
            {
                $newDate['start-date'] = $date->{'start-date'} / 1000;
                $newDate['end-date'] = $date->{'end-date'} / 1000;
                $newDate['all-day'] = $date->{'all-day'}->{'value'};


                // This will hide all events prior to today.
                if( time() > $date->{'end-date'} / 1000 && $PriorToToday != 'Show')
                    continue;

                $newEvent = $event;
                $newEvent['date'] = $newDate;
                $newEvent['date-for-sorting'] = $date->{'start-date'} / 1000;

                $newEvent['html'] = get_event_html($newEvent);

                if (display_on_feed_events($newEvent))
                {
                    array_push($eventArrayWithMultipleEvents, $newEvent);
                }



                // art exhibits and theatre productions only add 1 date.
                if(check_if_art_or_theatre($newEvent) )
                    break;
            }
        }

        /////////////////////////////////////////

        $sortedEvents = array_reverse(sort_array($eventArrayWithMultipleEvents));
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
        $featuredEvents = create_featured_event_array();

        $numEvents = sizeof( $eventArray);

        // Print No upcoming events if there are no events.
        if( sizeOf( $eventArray) == 0){
            $eventsArray = array("<p>No upcoming events.</p>");
        }
        $combinedArray = array($featuredEvents, $eventArray, $numEvents );
        return $combinedArray;
    }

    // Create the Featured Events.
    // The 3rd index in each '$featuredEvent' is the html of the event.
    //  ( 0-2 include the info of the event. )
    function create_featured_event_array(){
        $featuredEvents = array();

        global $featuredEventOptions;

        foreach( $featuredEventOptions as $key=>$featuredEvent ){
            if( $featuredEvent[3] != null && $featuredEvent[3] != ""){
                array_push($featuredEvents, $featuredEvent[3]);
            }
        }
        return $featuredEvents;
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

                    if (   'Art Galleries' == $value
                        || 'Johnson Gallery' == $value
                        || 'Olson Gallery' == $value
                        || 'Theatre' == $value
                        )
                    {
                        return true;
                    }
                }

            }
        }
        return false;
    }

    function match_metadata_events($xml, $categories){
        foreach ($xml->{'dynamic-metadata'} as $md){

            $name = $md->name;

            $options = array('general', 'offices', 'academic-dates', 'cas-departments', 'internal');

            foreach($md->value as $value ){
                if($value == "None" || $value == "none"){
                    continue;
                }

                if (in_array($name,$options)){
                    //Is this a calendar category?
                    if (in_array($value, $categories)){
                        return "Yes";
                    }
                }

            }
        }
        return "No";
    }

    // Gathers the information of an event page
    function inspect_event_page($xml, $categories){
        //echo "inspecting page";
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
            "display-on-feed" => "No",
            "external-link" => "",
            "image" => "",
            "xml" => $xml,
        );
        if( strpos($page_info['path'],"_testing") !== false)
            return "";

        $ds = $xml->{'system-data-structure'};

        $page_info['display-on-feed'] = match_metadata_events($xml, $categories);

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
        $modifiedStartDate = $StartDate / 1000;
        $modifiedEndDate = $EndDate / 1000;

        //Check if the event falls between the given range.
        if( $StartDate != "" && $EndDate != "" ){
            // If the end date of the page comes after the start
            if( $modifiedStartDate < $page_info['date']['end-date'] && $page_info['date']['start-date'] < $modifiedEndDate ){
                return true;
            }
        }
        elseif( $StartDate != ""){
            if( $modifiedStartDate < $page_info['date']['end-date']){
                return true;
            }
        }
        elseif( $EndDate != ""){
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


        // Only display it if it has an image.
        if( $event['image'] != "" && $event['image'] != "/"){

            global $destinationName;
            $twig = makeTwigEnviron('/code/events/twig');
            $twig->addFilter(new Twig_SimpleFilter('convert_path_to_link','convert_path_to_link'));
            $twig->addFilter(new Twig_SimpleFilter('format_featured_event_date','format_featured_event_date'));
            $html = $twig->render('feed_events.html', array(
                'destinationName' => $destinationName,
                'thumborURL'=> thumborURL($event['image'], 400, false, false),
                'event' => $event,
                'featuredEventOptions' => $featuredEventOptions,
                'dates' => $dates,
                'firstDate' => $firstDate));

        }
        else
            return null;

        return $html;
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
        $startDate = $date->{'start-date'} / 1000;
        $endDate = $date->{'end-date'} / 1000;
        $allDay = $date->{'all-day'};

        // If it spans multiple days, do not display a time.
        // if all day, do not display a time.
        if( date("m/d/Y", $startDate) != date("m/d/Y", $endDate) ){
            return date("F d, Y", $startDate)." - ".date("F d, Y", $endDate);
        }

        // if it is all day
        if( $allDay == "Yes" ){
            return date("F d, Y", $startDate);
        }

        // if it is normal.
        // if 12 pm, change to noon
        if( date("g:i", $startDate) == "12:00 pm"){
            return date("F d, Y |", $startDate)." noon";
        }
        else{
            $returnedDate = date("F d, Y | g:i a", $startDate);
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

            // if 12 pm, change to noon
            elseif( date("g:i", $startDate) == "12:00 pm"){
                return "noon";
            }
            else{
                // Change am/pm to a.m./p.m.
                $date = str_replace("am", "a.m.", $date);
                $date = str_replace("pm", "p.m.", $date);

                // format 7:00 to 7
                return str_replace(":00", "", $date);
            }
        }
        // Dummy return.
        return $date;
    }

    // Returns the correct link to be used.
    function convert_path_to_link( $event){
        if( $event["external-link"] != "" )
            return $event["external-link"];
        return "http://bethel.edu".$event['path'];
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

?>
