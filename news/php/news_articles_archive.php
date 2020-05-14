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
function create_news_article_archive($categories, $blerts="No"){
    include_once $_SERVER["DOCUMENT_ROOT"] . "/code/php_helper_for_cascade.php";
    include_once $_SERVER["DOCUMENT_ROOT"] . "/code/general-cascade/feed_helper.php";

    if( strpos($_SERVER['REQUEST_URI'], 'president/announcements') !== false ) {
        // president archive
        $arrayOfArticles = get_xml($_SERVER["DOCUMENT_ROOT"] . "/_shared-content/xml/news-president.xml", $categories, "inspect_news_archive_page");
    } else {
        // news archive
        $arrayOfArticles = get_xml($_SERVER["DOCUMENT_ROOT"] . "/_shared-content/xml/articles.xml", $categories, "inspect_news_archive_page");
        $arrayOfNewsAndStories = get_xml($_SERVER["DOCUMENT_ROOT"] . "/_shared-content/xml/news-and-stories.xml", $categories, "inspect_news_archive_page");
        $arrayOfArticles = array_merge($arrayOfArticles, $arrayOfNewsAndStories);
    }

    $arrayOfArticles = sort_news_articles($arrayOfArticles);
    $arrayOfArticles = array_reverse($arrayOfArticles);

    // Blert logic
    $finalArticleArray = [];
    foreach ($arrayOfArticles as $article) {
        // If the news feed is set to use blerts, we check to make sure they include the values we want, else continue
        // if we include public alerts, then we only want to skip internal ones
        // if we don't want blerts, then we skip all blerts
        // if we want to include internal, then we don't skip any
        print_r($blerts);
        print_r($article['bethel-alert']);
        if( ($blerts == 'Yes - Public Bethel Alert' and $article['bethel-alert'] == 'Internal Bethel Alert')
            or ($blerts == 'No' and $article['bethel-alert'] != 'No')){
            continue;
        }
        array_push($finalArticleArray, $article);
    }

//    echo autoCache("echo_articles", array($finalArticleArray), 300, $blerts);
    echo echo_articles($finalArticleArray);

    return;
}

function echo_articles($arrayOfArticles) {
    print_r('echo_articles');
    $twig = makeTwigEnviron('/code/news/twig');
    $return_str = '';
    foreach( $arrayOfArticles as $yearArray )
    {
        // This should return an array of 5 items. Each of those includes a full years worth of articles, pre-sorted, and including the headers.
        //twig version
        $return_str .= $twig->render('news_article_archive.html',array(
            'yearArray' => $yearArray
        ));
    }
    return $return_str;
}

////////////////////////////////////////////////////////////////////////////////
// Gathers the info/html of the news article
////////////////////////////////////////////////////////////////////////////////
function inspect_news_archive_page($xml, $categories){

    $page_info = array(
        "title"             => $xml->title,
        "display-name"      => $xml->{'display-name'},
        "published"         => $xml->{'last-published-on'},
        "description"       => $xml->{'description'},
        "path"              => $xml->path,
        "md"                => array(),
        "html"              => "",
        "display-on-feed"   => true,
        "id"                => $xml['id'],
        "bethel-alert"      => 'No'
    );
    // if the file doesn't exist, skip it.
    if( !file_exists($_SERVER["DOCUMENT_ROOT"] . '/' . $page_info['path'] . '.php') ) {
        return "";
    }
    // don't include testing ones
    if( strpos($page_info['path'],"_testing") !== false)
        return "";

    $ds = $xml->{'system-data-structure'};

    // To get the correct definition path.
    $dataDefinition = $ds['definition-path'];


    $year_works = false;
    for( $i = 2012; $i <= intval(date("Y")); $i++ ){
        if( strstr($xml->path, "/$i/") ){
            $year_works = true;
            break;
        }
    }
    // Get the correct date, depending on which Data Definition you are dealing with.
    if( $year_works ) {
        if( $dataDefinition == "News Article") {
            $date_for_sorting = $xml->{'system-data-structure'}->{'publish-date'} / 1000;

            if ($ds->{'external-link'} != ''){
                $page_info['path'] = $ds->{'external-link'};
            }
        } elseif( $dataDefinition == "News Article - Flex" ) {
            $date_for_sorting = $ds->{'story-metadata'}->{'publish-date'} / 1000;
        } elseif( $dataDefinition == "News Article - President" ){
            $date_for_sorting = $ds->{'publish-date'} / 1000;
        } else {
            // todo: this probably isn't needed, but we should have some default value
            $date_for_sorting = $ds->{'story-metadata'}->{'publish-date'} / 1000;
        }
    } else {
        return $page_info;
    }

    $page_info['date-for-sorting'] = $date_for_sorting;
    $page_info['day'] = date("d", $date_for_sorting);
    $page_info['year'] = date("Y", $date_for_sorting);
    $page_info['month'] = date("m", $date_for_sorting);
    $page_info['month-name'] = date("F", $date_for_sorting);

    if( strpos($_SERVER['REQUEST_URI'], 'president/announcements') !== false ) {
        if( $dataDefinition == "News Article - President" ){
            $page_info['display-on-feed'] = true;
        } elseif( $dataDefinition == "News Article") {
            $options = array('school', 'topic', 'department', 'adult-undergrad-program', 'graduate-program', 'seminary-program', 'unique-news');
            $page_info['display-on-feed'] = match_metadata_articles($xml, $categories, $options, "news");
        } elseif( $dataDefinition == "News Article - Flex") {
            $page_info['bethel-alert'] = $ds->{'story-metadata'}->{'bethel-alert'};
        }
    } else {
        print_r('got here, its a problem!');
        # if its not the president announcements page, we want to make sure that the prez announcements are NOT shown.
        foreach ($xml->{'dynamic-metadata'} as $md) {
            $name = $md->name;

            foreach ($md->value as $value) {
                if ( $name == 'unique-news' && in_array($value, ['President Announcements', 'President Announcements - Strategic plan', 'Internal']) ) {
                    return "";
                }
            }
        }

    }

    $page_info['html'] = get_news_article_archive_html($page_info);
    return $page_info;
}


// Returns the html of the news article
function get_news_article_archive_html( $article ){
    $twig = makeTwigEnviron('/code/news/twig');

    return $twig->render('get_news_article_archive_html.html', array(
        'article' => $article
    ));
}

// Sort the array of articles, newest first.
function sort_news_articles( $articles ){
    $finalArray = array();

    // Puts the articles into the correct arrays
    foreach( $articles as $article)
    {
        $articleYear = find('year', $article);
        $articleMonth = find('month', $article);
        $finalYear = find($articleYear, $finalArray);
        $finalMonth = find($articleMonth, $finalYear);
        if( $finalMonth && sizeof( $finalMonth) > 0)
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
            $finalArray[$currentYear][$currentMonth] = sort_by_date($monthArray, false);
        }
    }

    return $finalArray;
}

function find($key, $array){
    if($array != null && array_key_exists($key, $array)){
        return $array[$key];
    }else{
        return null;
    }
}



?>
