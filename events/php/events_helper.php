<?php
/**
 * Created by PhpStorm.
 * User: ejc84332
 * Date: 8/28/14
 * Time: 10:23 AM
 */


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