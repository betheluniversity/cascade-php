<?php
/**
 * Created by PhpStorm.
 * User: ces55739
 * Date: 6/27/18
 * Time: 3:55 PM
 */

include_once $_SERVER["DOCUMENT_ROOT"] . "/code/news/php/news_article_feed.php";
include_once $_SERVER["DOCUMENT_ROOT"] . "/code/php_helper_for_cascade.php";
include_once $_SERVER["DOCUMENT_ROOT"] . "/code/general-cascade/feed_helper.php";

function create_news_article_gallery_feed($categories){
    // set $DisplayImages and $DisplayTeaser to Yes, as it is used for the normal feeds - so we need to still set those
    global $DisplayImages;
    $DisplayImages = 'Yes';
    global $DisplayTeaser;
    $DisplayTeaser = 'Yes';


    // todo: assume older things are articles
    // todo: then pull the most recent 3 stories from that list
    // todo: then output the remaining X items


    // this is legacy code. It will be used for the archive and for any feed that includes old articles
    $arrayOfArticles = autoCache('get_xml', array($_SERVER["DOCUMENT_ROOT"] . "/_shared-content/xml/articles.xml", $categories, "inspect_news_article"));
    $arrayOfArticles = get_xml($_SERVER["DOCUMENT_ROOT"] . "/_shared-content/xml/articles.xml", $categories, "inspect_news_article");
    // This is the new version of news.
    $arrayOfNewsAndStories = autoCache('get_xml', array($_SERVER["DOCUMENT_ROOT"] . "/_shared-content/xml/news-and-stories.xml", $categories, "inspect_news_article"));
    $arrayOfNewsAndStories = get_xml($_SERVER["DOCUMENT_ROOT"] . "/_shared-content/xml/news-and-stories.xml", $categories, "inspect_news_article");
    $arrayOfNewsAndStories = sort_by_date($arrayOfNewsAndStories);

    $threeStories = array();
    foreach( $arrayOfNewsAndStories as $index => $newsAndStory){
        if( $newsAndStory['article-type'] == 'Story'){
            array_push($threeStories, $newsAndStory);
            unset($arrayOfNewsAndStories[$index]);

            // exit once there are 3
            if( sizeof($threeStories) >= 3)
                break;
        }
    }

    # todo: grab the most recent 3

    $arrayOfArticles = array_merge($arrayOfArticles, $arrayOfNewsAndStories);
    global $NumArticles;
    $sortedArticles = sort_by_date($arrayOfArticles);

    // Only grab the first X number of articles.
    $sortedArticles = array_slice($sortedArticles, 0, $NumArticles, true);

    $twig = makeTwigEnviron('/code/news/twig');
    $html = $twig->render('news_article_gallery_feed.html', array(
        'sortedArticles'     => $sortedArticles,
        'threeStories'     => $threeStories
    ));

    return $html;
}