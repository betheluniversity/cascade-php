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

function create_news_article_gallery_feed($categories, $galleryStyle){
    // set $DisplayImages and $DisplayTeaser to Yes, as it is used for the normal feeds - so we need to still set those
    global $DisplayImages;
    $DisplayImages = 'Yes';
    global $DisplayTeaser;
    $DisplayTeaser = 'Yes';

    // this is legacy code. It will be used for the archive and for any feed that includes old articles
//    $arrayOfArticles = autoCache('get_xml', array($_SERVER["DOCUMENT_ROOT"] . "/_shared-content/xml/articles.xml", $categories, "inspect_news_article"), 10);
    $arrayOfArticles = get_xml($_SERVER["DOCUMENT_ROOT"] . "/_shared-content/xml/articles.xml", $categories, "inspect_news_article");

    // This is the new version of news.
//    $arrayOfNewsAndStories = autoCache('get_xml', array($_SERVER["DOCUMENT_ROOT"] . "/_shared-content/xml/news-and-stories.xml", $categories, "inspect_news_article"), 10);
    $arrayOfNewsAndStories = get_xml($_SERVER["DOCUMENT_ROOT"] . "/_shared-content/xml/news-and-stories.xml", $categories, "inspect_news_article");
    $arrayOfNewsAndStories = sort_by_date($arrayOfNewsAndStories);

    $threeStories = array();
    foreach( $arrayOfNewsAndStories as $index => $newsAndStory){
        if( $newsAndStory['article-type'] == 'Story'){
            $newsAndStory['gallery-image'] = srcset($newsAndStory['image-path'], false, true, '', $newsAndStory['title']);
            array_push($threeStories, $newsAndStory);
            unset($arrayOfNewsAndStories[$index]);

            // exit once there are 3
            if( sizeof($threeStories) >= 3)
                break;
        }
    }

    $arrayOfArticles = array_merge($arrayOfArticles, $arrayOfNewsAndStories);
    global $NumArticles;
    $sortedArticles = sort_by_date($arrayOfArticles);

    // Only grab the first X number of articles.
    $sortedArticles = array_slice($sortedArticles, 0, $NumArticles, true);

    echo "<script>console.log( 'Debug Objects: " . $galleryStyle . "' );</script>";

    $renderFile = "no_feature_news_gallery.html";
    if( $galleryStyle == "Feature Top") {
        $renderFile = "feature_top_news_gallery.html";
    } else if( $galleryStyle == "Feature Left") {
        $renderFile = "feature_left_news_gallery.html";
    }

    $twig = makeTwigEnviron('/code/news/twig');
    $html = $twig->render($renderFile, array(
        'sortedArticles'     => $sortedArticles,
        'threeStories'     => $threeStories
    ));

    return $html;
}
