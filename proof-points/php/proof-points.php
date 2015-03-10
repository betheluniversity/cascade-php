<?php
/**
 * Created by PhpStorm.
 * User: ces55739
 * Date: 3/9/15
 * Time: 10:36 AM
 */

/*
    1) create render proof point function
    2) create a get_proof_points( $numProofPoints )
        a) Pull ALL proof points\
        b) return X of them
    3) add to the /code/general-cascade/carousel-viewer.php file to allow for proof-point carousels.

    // Page metadata
    $School = array();
    $Topic = array();
    $CAS = array();
    $CAPS = array();
    $GS = array();
    $SEM = array();
 */


    function test($numProofPoints){
        echo "test";
        echo "<h2>Test</h2>";
        get_proof_point_xml();
    }

    function get_proof_point_xml(){
        $xml = simplexml_load_file("/var/www/cms.pub/_shared-content/xml/proof-points.xml");
        $proof_points = $xml->xpath("//system-block");

        // For each proof point
        foreach($proof_points as $proof_point){
            // For each items or "child" elements
            foreach( $proof_point as $child){
                if($child->getName() == "dynamic-metadata"){
                    // list of metadata
                    
                }
            }
        }
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
                }
            }
        }

        $xml = simplexml_load_file("/var/www/cms.pub/_shared-content/xml/events.xml");
        $event_pages = $xml->xpath("//system-page[system-data-structure[@definition-path='Event']]");
        $dates = array();
        foreach($event_pages as $child ){
            $page_data = inspect_page($child, $categories);
            add_event_to_array($dates, $page_data);
        }
        return $dates;
    }