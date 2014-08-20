<?php
function create_archive(){

    // Feed
    global $newsArticleFeedCategories;
    $categories = $newsArticleFeedCategories;

    // Staging Site
    global $destinationName;
    if( strstr(getcwd(), "staging/public") ){
        include_once "/var/www/staging/public/code/php_helper_for_cascade.php";
        $destinationName = "staging";
        $arrayOfArticles = get_xml("/var/www/staging/public/_shared-content/xml/articles.xml", $categories);
    }
    else{ // Live site.
        include_once "/var/www/cms.pub/code/php_helper_for_cascade.php";
        $destinationName = "www";
        $arrayOfArticles = get_xml("/var/www/cms.pub/_shared-content/xml/articles.xml", $categories);
    }

    $sortedArticles = sort_news_articles($arrayOfArticles);

    $articleArray = array();
    foreach( $sortedArticles as $article){
        array_push($articleArray, $article['html']);
    }

    $finalArray = array();

    foreach($articleArray as $article){
        $currentMonth = $article['publish-date'];
        array_push($finalArray, "<p>" + $currentMonth + "</p>");
    }

    return $finalArray;
}

// Sort the array of articles, newest first.
function sort_news_articles( $articles ){
    function cmpi($a, $b)
    {
        return strcmp($b["date"], $a["date"]);
    }
    usort($articles, 'cmpi');

    return $articles;
}
?>