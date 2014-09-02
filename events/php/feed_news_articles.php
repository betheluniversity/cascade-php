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
$StartDate;
$EndDate;

$featuredArticleOptions;

// returns an array of html elements.
function create_news_article_feed(){

    // Staging Site
    global $destinationName;
    if( strstr(getcwd(), "staging/public") ){
        include_once "/var/www/staging/public/code/php_helper_for_cascade.php";
        $destinationName = "staging";
        $arrayOfArticles = get_xml("/var/www/staging/public/_shared-content/xml/articles.xml", "");
    }
    else{ // Live site.
        include_once "/var/www/cms.pub/code/php_helper_for_cascade.php";
        $destinationName = "www";
        $arrayOfArticles = get_xml("/var/www/cms.pub/_shared-content/xml/articles.xml", "");
    }

    $sortedArticles = sort_array($arrayOfArticles);
    $sortedArticles = array_reverse($sortedArticles);

    // Only grab the first X number of articles.
    global $NumArticles;
    $sortedArticles = array_slice($sortedArticles, 0, $NumArticles, true);

    $articleArray = array();
    foreach( $sortedArticles as $article){
        array_push($articleArray, $article['html']);
    }


    // FEATURED ARTICLES
    $featuredArticles = create_featured_articles_array();

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
function inspect_news_article_page($xml){
    $page_info = array(
        "title" => $xml->title,
        "display-name" => $xml->{'display-name'},
        "published" => $xml->{'last-published-on'},
        "description" => $xml->{'description'},
        "path" => $xml->path,
        "date-for-sorting" => $xml->{'system-data-structure'}->{'publish-date'},       //timestamp.
        "md" => array(),
        "html" => "",
        "display-on-feed" => "No",
    );

    $ds = $xml->{'system-data-structure'};
    $page_info['display-on-feed'] = match_metadata_news_articles($xml);
//    $page_info['date-for-sorting'] = time();

    // To get the correct definition path.
    $dataDefinition = $ds['definition-path'];

    if( $dataDefinition == "News Article")
    {
        $page_info['teaser'] = $xml->teaser;
        $page_info['html'] = get_news_article_html($page_info, $xml);

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
    $date = $ds->{'publish-date'};
    global $StartDate;
    global $EndDate;

    if( $page_info['display-on-feed'] == "Metadata Matches")
    {
        // Check if it falls between the given range.
        if( $StartDate != "" && $EndDate != "" ){
            if( $StartDate < $date && $date < $EndDate){
                return "Yes";
            }
        }
        elseif( $StartDate != ""){
            if( $StartDate < $date){
                return "Yes";
            }
        }
        elseif( $EndDate != ""){
            if( $date < $EndDate){
                return "Yes";
            }
        }
        else
        {
            return "Yes";
        }
    }

    return "No";
}

// Returns the html of the news article
function get_news_article_html( $article, $xml ){
    $ds = $xml->{'system-data-structure'};
    $imagePath = $ds->{'media'}->{'image'}->{'path'};
    $date = $ds->{'publish-date'};

    $html = '<div class="grid">';
        $html .= '<div class="grid-cell  u-medium-1-3">';
            $html .= '<div class="medium-grid-pad-1x">';

            global $destinationName;
            $html .= '<a href="http://'.$destinationName.'.bethel.edu'.$article['path'].'">';
            $html .= render_image($imagePath, $article['description'], "media-box-img  delayed-image-load", "", $destinationName);
            $html .= '</a>';
            $html .= '</div>';
        $html .= '</div>';

        $html .= '<div class="grid-cell  u-medium-2-3">';
        $html .= '<div class="medium-grid-pad-1x">';
        $html .= '<h2 class="h5"><a href="http://'.$destinationName.'.bethel.edu'.$article['path'].'">'.$article['title'].'</a></h2>';

        if( $date != "" && $date != "null" )
        {
            $formattedDate = format_featured_date_news_article($date);
            $html .= "<p>".$formattedDate."</p>";
        }

        $html .= '<p>'.$article['teaser'].'</p>';
        $html .= '</div>';

        $html .= '</div>';
    $html .= '</div>';

    return $html;
}

// Checks the metadata of the page against the metadata of the news articles.
// if it matches, return "Metadata Matches"
// else, return "No"
function match_metadata_news_articles($xml){
    global $School;
    global $Department;
    global $UniqueNews;
    foreach ($xml->{'dynamic-metadata'} as $md){

        $name = $md->name;

        foreach($md->value as $value ){
            if($value == "Select" || $value == "select"){
                continue;
            }

            if( $name == "school")
            {
                if (in_array($value, $School)){
                    return "Metadata Matches";
                }
            }
            elseif( $name == "department")
            {
                if (in_array($value, $Department)){
                    return "Metadata Matches";
                }
            }
            elseif( $name == "unique-news")
            {
                if (in_array($value, $UniqueNews)){
                    return "Metadata Matches";
                }
            }
        }
    }
    return "No";
}


// Create the Featured Articles.
function create_featured_articles_array(){
    $featuredArticles = array();

    global $featuredArticleOptions;

    foreach( $featuredArticleOptions as $key=>$options ){
        if( $options[3] != "null" && $options[3] != ""){
            array_push($featuredArticles, $options[3]);
        }
    }
    return $featuredArticles;
}

// Returns the featured Article html.
function get_featured_article_html($page_info, $xml, $options){
    $ds = $xml->{'system-data-structure'};
    $imagePath = $ds->{'media'}->{'image'}->{'path'};
    $date = $ds->{'publish-date'};

    // Only display it if it has an image.
    if( $imagePath != "" && $imagePath != "/")
    {
        $html = '<div class="mt1 mb2 pa1" style="background: #f4f4f4">';
        $html .= '<div class="grid left false">';
        $html .= '<div class="grid-cell  u-medium-1-2">';
        $html .= '<div class="medium-grid-pad-1x">';

        global $destinationName;
        $html .= render_image($imagePath, $page_info['title'], "delayed-image-load", "400", $destinationName);

        $html .= '</div>';
        $html .= '</div>';
        $html .= '<div class="grid-cell  u-medium-1-2">';
        $html .= '<div class="medium-grid-pad-1x">';
        if( $page_info['title'] != "")
            $html .= '<h2 class="h5"><a href="http://'.$destinationName.'.bethel.edu'.$xml->path.'">'.$page_info['title'].'</a></h2>';

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
        $html .= '</div>';
        $html .= '</div>';

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
    return $formattedDate;
}
?>