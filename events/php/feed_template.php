<?php
/**
 * Created by PhpStorm.
 * User: ces55739
 * Date: 7/24/14
 * Time: 2:15 PM
 */

///////////////////////////////////////////////////////////////////////////////
/////////////////////////// FEED TEMPLATE /////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////
// This is a template for the feed_controller.php

// Use 'feed_events.php' as an example
///////////////////////////////////////////////////////////////////////////////



    // returns an array of html elements.
    function create_example_feed(){
        $array = array();
        // EXAMPLE CODE

        //global $feedCategories;                                                                           // get global categories.
        //$categories = $feedCategories;
        //$arrayOfEvents = get_event_xml("/var/www/cms.pub/_shared-content/xml/events.xml", $categories);   // get Events
        //$array = sort_events($arrayOfEvents);                                                             // sort

        return $array;
    }

    ////////////////////////////////////////////////////////////////////////////////
    // Returns the information of the page.
    ////////////////////////////////////////////////////////////////////////////////
    // Make sure to set the 'html' to what you want to display.
    // Make sure to set the 'date-for-sorting' to sort the dates. This is a timestamp.
    // Set 'display-on-feed' = 'Yes' if you want to display the event. Else, set to 'No'
    ////////////////////////////////////////////////////////////////////////////////
    function inspect_example_page($xml, $categories){
        $page_info = array(
            "title" => $xml->title,
            "display-name" => $xml->{'display-name'},
            "published" => $xml->{'last-published-on'},
            "description" => $xml->{'description'},
            "path" => $xml->path,
            "date-for-sorting" => "",       //timestamp.
            "md" => array(),
            "html" => "",
            "display-on-feed" => "No",
        );

        $ds = $xml->{'system-data-structure'};
        $page_info['display-on-feed'] = match_metadata($xml, $categories);

        // To get the correct definition path.
        $dataDefinition = $ds['definition-path'];
        /////////////////// Write Code Here //////////////////////
        if( $dataDefinition == "ENTER DATA DEFINITION HERE")
        {




        }
        //////////////////////////////////////////////////////////

        return $page_info;
    }

?>