<?php

include_once $_SERVER["DOCUMENT_ROOT"] . "/code/news/php/news_article_feed.php";
include_once $_SERVER["DOCUMENT_ROOT"] . "/code/php_helper_for_cascade.php";
include_once $_SERVER["DOCUMENT_ROOT"] . "/code/general-cascade/feed_helper.php";
include_once $_SERVER["DOCUMENT_ROOT"] . "/code/general-cascade/macros.php";
require $_SERVER["DOCUMENT_ROOT"] . '/code/vendor/autoload.php';


// todo - remove unused variable here later
function create_news_carousel_feed($categories, $galleryStyle, $myBethel, $blerts='No'){
    // set $DisplayImages and $DisplayTeaser to Yes, as it is used for the normal feeds - so we need to still set those
    global $DisplayImages;
    $DisplayImages = 'Yes';
    global $DisplayTeaser;
    $DisplayTeaser = 'Yes';

    // grab the global variable so we don't use stories that have already been used
    if( !array_key_exists('stories-already-used', $GLOBALS) ){
        $GLOBALS['stories-already-used'] = array();
    }

    // This is the new version of news.
    $arrayOfNewsAndStories = autoCache('get_xml', array($_SERVER["DOCUMENT_ROOT"] . "/_shared-content/xml/news-and-stories.xml", $categories, "inspect_news_article"), 300, $blerts);
    $arrayOfNewsAndStories = sort_by_date($arrayOfNewsAndStories);
    $articleArray = array();

    foreach( $arrayOfNewsAndStories as $index => $article) {
        $id = $article['id'];
        // if it's already been used, skip this article
        // if its the Homepage Top Feature, skip any that aren't tagged as homepage
        // if( in_array($id, $GLOBALS['stories-already-used']) || ($galleryStyle == 'Homepage Top Feature' && !$article['featured-homepage-article']) || ($galleryStyle == 'About Page Feature' && !$article['about-page']) )
        //     continue;

        // If the news feed is set to use blerts, we check to make sure they include the values we want, else continue
        // if we include public alerts, then we only want to skip internal ones
        // if we don't want blerts, then we skip all blerts
        // if we want to include internal, then we don't skip any
        if( ($blerts == 'Yes - Public Bethel Alert' and $article['bethel-alert'] == 'Internal Bethel Alert')
            or ($blerts == 'No' and $article['bethel-alert'] != 'No')){
            continue;
        }

        // We add the mybethel class for the community dashboard
        // $add_mybethel_class = '';
        // if( strpos($_SERVER['REQUEST_URI'], '_portal/') !== false )
        //     $add_mybethel_class = 'img-fluid';
        // // Add the srcset to the gallery-image to be passed along to the html twig file
        // $article['gallery-image'] = srcset($article['image-path'], false, true, $classes = $add_mybethel_class, $article['title']);

        // don't use this story on this page again
        array_push($GLOBALS['stories-already-used'], $id);

        array_push($articleArray, $article);
        unset($arrayOfNewsAndStories[$index]);

        // TODO: THIS IS THE DEFAULT CODE, but we don't want to do this while we have the cornavirus locked in position 3
        // exit once there are 3
        if( sizeof($articleArray) >= 3)
            break;
    }

    $arrayOfArticles = array_merge($arrayOfArticles, $arrayOfNewsAndStories);
    global $NumArticles;
    $sortedArticles = sort_by_date($arrayOfArticles);

    // Only grab the first X number of articles.
    $sortedArticles = array_slice($sortedArticles, 0, $NumArticles, true);

//     echo "<script>console.log( 'Debug Objects: " . $galleryStyle . "' );</script>";

    $renderFile = "carousel-news-feed.html";    
    
    $twig = makeTwigEnviron('/code/news/twig');
    $html = $twig->render($renderFile, array(
        'sortedArticles'     => $sortedArticles
    ));

    return $html;
}
