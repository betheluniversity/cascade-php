<?php
/**
 * Created by PhpStorm.
 * User: cav28255
 * Date: 5/4/15
 * Time: 11:09 AM
 */

function traverse_folder($xml, $pages, $categories, $func){
    if(!$xml){
        return;
    }
    foreach ($xml->xpath("//system-page") as $child) {

        // Set the page data.=
        $page = $func($child, $categories);
        // Add to an event array.
        if( $page['display-on-feed'] ) {
            array_push($pages, $page);
        }
    }
    return $pages;
}

function get_xml($fileToLoad, $categories, $func){
    $xml = simplexml_load_file($fileToLoad);
    $pages = array();
    $pages = traverse_folder($xml, $pages, $categories, $func);
    return $pages;
}

// Create the Featured array.
// The 3rd index in each '$Option' is the html of the event.
//  ( 0-2 include the info of the Option. )
function create_featured_array($featuredOptions){
    $featured= array();

    foreach( $featuredOptions as $key=>$Option ){
        if( $Option[3] != null && $Option[3] != ""){
            array_push($featured, $Option[3]);
        }
    }
    return $featured;
}

function match_metadata_articles($xml, $categories, $options, $feedType){
    foreach( $categories as $category) {
        foreach ($xml->{'dynamic-metadata'} as $md) {

            $name = $md->name;

            foreach ($md->value as $value) {
                if ($value == "None" || $value == "none" || $value == "select" || $value == "Select") {
                    continue;
                }
                if ($feedType == 'event')
                    $value = htmlspecialchars($value);

                if (in_array($name, $options)) {
                    if (in_array($value, $category)) {
                        return true;
                    }
                }

            }
        }
    }
    return false;
}

//Sort an array
function sort_by_date( $array ){

    if( sizeof($array) != 0)
    {
        usort($array, 'sort_by_date_helper');
    }

    return array_reverse($array);
}

function sort_by_date_helper($a, $b)
{
    return strcmp($a["date-for-sorting"], $b["date-for-sorting"]);
}


