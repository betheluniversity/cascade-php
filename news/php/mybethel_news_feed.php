<?php
/**
 * Created by PhpStorm.
 * User: ces55739
 * Date: 6/27/18
 * Time: 3:55 PM
 */

// blerts is bethel alerts.
function mybethel_news_feed($categories, $blerts='No'){
    include_once $_SERVER["DOCUMENT_ROOT"] . '/code/vendor/autoload.php';
    include_once $_SERVER["DOCUMENT_ROOT"] . "/code/news/php/news_article_feed.php";
    include_once $_SERVER["DOCUMENT_ROOT"] . "/code/general-cascade/feed_helper.php";
    include_once $_SERVER["DOCUMENT_ROOT"] . "/code/general-cascade/macros.php";

    // set $DisplayImages and $DisplayTeaser to Yes, as it is used for the normal feeds - so we need to still set those
    global $DisplayImages;
    $DisplayImages = 'Yes';
    global $DisplayTeaser;
    $DisplayTeaser = 'Yes';

//    $arrayOfNewsAndStories = autoCache('get_xml', array($_SERVER["DOCUMENT_ROOT"] . "/_shared-content/xml/news-and-stories.xml", $categories, "inspect_news_article"));
    $arrayOfNewsAndStories = get_xml($_SERVER["DOCUMENT_ROOT"] . "/_shared-content/xml/news-and-stories.xml", $categories, "inspect_news_article");
    $arrayOfNewsAndStories = sort_by_date($arrayOfNewsAndStories);

    // Only grab the first X number of articles.
    global $NumArticles;
    $count = 0;
    $sortedArticles = array();
    while( $count < $NumArticles ){
        $article = $arrayOfNewsAndStories[$count];
        if( !in_array( 'president/', $article['path']) ) {
            // If the news feed is set to use blerts, we check to make sure they include the values we want, else continue
            // if we include public alerts, then we only want to skip internal ones
            // if we don't want blerts, then we skip all blerts
            // if we want to include internal, then we don't skip any
            if( ($blerts == 'Yes - Public Bethel Alert' and $article['bethel-alert'] == 'Internal Bethel Alert')
                    or ($blerts == 'No' and $article['bethel-alert'] != 'No')){
                continue;
            }

            array_push($sortedArticles, $article);
            $count++;
        }
    }

    $twig = makeTwigEnviron('/code/news/twig');
    $html = $twig->render('mybethel_news_feed.html', array(
        'sortedArticles'     => $sortedArticles
    ));
    return $html;
}