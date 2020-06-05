<?php
/**
 * Created by PhpStorm.
 * User: cav28255
 * Date: 5/4/15
 * Time: 11:09 AM
 */

// Todo: make this work with blocks and pages
function traverse_folder($xml, $pages, $categories, $func){
    if(!$xml){
        return;
    }
    foreach ($xml->xpath("//system-page") as $child) {
        // Set the page data.=
        $page = $func($child, $categories);
        // Add to an event array.
        if( is_array($page) && isset($page['display-on-feed']) && $page['display-on-feed'] == 1) {
            array_push($pages, $page);
        }
    }
    return $pages;
}

// Todo: make this work with blocks and pages
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

function match_metadata_articles($xml, $categories, $options){
    foreach( $categories as $category) {
        foreach ($xml->{'dynamic-metadata'} as $md) {
            $name = $md->name;

            foreach ($md->value as $value) {
                if (strtolower($value) == "none" || strtolower($value) == "select") {
                    continue;
                }

                if (in_array($name, $options)) {
                    if (in_array($value, $category)) {
                        print_r($value. " " . $category. "|");

                        return true;
                    }
                }

            }
        }
    }
    return false;
}

//Sort an array
function sort_by_date( $array, $reverse = true){
    // if $array is not an array, return an empty array
    if( !is_array($array) )
        return array();

    if( sizeof($array) != 0)
    {
        // ran into a php bug here. used the following stackoverflow to solve it.
        // http://stackoverflow.com/questions/3235387/usort-array-was-modified-by-the-user-comparison-function
        @usort($array, 'sort_by_date_helper');
    }
    if( $reverse )
        return array_reverse($array);
    else
        return $array;
}

function sort_by_date_helper($a, $b)
{
    return strcmp($a["date-for-sorting"], $b["date-for-sorting"]);
}


