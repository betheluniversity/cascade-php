<?php
/**
 * Created by PhpStorm.
 * User: ces55739
 * Date: 6/27/18
 * Time: 3:55 PM
 */

function create_news_article_gallery_feed($categories){
    // todo: assume older things are articles
    // todo: then pull the most recent 3 stories from that list
    // todo: then output the remaining X items

    include_once $_SERVER["DOCUMENT_ROOT"] . "/code/php_helper_for_cascade.php";
    include_once $_SERVER["DOCUMENT_ROOT"] . "/code/general-cascade/feed_helper.php";

    // this is legacy code. It will be used for the archive and for any feed that includes old articles
    $arrayOfArticles = autoCache('get_xml', array($_SERVER["DOCUMENT_ROOT"] . "/_shared-content/xml/articles.xml", $categories, "inspect_news_article"));
    // This is the new version of news.
    $arrayOfNewsAndStories = autoCache('get_xml', array($_SERVER["DOCUMENT_ROOT"] . "/_shared-content/xml/news-and-stories.xml", $categories, "inspect_news_article"));

    # todo: grab the most recent 3

    $arrayOfArticles = array_merge($arrayOfArticles, $arrayOfNewsAndStories);
    global $NumArticles;
    // echo 'feed_news_sorted_'.$NumArticles;
    $sortedArticles = sort_by_date($arrayOfArticles);

    // Only grab the first X number of articles.
    $sortedArticles = array_slice($sortedArticles, 0, $NumArticles, true);




    return 'got here';
}