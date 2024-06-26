<?php

include_once $_SERVER["DOCUMENT_ROOT"] . "/code/news/php/news_article_feed.php";
include_once $_SERVER["DOCUMENT_ROOT"] . "/code/php_helper_for_cascade.php";
include_once $_SERVER["DOCUMENT_ROOT"] . "/code/general-cascade/feed_helper.php";
include_once $_SERVER["DOCUMENT_ROOT"] . "/code/general-cascade/macros.php";
require $_SERVER["DOCUMENT_ROOT"] . '/code/vendor/autoload.php';


// todo - remove unused variable here later
function create_news_carousel_feed($categories, $galleryStyle, $myBethel, $blerts='No'){
    
    $carouselFeed = create_news_article_feed($categories, $blerts='No');

    $renderFile = "carousel-news-feed.html";    
    
    $twig = makeTwigEnviron('/code/news/twig');
    $html = $twig->render($renderFile, array(
        $carouselFeed
    ));

    return $html;
}
