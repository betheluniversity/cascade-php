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
include_once $_SERVER["DOCUMENT_ROOT"] . "/code/general-cascade/macros.php";
require $_SERVER["DOCUMENT_ROOT"] . '/code/vendor/autoload.php';


function create_news_article_gallery_feed($categories, $galleryStyle, $myBethel, $newsOrStories = "Stories"){
    // set $DisplayImages and $DisplayTeaser to Yes, as it is used for the normal feeds - so we need to still set those
    global $DisplayImages;
    $DisplayImages = 'Yes';
    global $DisplayTeaser;
    $DisplayTeaser = 'Yes';

    // grab the global variable so we don't use stories that have already been used
    if( !array_key_exists('stories-already-used', $GLOBALS) ){
        $GLOBALS['stories-already-used'] = array();
    }

    // this is legacy code. It will be used for the archive and for any feed that includes old articles
    // TODO: currently the cached version is broken. It caches a BAD value.
    $arrayOfArticles = autoCache('get_xml', array($_SERVER["DOCUMENT_ROOT"] . "/_shared-content/xml/articles.xml", $categories, "inspect_news_article"));
//    $arrayOfArticles = get_xml($_SERVER["DOCUMENT_ROOT"] . "/_shared-content/xml/articles.xml", $categories, "inspect_news_article");

    // This is the new version of news.
    // TODO: currently the cached version is broken. It caches a BAD value.
    $arrayOfNewsAndStories = autoCache('get_xml', array($_SERVER["DOCUMENT_ROOT"] . "/_shared-content/xml/news-and-stories.xml", $categories, "inspect_news_article"));
//    $arrayOfNewsAndStories = get_xml($_SERVER["DOCUMENT_ROOT"] . "/_shared-content/xml/news-and-stories.xml", $categories, "inspect_news_article");
    $arrayOfNewsAndStories = sort_by_date($arrayOfNewsAndStories);

    $includeStories = false;
    $includeNews = false;

    if( $newsOrStories == "Both" ) {
        $includeStories = true;
        $includeNews = true;
    } elseif ( $newsOrStories == "News" ) {
        $includeNews = true;
    } else {
        $includeStories = true;
    }

    $threeStories = array();
    foreach( $arrayOfNewsAndStories as $index => $newsAndStory) {
        $id = $newsAndStory['id'];
        // if its the Homepage Top Feature, skip any that aren't tagged as homepage
        if( $galleryStyle == 'Homepage Top Feature' && !$newsAndStory['homepage-article'] )
            continue;
        if( $includeNews ) {
            if( $newsAndStory['article-type'] == 'News' and !in_array($id, $GLOBALS['stories-already-used'])) {
                $newsAndStory['gallery-image'] = srcset($newsAndStory['image-path'], false, true, '', $newsAndStory['title']);

                // don't use this story on this page again
                array_push($GLOBALS['stories-already-used'], $id);

                array_push($threeStories, $newsAndStory);
                unset($arrayOfNewsAndStories[$index]);
            }
        }
        if( $includeStories ) {
            if( $newsAndStory['article-type'] == 'Story' and !in_array($id, $GLOBALS['stories-already-used'])){
                $newsAndStory['gallery-image'] = srcset($newsAndStory['image-path'], false, true, '', $newsAndStory['title']);

                // don't use this story on this page again
                array_push($GLOBALS['stories-already-used'], $id);

                array_push($threeStories, $newsAndStory);
                unset($arrayOfNewsAndStories[$index]);
            }
        }
        // exit once there are 4
        if( sizeof($threeStories) >= 4)
            break;
    }

    $arrayOfArticles = array_merge($arrayOfArticles, $arrayOfNewsAndStories);
    global $NumArticles;
    $sortedArticles = sort_by_date($arrayOfArticles);

    // Only grab the first X number of articles.
    $sortedArticles = array_slice($sortedArticles, 0, $NumArticles, true);

//     echo "<script>console.log( 'Debug Objects: " . $galleryStyle . "' );</script>";

    $renderFile = "feature_home_news_gallery.html";
    if( $galleryStyle == "Feature Top") {
        $renderFile = "feature_top_news_gallery.html";
    } else if( $galleryStyle == "Feature Left") {
        $renderFile = "feature_left_news_gallery.html";
    }

    $twig = makeTwigEnviron('/code/news/twig');
    $html = $twig->render($renderFile, array(
        'sortedArticles'     => $sortedArticles,
        'threeStories'     => $threeStories,
        'myBethel'         => $myBethel
    ));

    return $html;
}
