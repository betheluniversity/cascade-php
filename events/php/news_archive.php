<?php
function create_archive(){

    /*// Feed
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
        //array_push($finalArray, "<p>" . $currentMonth ."</p>");
        array_push($finalArray, "<p>TESTING</p>");
    }

    echo "<h1>TEST CONNECTION</h1>";

    array_push($finalArray, "<p>TESTING1</p>");
    array_push($finalArray, "<p>TESTING2</p>");
    array_push($finalArray, "<p>TESTING3</p>");
    array_push($finalArray, "<p>TESTING4</p>");
    array_push($finalArray, "<p>TESTING5</p>");
    array_push($finalArray, "<p>TESTING6</p>");
    array_push($finalArray, "<p>TESTING7</p>");

    return $finalArray;*/
    $articleArray = array("<p>Testing.</p>");

    return $articleArray;
}

/*// Sort the array of articles, newest first.
function sort_news_articles( $articles ){
    function cmpi($a, $b)
    {
        return strcmp($b["date"], $a["date"]);
    }
    usort($articles, 'cmpi');

    return $articles;
}
?>*/