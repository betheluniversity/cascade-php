<?php

// Values are passed in from Cascade
$numPosts;
$includeTitle;
$feedTitle;
$categories;
$metadata;
$linkType;
$linkTest;
$includeBlogLink;
$customLinkText;


//TODO Move to feed_helper later (ACH)
function get_blog_rss_xml($fileToLoad){
    echo "in get_blog_rss_xml</br>";
    $feed = file_get_contents($fileToLoad);
    $xml = simplexml_load_string($feed);
    if(!$xml){
        echo "returning due to !xml</br>";
        return;
    }
//    $pages = array();
//    $func = "inspect_news_article";
//
    $pages = traverse_blog_rss($xml);
    echo "survived traverse_blog_rss</br>";
//    return $pages;
}

function traverse_blog_as_json($xml)
{
    $xmlAsJson = json_encode($xml);
    $xmlArray = json_decode($xmlAsJson, TRUE);
    print_r($xmlArray);
}


function traverse_blog_rss($xml){
    if (!$xml) {
        echo "Cannot parse invalid xml</br>";
        return;
    }

    echo $xml->channel->item[0]->title . "</br>";
    echo $xml->channel->item[1]->title . "</br>";

    foreach ($xml->xpath("//item") as $item){
        echo $item->title;
        echo "</br>!!@_</br>";
    }

    //$linkToMore = $xml->channel->link;
    echo "done with loop </br>";

    traverse_blog_as_json($xml);
}

function create_news_article_feed(){
    $feed = autoCache("create_news_article_feed_logic");
    return $feed;
}

// returns an array of html elements.
function create_news_article_feed_logic(){
    include_once $_SERVER["DOCUMENT_ROOT"] . "/code/php_helper_for_cascade.php";
    include_once $_SERVER["DOCUMENT_ROOT"] . "/code/general-cascade/feed_helper.php";

    $xml = file_get_contents($_SERVER["DOCUMENT_ROOT"] . "/_testing/anna-h/blog/_feeds/blog-articles-xml.xml");
    $xmlToJson = json_encode($xml);
    $jsonToArray = json_decode($xmlToJson, TRUE);

    return $jsonToArray;
}

// Returns a formatted version of the date.
function format_featured_date_news_article( $date)
{
    $date = $date/1000;
    $formattedDate = date("F d, Y | g:i a", $date);

    // Change am/pm to a.m./p.m.
    $formattedDate = str_replace("am", "a.m.", $formattedDate);
    $formattedDate = str_replace("pm", "p.m.", $formattedDate);

    // format 7:00 to 7
    $formattedDate = str_replace(":00", "", $formattedDate);
    $formattedDate = str_replace("12 p.m.", "Noon", $formattedDate);
    $formattedDate = str_replace("12 a.m", "midnight", $formattedDate);
    return $formattedDate;
}


?>
