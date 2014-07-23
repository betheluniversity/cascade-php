<?php
/**
 * Created by PhpStorm.
 * User: ces55739
 * Date: 7/16/14
 * Time: 3:36 PM
 */

    // These are placeholders of the featured events.
    // They are globals to make it easier.
    $firstFeaturedEvent;
    $secondFeaturedEvent;
    $featuredEventOneOptions = $_GET["featuredEventOneOptions"];
    $featuredEventTwoOptions = $_GET["featuredEventTwoOptions"];

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
                }
            }
        }

        $xml = simplexml_load_file("/var/www/cms.pub/_shared-content/xml/events.xml");
        $events = array();
        $events = traverse_folder($xml, $events, $categories);
        return $events;

    }

    function traverse_folder($xml, $events, $categories){
        foreach ($xml->children() as $child) {

            $name = $child->getName();

            if ($name == 'system-folder'){
                $events = traverse_folder($child, $events, $categories);
            }elseif ($name == 'system-page'){
                // Set the page data.
                $event = inspect_page($child, $categories);

                // Add to an event array.
                if( $event['display-on-feed'] == "Yes")
                    array_push($events, $event);
            }
        }

        return $events;
    }

    // Gathers the information of an event page
    function inspect_page($xml, $categories){
        //echo "inspecting page";
        $page_info = array(
            "title" => $xml->title,
            "display-name" => $xml->{'display-name'},
            "published" => $xml->{'last-published-on'},
            "description" => $xml->{'description'},
            "path" => $xml->path,
            "date" => array(),
            "md" => array(),
            "html" => "",
            "display-on-feed" => "No",
            "external-link" => "",
            "image" => "",
        );

        $ds = $xml->{'system-data-structure'};

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
                        $page_info["display-on-feed"] = "Metadata Matches";
                    }
                }

            }
        }


        $page_info["external-link"] = $ds->{'link'};


        ///////////////////////////////////////////
        // Only include the first date after today.
        ///////////////////////////////////////////
        $dates = $ds->{'event-dates'};
        $displayableDate = get_displayable_date($page_info, $dates);
        $currentDate = time();

        // There are 259200 seconds in 3 days.
        // After 3 days of being on the calendar, do not display it anymore.
        if( sizeof( $displayableDate) != 0 && $displayableDate['start-date']+259200 > $currentDate )//&& $page_info["display-on-feed"] == "Metadata Matches")
        {
            $page_info["display-on-feed"] = "Yes";
            $page_info['date'] = $displayableDate;
        }
        ///////////////////////////////////////////

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



        $page_info['html'] = get_event_html($page_info);

        // Get the image.
        $page_info['image'] = $ds->{'image'}->path;

        global $featuredEventOneOptions;
        global $featuredEventTwoOptions;

        //Check if it is a featured Event.
        if( $page_info['path'] == $featuredEventOneOptions[0]){
            GLOBAL $firstFeaturedEvent;
            $firstFeaturedEvent = get_featured_event_html( $page_info, $featuredEventOneOptions);
        }
        if( $page_info['path'] == $featuredEventTwoOptions[0]){
            GLOBAL $secondFeaturedEvent;
            $secondFeaturedEvent = get_featured_event_html( $page_info, $featuredEventTwoOptions);
        }


        return $page_info;
    }

    // Returns the featured Event html.
    function get_featured_event_html($event, $featuredEventOptions){
        // Only display it if it has an image.
        if( $event['image'] != "" && $event['image'] != "/"){

            $html = '<div class="mt1 mb2 pa1" style="background: #f4f4f4">';
                $html .= '<div class="grid left false">';
                    $html .= '<div class="grid-cell  u-medium-1-2">';
                        $html .= '<div class="medium-grid-pad-1x">';
                            $html .= '<img src="//cdn1.bethel.edu/resize/unsafe/400x0/smart/http://staging.bethel.edu/'.$event['image'].'" class="image-replace" alt="'.$event['title'].'" data-src="//cdn1.bethel.edu/resize/unsafe/{width}x0/smart/http://staging.bethel.edu/'.$event['image'].'" width="400">';
                        $html .= '</div>';
                    $html .= '</div>';
                    $html .= '<div class="grid-cell  u-medium-1-2">';
                        $html .= '<div class="medium-grid-pad-1x">';

                            if( $event['title'] != "")
                            $html .= '<h3><a href="http://bethel.edu'.$event['path'].'">'.$event['title'].'</a></h3>';

                            if( $featuredEventOptions[2] == "No"){
                                $formattedDate = format_featured_event_date($event['date']);
                                if($formattedDate != "" )
                                $html .= "<p>".$formattedDate."</p>";
                            }

                            if( $featuredEventOptions[1] != "" )
                                $html .= '<p>'.$featuredEventOptions[1].'</p>';
                            elseif( $event['description'] != "")
                                $html .= '<p>'.$event['description'].'</p>';

                        $html .= '</div>';
                    $html .= '</div>';
                $html .= '</div>';
            $html .= '</div>';

        }
        else
            return "null";

        return $html;
    }

    // returns the html of the event.
    function get_event_html( $event){
        $html = '<div class="media-box  mv1">';
        $html .= '<p class="events-date-tag">';


        // Month
        $html .= get_month_shorthand_name(date("F", $event['date']['start-date']));

        // Day
        $html .= "<span>".date("d", $event['date']['start-date'])."</span>";

        // Year
        $html .= date("Y", $event['date']['start-date'])."</p>";
        $html .= '<div class="media-box-body">';
        // Title + Link
        $html .= '<h2 class="h5"><a href="'.convert_path_to_link($event).'">'.$event['title'].'</a></h2>';

        // Time + Location
        $date = format_fancy_event_date($event['date']);
        if($date != ""){
            if( $event['location'] != "")
            {
                $html .= '<p class="mb0">'.$date.' | '.$event['location'].'</p>';
            }
            else{
                $html .= '<p class="mb0">'.$date.'</p>';
            }
        }
        else{
            if( $event['location'] != "")
            {
                $html .= '<p class="mb0">'.$event['location'].'</p>';
            }
        }

        // Description
        $html .= '<p>'.$event['description'].'</p>';
        $html .= '</div></div>';
        $html .= $event["caleb-option"];



        return $html;
    }

    // Returns a Date that is formatted correctly for a Featured Event
    // Both $startdate and $endDate are timestamps
    function format_featured_event_date( $date ){
        $startDate = $date['start-date'];
        $endDate = $date['end-date'];
        $allDay = $date['all-day'];

        // Start with this date.
        $returnedDate = date("g:i a", $startDate);

        // If it spans multiple days, do not display a time.
        // if all day, do not display a time.
        if( date("m/d/Y", $startDate) != date("m/d/Y", $endDate) ){
            return date("F d, Y", $startDate)." - ".date("F d, Y", $endDate);
        }

        // if it is all day
        if( $allDay == "Yes" ){
            // return nothing?
            return "";
        }

        // if it has multiple dates.
        if( sizeof( $date) > 1){
            return "Various Dates";
        }

        // if it is normal.
        // if 12 pm, change to noon
        if( date("g:i", $startDate) == "12:00 pm"){
            $returnedDate = "noon";
        }
        else{
            // Change am/pm to a.m./p.m.
            $returnedDate = str_replace("am", "a.m.", $returnedDate);
            $returnedDate = str_replace("pm", "p.m.", $returnedDate);

            // format 7:00 to 7
            $returnedDate = str_replace(":00", "", $returnedDate);
        }
        return $returnedDate;
    }

    // Get the 1st date that we want to display it as.
    function get_displayable_date( $page_info, $dates ){
        $currentDate = time();
        $displayableDate = array();
        foreach ($dates as $date){
            $start_date = $date->{'start-date'} / 1000;
            $end_date = $date->{'end-date'} / 1000;
            $allDay = $date->{'all-day'}->{'value'};

            if( $currentDate < $start_date)
            {
                // If there is no date yet or if a different date occurs earlier.
                if( sizeof($displayableDate) == 0 || $displayableDate['start-date'] > $start_date){
                    $displayableDate['start-date'] = $start_date;
                    $displayableDate['end-date'] = $end_date;
                    $displayableDate['all-day'] = $allDay;
                }
            }
        }
        return $displayableDate;
    }

    // Returns a formatted date correctly for an event.
    // Both $startdate and $endDate are timestamps
    function format_fancy_event_date( $date){
        $startDate = $date['start-date'];
        $endDate = $date['end-date'];
        $allDay = $date['all-day'];

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

    // Sort the events
    function sort_events( $events ){
        function cmpi($a, $b)
        {
            return strcmp($a["date"]['start-date'], $b["date"]['start-date']);
        }
        usort($events, 'cmpi');

        return $events;
    }

    // Create the Featured Events.
    function create_featured_event_array(){
        $featuredEvents = array();
        GLOBAL $firstFeaturedEvent;
        GLOBAL $secondFeaturedEvent;
        array_push($featuredEvents, $firstFeaturedEvent, $secondFeaturedEvent);
        return $featuredEvents;
    }

    // Create the Event Feed events.
    function create_event_feed(){
        // Currently this is being passed an array of "titles".
        $arrayOfEvents = get_event_xml();

        // Once you get the events, Sort them.
        $sortedEvents = sort_events($arrayOfEvents);

        // Then make them into the correct form and send um over.
        $finalArray = array();
        foreach( $sortedEvents as $event){
            array_push($finalArray, $event['html']);
        }

        // Only grab the first X number of events.
        $numEventsToFind = $_GET["numEvents"];
        $finalArray = array_slice($finalArray, 0, $numEventsToFind, true);

        // Create the featured Events.
        $featuredEvents = create_featured_event_array();


        $combinedArray = array($featuredEvents, $finalArray);
        return json_encode($combinedArray);
    }

    // The starter that returns a 2D array of arrays (Featured Events, Events Feed Events).
    echo create_event_feed();

?>