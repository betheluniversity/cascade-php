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


function create_news_article_gallery_feed($categories, $galleryStyle, $myBethel, $newsOrStories = "Stories", $clearCacheBethelAlert='No'){
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
    $arrayOfArticles = autoCache('get_xml', array($_SERVER["DOCUMENT_ROOT"] . "/_shared-content/xml/articles.xml", $categories, "inspect_news_article"));

    // This is the new version of news.
    $arrayOfNewsAndStories = autoCache('get_xml', array($_SERVER["DOCUMENT_ROOT"] . "/_shared-content/xml/news-and-stories.xml", $categories, "inspect_news_article"), 300, $clearCacheBethelAlert);

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
    $onlyLookForCoronavirus = False;
    $coronavirusArticleId = 'c0a958b58c5865fc6f6501cb65bc8c89'; // TODO: THIS CAN BE REMOVED ONCE WE DON't HAVE THE CORNAVIRUS ARTICLE

    foreach( $arrayOfNewsAndStories as $index => $newsAndStory) {
        $id = $newsAndStory['id'];
        // if it's already been used, skip this article
        // if its the Homepage Top Feature, skip any that aren't tagged as homepage
        if( in_array($id, $GLOBALS['stories-already-used']) || ($galleryStyle == 'Homepage Top Feature' && !$newsAndStory['homepage-article']) )
            continue;

        // Check if the what the feed type is the same as the article type
        if( ($includeNews && $newsAndStory['article-type'] == 'News') || ($includeStories && $newsAndStory['article-type'] == 'Story')) {
            // TODO: THIS CHECK CAN BE REMOVED ONCE WE DON't HAVE THE CORNAVIRUS ARTICLE
            if($onlyLookForCoronavirus === True && $galleryStyle == 'Homepage Top Feature' && $id != $coronavirusArticleId){
                continue;
            }

            // We add the mybethel class for the community dashboard
            $add_mybethel_class = '';
            if( strpos($_SERVER['REQUEST_URI'], '_portal/') !== false )
                $add_mybethel_class = 'img-fluid';
            // Add the srcset to the gallery-image to be passed along to the html twig file
            $newsAndStory['gallery-image'] = srcset($newsAndStory['image-path'], false, true, $classes = $add_mybethel_class, $newsAndStory['title']);

            // don't use this story on this page again
            array_push($GLOBALS['stories-already-used'], $id);

            array_push($threeStories, $newsAndStory);
            unset($arrayOfNewsAndStories[$index]);
        }

        // TODO: THIS IS THE DEFAULT CODE, but we don't want to do this while we have the cornavirus locked in position 3
//        // exit once there are 3
//        if( sizeof($threeStories) >= 3)
//            break;
        // TODO: If its the homepage top feature and we have 2 and the coronavirus isn't in it, we only need to look for the cornavirus article.
        if( $galleryStyle == 'Homepage Top Feature' && sizeof($threeStories) == 2 && !in_array($coronavirusArticleId, $GLOBALS['stories-already-used'])) {
            $onlyLookForCoronavirus = True;
        }
        elseif( sizeof($threeStories) == 3) {
            break;
        }
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
