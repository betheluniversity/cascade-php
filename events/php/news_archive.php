<?php
/**
 * Created by PhpStorm.
 * User: ces55739
 * Date: 7/25/14
 * Time: 9:35 AM
 */
// GLOBALS

// returns an array of html elements.
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

//    foreach($arrayOfArticles as $value )
//        echo $value['html'];

    $arrayOfArticles = sort_news_articles( $arrayOfArticles );
    $arrayOfArticles = array_reverse($arrayOfArticles);

    foreach( $arrayOfArticles as $yearArray )
    {
        echo "<div class='archive-year year-" . $yearArray['01'][0]['year'] . "' >";
        for( $i = 1; $i <= 12 ;$i++)
        {
            if( $i <= 10 )
                $newi = '0'.$i;
            else
                $newi = $i;
            if( sizeof($yearArray[$newi]) > 0)
            {
                echo "<a name='" . strtolower($yearArray[$newi][0]['month-name']) . "'></a>";
                echo "<h4>" . $yearArray[$newi][0]['month-name'] . "</h4>";

                echo '<ul style="list-style:none outside none;padding-left:15px;">';
                foreach( $yearArray[$newi] as $article)
                {
                    echo $article['html'];
                }
                echo "</ul>";
            }
        }
        echo "</div>";
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
        "date" => $xml->{'system-data-structure'}->{'publish-date'} / 1000,       //timestamp.
        "md" => array(),
        "html" => "",
        "display-on-feed" => "Yes",
    );

    $ds = $xml->{'system-data-structure'};

    // To get the correct definition path.
    $dataDefinition = $ds['definition-path'];

    if( $dataDefinition == "News Article")
    {
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
    $title = $article['title'];

    $day = $article['day'];

    $html = "<li>";
    $html .= $day . " - <a href='https://www.bethel.edu/" .$path . "'>" . $title . "</a>";
    $html .= "</li>";

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

    // Need to sort the arrays properly.
    foreach($finalArray as $yearArray)
    {
        foreach($yearArray as $monthArray)
        {
            /// **** here is where you sort each month
            // sort_news_archive returns the new array.
            $newMonthArray = sort_news_archive($monthArray);
        }
    }

    return $finalArray;
}

function sort_news_archive( $array ){
    usort($array, 'sort_by_day');

    return $array;
}

function sort_by_day($a, $b)
{
    return strcmp($a["day"], $b["day"]);
}

?>
