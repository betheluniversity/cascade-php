<?php
/**
 * Created by PhpStorm.
 * User: ces55739
 * Date: 7/25/14
 * Time: 9:35 AM
 */
// GLOBALS

// Metadata of feed
$School;
$Department;
$UniqueNews;

$NumArticles;
$AddFeaturedArticle;
$ExpireAfterXDays;
$DisplayTeaser;
$DisplayImages;

$featuredArticleOptions;

function create_news_article_feed($categories){
    $feed = autoCache("create_news_article_feed_logic", array($categories));
    return $feed;

}

// returns an array of html elements.
function create_news_article_feed_logic($categories){
    include_once $_SERVER["DOCUMENT_ROOT"] . "/code/php_helper_for_cascade.php";
    include_once $_SERVER["DOCUMENT_ROOT"] . "/code/general-cascade/feed_helper.php";


    $arrayOfArticles = autoCache('get_xml', array($_SERVER["DOCUMENT_ROOT"] . "/_shared-content/xml/articles.xml", $categories));


    global $NumArticles;
    // echo 'feed_news_sorted_'.$NumArticles;
    $sortedArticles = sort_by_date($arrayOfArticles);



    // Only grab the first X number of articles.
    $sortedArticles = array_slice($sortedArticles, 0, $NumArticles, true);

    $articleArray = array();
    foreach( $sortedArticles as $article){
        array_push($articleArray, $article['html']);
    }


    // FEATURED ARTICLES
    global $featuredArticleOptions;
    $featuredArticles = create_featured_array($featuredArticleOptions);

    $numArticles = sizeof($articleArray );
    if( $numArticles == 0){
        $articleArray = array("<p>No news articles available at this time.</p>");
    }

    $combinedArray = array($featuredArticles, $articleArray, $numArticles );
    return $combinedArray;
}

////////////////////////////////////////////////////////////////////////////////
// Gathers the info/html of the news article
////////////////////////////////////////////////////////////////////////////////

function inspect_news_article($xml, $categories){

    $page_info = array(
        "title"             => $xml->title,
        "display-name"      => $xml->{'display-name'},
        "published"         => $xml->{'last-published-on'},
        "description"       => $xml->{'description'},
        "path"              => $xml->path,
        "date-for-sorting"  => $xml->{'system-data-structure'}->{'publish-date'},       //timestamp.
        "md"                => array(),
        "html"              => "",
        "display-on-feed"   => false,
    );

    if( strpos($page_info['path'],"_testing") !== false)
        return "";

    $ds = $xml->{'system-data-structure'};

//    $page_info['date-for-sorting'] = time();

    // To get the correct definition path.
    $dataDefinition = $ds['definition-path'];

    if( $dataDefinition == "News Article")
    {
        $page_info['teaser'] = $xml->teaser;
        $page_info['html'] = get_news_article_html($page_info, $xml);

        $options = array('school', 'topic', 'department', 'adult-undergrad-program', 'graduate-program', 'seminary-program', 'unique-news');
        $page_info['display-on-feed'] = match_metadata_articles($xml, $categories, $options, "news");

        $page_info['display-on-feed'] = display_on_feed_news_articles($page_info, $ds);

        // Featured Articles
        global $featuredArticleOptions;
        global $AddFeaturedArticle;
        // Check if it is a featured Article.
        // If so, get the featured article html.
        if ( $AddFeaturedArticle == "Yes"){
            foreach( $featuredArticleOptions as $key=>$options)
            {
                // Check if the url of the article = the url of the desired feature article.
                if( $page_info['path'] == $options[0]){
                    $featuredArticleOptions[$key][3] = get_featured_article_html( $page_info, $xml, $options);
                }
            }
        }
    }

    return $page_info;
}

// Determine if the news article falls within the given range to be displayed
function display_on_feed_news_articles($page_info, $ds){
    $publishDate = $ds->{'publish-date'} / 1000;
    $currentDate = time();
    global $ExpireAfterXDays;
    $ExpiresInSeconds = $ExpireAfterXDays*86400; //converts days to seconds.
    if( $page_info['display-on-feed'] == true)
    {
        // Check if it falls between the given range.
        if( $ExpireAfterXDays != "" ){
            // if $publishDate is greater than $ExpiresInSeconds away from $currentDate, stop displaying it.
            if( $publishDate > $currentDate - $ExpiresInSeconds){
                return true;
            }else{
                return false;
            }
        }
        else
        {
            return true;
        }
    }
    return false;
}

// Returns the html of the news article
function get_news_article_html( $article, $xml ){
    $ds = $xml->{'system-data-structure'};
    $imagePath = $ds->{'media'}->{'image'}->{'path'};

    if (array_key_exists('external-path', $article)){
        $externalPath = $article['external-path'];
    }else{
        $externalPath = "";
    }

    if( $externalPath == "")
        $path = $article['path'];
    else
        $path = $externalPath;

    $date = $ds->{'publish-date'};

    global $DisplayTeaser;
    if( $date != "" && $date != "null" )
    {
        $formattedDate = format_featured_date_news_article($date);
    }

    global $DisplayImages;
    if( $DisplayImages && $DisplayImages === "No")
        $thumborURL = '';
    else
        $thumborURL = thumborURL($imagePath, 215, $lazy=true, $print=false);

    $twig = makeTwigEnviron('/code/news/twig');
    $html = $twig->render('news_article_feed.html', array(
        'DisplayTeaser' => $DisplayTeaser,
        'date' => $date,
        'formattedDate' => $formattedDate,
        'article' => $article,
        'path' => $path,
        'thumborURL' => $thumborURL));




    return $html;
}


// Matches the metadata of the page against the metadata of the proof point
function match_metadata_department_news_article($xml, $feed_value_array)
{
    foreach( $feed_value_array as $feed_value){
        foreach ($xml->{'dynamic-metadata'} as $md) {
            $name = $md->name;
            foreach ($md->value as $value) {
                if ($value == "Select" || $value == "none") {
                    continue;
                }
                if (htmlspecialchars($value) == htmlspecialchars($feed_value)) {
                    return true;
                }
            }
        }
    }
    return false;
}

function match_generic_school_news_articles($xml, $schools){

    $schoolsArray = array();
    foreach ($xml->{'dynamic-metadata'} as $md) {
        foreach ($md->value as $value) {
            if ($value == "Select" || $value == "none" || $value == "None" || $value == "") {
                continue;
            }

            // Add schools to an array to check later
            if ($md->name == "school") {
                array_push($schoolsArray, htmlspecialchars($value));
            }

            // if there are any depts, they are not generic. therefore, don't include.
            if ($md->name == "department" || $md->name == "adult-undergrad-program" || $md->name == "graduate-program" || $md->name == "seminary-program") {
                return false;
            }
        }
    }
    // event has no schools
    if( sizeof( $schoolsArray) == 0)
        return false;


    // Fix the values on $schools (it likes to store & as &amp;
    for( $i = 0; $i < sizeof($schools); $i++){
        $schools[$i] = htmlspecialchars($schools[$i]);
    }

    // returns true if the two arrays are equal
    if (sizeof(array_diff_assoc($schoolsArray, $schools)) == 0 ) {
        return true;
    }
    return false;
}

// Returns the featured Article html.
function get_featured_article_html($page_info, $xml, $options){
    $ds = $xml->{'system-data-structure'};
    $imagePath = $ds->{'media'}->{'image'}->{'path'};
    $date = $ds->{'publish-date'};
    $externalPath = $page_info['external-path'];
    if( $externalPath == "")
        $path = $page_info['path'];
    else
        $path = $externalPath;

    // Only display it if it has an image.
    if( $imagePath != "" && $imagePath != "/")
    {
        $html = '<div class="mt1 mb2 pa1" style="background: #f4f4f4">';
        $html .= '<span itemscope="itemscope" itemtype="https://schema.org/NewsArticle"><div class="grid left false">';
        $html .= '<div class="grid-cell  u-medium-1-2">';
        $html .= '<div class="grid-pad-1x">';

        $html .= thumborURL($imagePath, "400");

        $html .= '</div>';
        $html .= '</div>';
        $html .= '<div class="grid-cell  u-medium-1-2">';
        $html .= '<div class="grid-pad-1x">';
        // FIX THIS
        if( $page_info['title'] != "")
            $html .= '<h4"><a href="https://'.'.bethel.edu'.$path.'"><span itemprop="headline">'.$page_info['title'].'</span></a></h4>';

        if( $date != "" && $date != "null" )
        {
            $formattedDate = format_featured_date_news_article($date);
            $html .= "<p>".$formattedDate."</p>";
        }

        if( $options[1] != "" )
            $html .= '<p>'.$options[1].'</p>';
        elseif( $page_info['description'] != "")
            $html .= '<p>'.$page_info['description'].'</p>';

        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div></span>';
        $html .= '</div>';

        //twig version todo test and delete original
//        global $destinationName;
//        if( $date != "" && $date != "null" )
//        {
//            $formattedDate = format_featured_date_news_article($date);
//        }
//        $twig = makeTwigEnviron('/code/events/twig');
//        $html = $twig->render('feed_news_article.html', array(
//            'destinationName' => $destinationName,
//            'date' => $date,
//            'formattedDate' => $formattedDate,
//            'page_info' => $page_info,
//            'path' => $path,
//            'options' => $options,
//            'thumborURL' => thumborURL($imagePath, "400")));

    }
    else
        return "null";

    return $html;
}

// Returns a formatted version of the date.
function format_featured_date_news_article( $date)
{
    $date = $date/1000;
    $formattedDate = date("F d, Y | g:i a", $date);

    // Change am/pm to a.m./p.m.
    $formattedDate = str_replace("am", "a.m.", $formattedDate);
    $formattedDate = str_replace("pm", "p.m.", $formattedDate);

    // format 7:00 to 7
    $formattedDate = str_replace(":00", "", $formattedDate);
    $formattedDate = str_replace("12 p.m.", "noon", $formattedDate);
    $formattedDate = str_replace("12 a.m", "midnight", $formattedDate);
    return $formattedDate;
}


?>
