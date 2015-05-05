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


//TODO unsure if still needed. general-cascade/feed_helper.php has kind of replaced this.
//TODO exact copies of code have been deleted, but I will leave the rest commented out
////////////////////////
// The Controller
////////////////////////
//function create_feed($feedType, $School, $Topic, $CAS, $CAPS, $GS, $SEM){
//    $feedHTMLArray = array();
//    if( $feedType == "Event Feed" ){
//        include_once "feed_events.php";
//        $feedHTMLArray = create_event_feed($School, $Topic, $CAS, $CAPS, $GS, $SEM);
//    }
//    elseif( $feedType == "News Article Feed" ){
//        include_once "feed_news_articles.php";
//        $feedHTMLArray = create_news_article_feed($School, $Topic, $CAS, $CAPS, $GS, $SEM);
//    }
//    elseif( $feedType == "News Archive" ){
//        include_once "news_archive.php";
//        $feedHTMLArray = create_archive();
//    }
//    return $feedHTMLArray;
//}
//
//
//// Get the corresponding page.
//function inspect_page($xml, $School, $Topic, $CAS, $CAPS, $GS, $SEM){
//    global $feedType;
//
//    if( $feedType == "Event Feed" )
//        return inspect_event_page( $xml, $School, $Topic, $CAS, $CAPS, $GS, $SEM);
//    elseif( $feedType == "News Article Feed" )
//        return inspect_news_article_page( $xml, $School, $Topic, $CAS, $CAPS, $GS, $SEM);
//    elseif( $feedType == "News Archive" ){
//        return inspect_news_archive_page($xml, $School, $Topic, $CAS, $CAPS, $GS, $SEM);
//    }
//}
//
//?>