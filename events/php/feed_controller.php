<?php
/**
 * Created by PhpStorm.
 * User: ces55739
 * Date: 7/16/14
 * Time: 3:36 PM
 */

// This file should be re-written to become a helper file.
// Event feeds, news feeds, and news archives should be calling their files directly.
// (then the files can be moved to the appropriate folders... news-feed => news folder.)


////////////////////////
// The Controller
////////////////////////
function create_feed($feedType, $School, $Topic, $CAS, $CAPS, $GS, $SEM){
    $feedHTMLArray = array();
    if( $feedType == "Event Feed" ){
        include_once "feed_events.php";
        $feedHTMLArray = create_event_feed($School, $Topic, $CAS, $CAPS, $GS, $SEM);
    }
    elseif( $feedType == "News Article Feed" ){
        include_once "feed_news_articles.php";
        $feedHTMLArray = create_news_article_feed($School, $Topic, $CAS, $CAPS, $GS, $SEM);
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

    return array_reverse($array);
}

function sort_by_date($a, $b)
{
    return strcmp($a["date-for-sorting"], $b["date-for-sorting"]);
}

function get_xml($fileToLoad, $School, $Topic, $CAS, $CAPS, $GS, $SEM){
    $xml = simplexml_load_file($fileToLoad);
    $pages = array();
    $pages = traverse_folder($xml, $pages, $School, $Topic, $CAS, $CAPS, $GS, $SEM);
    return $pages;
}

function traverse_folder($xml, $pages, $School, $Topic, $CAS, $CAPS, $GS, $SEM){
    if(!$xml){
        return;
    }
    foreach ($xml->children() as $child) {

        $name = $child->getName();

        if ($name == 'system-folder'){
            $pages = traverse_folder($child, $pages, $School, $Topic, $CAS, $CAPS, $GS, $SEM);
        }elseif ($name == 'system-page'){
            // Set the page data.
            $page = inspect_page($child, $School, $Topic, $CAS, $CAPS, $GS, $SEM);

            // Add to an event array.
            if( $page['display-on-feed'] ) {
                array_push($pages, $page);
            }
        }
    }

    return $pages;
}

// Get the corresponding page.
function inspect_page($xml, $School, $Topic, $CAS, $CAPS, $GS, $SEM){
    global $feedType;

    if( $feedType == "News Article Feed" )
        return inspect_news_article_page( $xml, $School, $Topic, $CAS, $CAPS, $GS, $SEM);
    elseif( $feedType == "News Archive" ){
        return inspect_news_archive_page($xml, $School, $Topic, $CAS, $CAPS, $GS, $SEM);
    }
}

?>