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


    include_once $_SERVER["DOCUMENT_ROOT"] . "/code/general-cascade/event_feed.php";



    // Create the Event Feed events.
    function create_event_feed($School, $Topic, $CAS, $CAPS, $GS, $SEM){
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

        global $destinationName;
        // Dynamically get the correct xml.
        if( $destinationName == "staging/public" ){
//            $arrayOfEvents = autoCache("get_xml", array($_SERVER["DOCUMENT_ROOT"] . "/_shared-content/xml/events.xml", $categories),  'feed_events_staging',true,20);
            $arrayOfEvents = get_xml($_SERVER["DOCUMENT_ROOT"] . "/_shared-content/xml/events.xml", $School, $Topic, $CAS, $CAPS, $GS, $SEM);
        }
        else{
            $arrayOfEvents = get_xml($_SERVER["DOCUMENT_ROOT"] . "/_shared-content/xml/events.xml", $School, $Topic, $CAS, $CAPS, $GS, $SEM);
//            $arrayOfEvents = autoCache("get_xml", array($_SERVER["DOCUMENT_ROOT"] . "/_shared-content/xml/events.xml", $School, $Topic, $CAS, $CAPS, $GS, $SEM), 'feed_events_www_test',1);
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

        $sortedEvents = array_reverse(sort_by_date($eventArrayWithMultipleEvents));
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
            $eventsArray = array("<p>No upcoming events.</p>");
        }
        $combinedArray = array($featuredEvents, $eventArray, $numEvents );
        return $combinedArray;
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
                        return true;
                    }
                }

            }
        }
        return false;
    }

    // Gathers the information of an event page
    function inspect_event_page($xml, $General, $Offices, $AcademicDates, $CAS, $CAPS, $GS, $SEM, $Internal){

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
            "display-on-feed" => false,
            "external-link" => "",
            "image" => "",
            "xml" => $xml,
        );
        if( strpos($page_info['path'],"_testing") !== false)
            return "";

        $ds = $xml->{'system-data-structure'};

//        $page_info['display-on-feed'] = match_metadata_events($xml);
        $page_info['display-on-feed'] = false;

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

?>
