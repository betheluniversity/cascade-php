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
    // index 3: final html of the event. Start out = "".

    $NumEvents;
    $Heading;
    $HideWhenNone;
    $AddFeaturedEvents;
    $StartDate;
    $EndDate;

    //button
    $addbutton;
    $moreeventslink;
    $buttontext;

    /////////////////////////////////////////////////////////////////////

    // Create the Event Feed events.
    function create_event_feed(){
        // EVENT FEED
        global $eventFeedCategories;
        $categories = $eventFeedCategories;

        if( strstr(getcwd(), "staging/public") ){
            $arrayOfEvents = get_xml("/var/www/staging/public/_shared-content/xml/events.xml", $categories);
        }
        else{ //if( strstr(getcwd(), "cms.pub") )
            $arrayOfEvents = get_xml("/var/www/cms.pub/_shared-content/xml/events.xml", $categories);
        }

        $sortedEvents = sort_events($arrayOfEvents);

        // Only grab the first X number of events.
        global $NumEvents;
        $numEventsToFind = $NumEvents;
        $sortedEvents = array_slice($sortedEvents, 0, $numEventsToFind, true);

        $eventsArray = array();
        foreach( $sortedEvents as $event){
            array_push($eventsArray, $event['html']);
        }

        // HEADING
        global $Heading;
        $heading = array("<h2>".$Heading."</h2>");

        // FEATURED EVENTS
        $featuredEvents = create_featured_event_array();

        // BUTTON
        global $addbutton;
        global $moreeventslink;
        global $buttontext;
        $buttonHTML = array("");
        if( $addbutton == "Yes")
        {
            array_push( $buttonHTML, '<a id="event-feed-button" class="btn center" href="http://www.bethel.edu/' . $moreeventslink . '">' . $buttontext . '</a>');
        }

        // Hide if None
        global $HideWhenNone;
        if( sizeOf( $eventsArray) == 0){
            if( $HideWhenNone == "Yes"){
                $heading = array();
                $eventsArray = array();
            }
            else{
                $eventsArray = array("<p>No upcoming events.</p>");
            }
        }

        $combinedArray = array_merge($featuredEvents, $heading, $eventsArray, $buttonHTML);
        return $combinedArray;
    }

    // Create the Featured Events.
    function create_featured_event_array(){
        $featuredEvents = array();

        global $featuredEventOptions;

        foreach( $featuredEventOptions as $key=>$featuredEvent ){
            if( $featuredEvent[3] != "null" && $featuredEvent[3] != ""){
                array_push($featuredEvents, $featuredEvent[3]);
            }
        }
        return $featuredEvents;
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
                        return "Metadata Matches";
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
            "date-for-sorting" => "",
            "date" => array(),
            "md" => array(),
            "html" => "",
            "display-on-feed" => "No",
            "external-link" => "",
            "image" => "",
            "has-multiple-dates" => "No",
        );
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
            if( sizeof( $dates) > 3){
                $page_info['has-multiple-dates'] = "Yes";
            }
            $displayableDate = get_displayable_date_event($page_info, $dates);

            if( $displayableDate['start-date'] == ""){
                $page_info['display-on-feed'] = "No";
                return $page_info;
            }

            $page_info['date'] = $displayableDate;
            $page_info['date-for-sorting'] = $displayableDate['start-date'];
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

            // Get the image.
            $page_info['image'] = $ds->{'image'}->path;

            ///////////////////////////////////////////
            // Display
            ///////////////////////////////////////////
            $page_info['display-on-feed'] = display_on_feed_events($page_info, $dates);
            $page_info['html'] = get_event_html($page_info);



            // Featured Events
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
        }

        return $page_info;
    }

    function display_on_feed_events($page_info, $dates){
        $currentDate = time();
        // There are 259200 seconds in 3 days.
        // After 3 days of being on the calendar, do not display it anymore.
        if( $page_info['date']['start-date'] != "" && $page_info['date']['start-date']+259200 > $currentDate && $page_info["display-on-feed"] == "Metadata Matches")
        {
            global $StartDate;
            global $EndDate;

            $modifiedStartDate = $StartDate / 1000;
            $modifiedEndDate = $EndDate / 1000;
            $latestDate = get_latest_date($page_info, $dates);

            //Check if it falls between the given range.
            if( $StartDate != "" && $EndDate != "" ){
                if( $latestDate != ""){
                    if( $modifiedStartDate < $page_info['date']['start-date'] && $latestDate < $modifiedEndDate ){
                        return "Yes";
                    }
                }
            }
            elseif( $StartDate != ""){
                if( $latestDate != ""){
                    if( $modifiedStartDate < $page_info['date']['start-date']){
                        return "Yes";
                    }
                }
            }
            elseif( $EndDate != ""){
                if( $latestDate != ""){
                    if( $latestDate < $modifiedEndDate ){
                        return "Yes";
                    }
                }
            }
            else
            {
                return "Yes";
            }
        }
        return "No";
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
                $html .= '<h2 class="h5"><a href="'.convert_path_to_link($event).'">'.$event['title'].'</a></h2>';

            if( $featuredEventOptions[2] == "No"){
                if( $event['has-multiple-dates'] == "Yes")
                {
                    $html .= "<p>Various Dates</p>";
                }
                else{
                    $formattedDate = format_featured_event_date($event['date']);
                    $html .= "<p>".$formattedDate."</p>";
                }
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

        if( $event['date']['start-date'] != "")
        {
            // Month
            $html .= get_month_shorthand_name(date("F", $event['date']['start-date']));

            // Day
            $html .= "<span>".date("d", $event['date']['start-date'])."</span>";

            // Year
            $html .= date("Y", $event['date']['start-date'])."</p>";
            $html .= '<div class="media-box-body">';
        }
        // Title + Link
        $html .= '<h2 class="h5"><a href="'.convert_path_to_link($event).'">'.$event['title'].'</a></h2>';

        // Time + Location
        if( $event['date'] )
            $date = format_fancy_event_date($event['date']);
        else
            $date = "";
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



        return $html;
    }

    // Returns a Date that is formatted correctly for a Featured Event
    // Both $startdate and $endDate are timestamps
    function format_featured_event_date( $date ){
        $startDate = $date['start-date'];
        $endDate = $date['end-date'];
        $allDay = $date['all-day'];

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

    // Get the date that we want to display it as.
    function get_displayable_date_event( $page_info, $dates ){
        $currentDate = time() - 43200; // (12 hours) This is to keep events on the calendar for an extra 12 hours.
        $displayableDate = array();
        $displayableDate['start-date'] = "";
        $displayableDate['end-date'] = "";
        $displayableDate['all-day'] = "";
        foreach ($dates as $date){
            $start_date = $date->{'start-date'} / 1000;
            $end_date = $date->{'end-date'} / 1000;
            $allDay = $date->{'all-day'}->{'value'};

            if( $currentDate < $start_date)
            {
                // If there is no date yet or if a different date occurs earlier.
                if( $displayableDate['start-date'] == "" || $displayableDate['start-date'] > $start_date){
                    $displayableDate['start-date'] = $start_date;
                    $displayableDate['end-date'] = $end_date;
                    $displayableDate['all-day'] = $allDay;
                }
            }
            elseif( $currentDate < $end_date)
            {
                if( $displayableDate['end-date'] == "" || $displayableDate['end-date'] > $end_date){
                    $displayableDate['start-date'] = $start_date;
                    $displayableDate['end-date'] = $end_date;
                    $displayableDate['all-day'] = $allDay;
                }
            }
        }
        return $displayableDate;
    }

    // This is only used for finding the latest date after today of the event.
    function get_latest_date( $page_info, $dates ){
        $currentDate = time();
        $lastDate = ""; // a default if there is no date.
        foreach ($dates as $date){
            $end_date = $date->{'end-date'} / 1000;

            if( $currentDate < $end_date)
            {
                // If there is no date yet or if a different date occurs earlier.
                if( $lastDate < $end_date){
                    $lastDate = $end_date;
                }
            }
        }
        return $lastDate;
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