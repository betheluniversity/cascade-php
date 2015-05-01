<?php

//TODO move this to the news/php folder.
/**
 * Created by PhpStorm.
 * User: ces55739
 * Date: 7/25/14
 * Time: 9:35 AM
 */
// GLOBALS
$yearChosen;
$uniqueNews;
// returns an array of html elements.
function create_news_article_archive($categories){
    include_once $_SERVER["DOCUMENT_ROOT"] . "/code/php_helper_for_cascade.php";
    $arrayOfArticles = get_xml_news_archive($_SERVER["DOCUMENT_ROOT"] . "/_shared-content/xml/articles.xml", $categories);

//    foreach($arrayOfArticles as $value )
//        echo $value['html'];

    $arrayOfArticles = autoCache("sort_news_articles", array($arrayOfArticles), "news_archive_sorted");
    $arrayOfArticles = array_reverse($arrayOfArticles);

    $twig = makeTwigEnviron('/code/news/twig');

    foreach( $arrayOfArticles as $yearArray )
    {
        //twig version
        echo $twig->render('news_article_archive.html',array(
            'yearArray' => $yearArray
        ));
    }

    // This should return an array of 5 items. Each of those includes a full years worth of articles, pre-sorted, and including the headers.
//    return $articleArray;
    return;
}

////////////////////////////////////////////////////////////////////////////////
// Gathers the info/html of the news article
////////////////////////////////////////////////////////////////////////////////

function inspect_news_archive_page($xml){

    $page_info = array(
        "title" => $xml->title,
        "display-name" => $xml->{'display-name'},
        "published" => $xml->{'last-published-on'},
        "description" => $xml->{'description'},
        "path" => $xml->path,
        "external-path" => $xml->{'system-data-structure'}->{'external-link'},
        "date" => $xml->{'system-data-structure'}->{'publish-date'} / 1000,       //timestamp.
        "md" => array(),
        "html" => "",
        "display-on-feed" => true,
    );
    if( strpos($page_info['path'],"_testing") !== false)
        return "";

    $ds = $xml->{'system-data-structure'};

    // To get the correct definition path.
    $dataDefinition = $ds['definition-path'];

    global $yearChosen;
    global $uniqueNews;

    $isInternal = in_array("Internal", $uniqueNews);
    if( $dataDefinition == "News Article" && ( strstr($xml->path, '2012') || strstr($xml->path, '2013') || ( strstr($xml->path, '2014')) || ( strstr($xml->path, '2015'))) )
    {
        //check if is internal
        $date = $page_info['date'];
        $page_info['day'] = date("d", $date);
        $page_info['year'] = date("Y", $date);
        $page_info['month'] = date("m", $date);
        $page_info['month-name'] = date("F", $date);

        $page_info['html'] = get_news_article_html($page_info, $xml);
    }
    return $page_info;
}


// Returns the html of the news article
function get_news_article_html( $article, $xml ){

    $path = $article['path'];
    $externalPath = $article['external-path'];
    $title = $article['title'];

    $day = $article['day'];
    $twig = makeTwigEnviron('/code/news/twig');

    $html = $twig->render('get_news_article_html.html', array(
        'externalPath' => $externalPath,
        'path' => $path,
        'title' => $title,
        'day' => $day));

    return $html;
}

// Sort the array of articles, newest first.
function sort_news_articles( $articles ){
    $finalArray = array();

    // Puts the articles into the correct arrays
    foreach( $articles as $article)
    {
        $articleYear = $article['year'];
        $articleMonth = $article['month'];

        if( sizeof( $finalArray[ $articleYear ][ $articleMonth ]) > 0)
            $tempMonthArray = $finalArray[ $articleYear ][ $articleMonth ];
        else
            $tempMonthArray = array();

        array_push( $tempMonthArray, $article );
        $finalArray[ $articleYear ][ $articleMonth ] = $tempMonthArray;
    }

    foreach($finalArray as $yearArray)
    {
        $currentYear = array_search($yearArray, $finalArray);
        foreach($yearArray as $monthArray)
        {
            $currentMonth = array_search($monthArray, $yearArray);
            //echo $currentYear."--".$currentMonth."<br/>";
            $finalArray[$currentYear][$currentMonth] = sort_news_archive($monthArray);
        }
    }

    return $finalArray;
}

function sort_news_archive( $array ){
    usort($array, 'sort_by_day');
    return array_reverse($array);
}

function sort_by_day($a, $b)
{
    return strcmp($a["day"], $b["day"]);
}

function get_xml_news_archive($fileToLoad){
    $xml = simplexml_load_file($fileToLoad);
    $pages = array();
    $pages = traverse_folder_news_archive($xml, $pages);
    return $pages;
}

function traverse_folder_news_archive($xml, $pages){
    if(!$xml){
        return;
    }
    foreach ($xml->children() as $child) {

        $name = $child->getName();

        if ($name == 'system-folder'){
            $pages = traverse_folder_news_archive($child, $pages);
        }elseif ($name == 'system-page'){
            // Set the page data.
            $page = inspect_news_archive_page($child);

            // Add to an event array.
            if( $page['display-on-feed'] ) {
                array_push($pages, $page);
            }
        }
    }

    return $pages;
}

?>
