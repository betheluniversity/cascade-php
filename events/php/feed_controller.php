<?php
/**
 * Created by PhpStorm.
 * User: ces55739
 * Date: 7/16/14
 * Time: 3:36 PM
 */

////////////////////////
// The Controller
////////////////////////
function create_feed($feedType){
    $feedHTMLArray = array();
    if( $feedType == "Event Feed" ){
        include_once "feed_events.php";
        $feedHTMLArray = create_event_feed();
    }
    elseif( $feedType == "News Article Feed" ){
        include_once "feed_news_articles.php";
        $feedHTMLArray = create_news_article_feed();
    }
    elseif( $feedType == "News Archive" ){
        include_once "news_archive.php";
        $feedHTMLArray = create_archive();
    }

    return $feedHTMLArray;
}

// Sort an array
function sort_array( $array ){

    if( sizeof($array) != 0)
    {
        usort($array, 'sort_by_date');
    }

    return $array;
}

function sort_by_date($a, $b)
{
    return strcmp($a["date-for-sorting"], $b["date-for-sorting"]);
}

function get_xml($fileToLoad, $categories){
    $xml = simplexml_load_file($fileToLoad);
    $pages = array();
    $pages = traverse_folder($xml, $pages, $categories);
    return $pages;
}

function traverse_folder($xml, $pages, $categories){
    foreach ($xml->children() as $child) {

        $name = $child->getName();

        if ($name == 'system-folder'){
            $pages = traverse_folder($child, $pages, $categories);
        }elseif ($name == 'system-page'){
            // Set the page data.
            $page = inspect_page($child, $categories);

            // Add to an event array.
            if( $page['display-on-feed'] == "Yes")
                array_push($pages, $page);
        }
    }

    return $pages;
}

// Get the corresponding page.
function inspect_page($xml, $categories){
    global $feedType;

    if( $feedType == "Event Feed" )
        return inspect_event_page( $xml, $categories);
    elseif( $feedType == "News Article Feed" )
        return inspect_news_article_page( $xml, $categories);
    elseif( $feedType == "News Archive" ){
        include_once "feed_news_articles.php";
        return inspect_news_article_page( $xml, $categories);
    }
}

?>