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

//TODO Move to feed_helper later (ACH)
function get_blog_rss_xml($fileToLoad, $categories){
    echo "in get_blog_rss_xml</br>";
    $feed = file_get_contents($fileToLoad);
    $xml = simplexml_load_string($feed);
    if(!$xml){
        echo "returning due to !xml</br>";
        return;
    }
    $pages = array();
    $func = "inspect_news_article";

    $pages = traverse_blog_rss($xml);
    echo "survived traverse_blog_rss</br>";
    return $pages;
}

function traverse_blog_rss($xml){
    if (!$xml) {
        echo "Cannot parse invalid xml</br>";
        return;
    }
//    echo "inside tbr</br>";
//    echo $xml->getName() . "</br>";
//    foreach ($xml->children() as $child)
//    {
//        echo $child->getName() . "</br>";
//        if($child->getName() == "channel"){
//            foreach($child->children() as $item){
//                echo $item->getName() . "</br>";
//                foreach($item->attributes() as $aa => $bb){
//                    echo $aa , "=", $bb, "</b>";
//                }
//            }
//        }
//
//    }
    echo $xml->channel->link;
}

function create_news_article_feed($categories, $blerts="No"){
    echo "Called create news article feed</br>";
    $feed = autoCache("create_news_article_feed_logic", array($categories, $blerts), 300, $blerts);
    return $feed;
}

// returns an array of html elements.
function create_news_article_feed_logic($categories, $blerts){
    include_once $_SERVER["DOCUMENT_ROOT"] . "/code/php_helper_for_cascade.php";
    include_once $_SERVER["DOCUMENT_ROOT"] . "/code/general-cascade/feed_helper.php";

    // grab the global variable so we don't use stories that have already been used
    if( !array_key_exists('stories-already-used', $GLOBALS) ){
        $GLOBALS['stories-already-used'] = array();
    }

    $arrayOfArticles = get_blog_rss_xml($_SERVER["DOCUMENT_ROOT"] . "/_testing/anna-h/blog/_feeds/blog-articles-xml.xml", $categories);

    global $NumArticles;
    // echo 'feed_news_sorted_'.$NumArticles;
    $sortedArticles = sort_by_date($arrayOfArticles);

    $articleArray = array();
    foreach( $sortedArticles as $article ){
        echo "  in foreach</br>";
        $id = $article['id'];
        
//        if( !in_array($id, $GLOBALS['stories-already-used']) ){
//            // If the news feed is set to use blerts, we check to make sure they include the values we want, else continue
//            // if we include public alerts, then we only want to skip internal ones
//            // if we don't want blerts, then we skip all blerts
//            // if we want to include internal, then we don't skip any
//            if(( $article['bethel-alert'] == 'Internal Bethel Alert' and $blerts != 'Yes- Internal Bethel Alert' )
//                or ( $article['bethel-alert'] == 'Public Bethel Alert' and $blerts == 'No' )) {
//                continue;
//            }
//
            echo "  pushing article (aka particle)</br>";
            array_push($articleArray, $article['html']);
//
//            // don't use this story on this page again
//            array_push($GLOBALS['stories-already-used'], $id);
//        }
//
        // Only grab the first X number of articles.
        if( sizeof($articleArray) >= $NumArticles )
            break;
    }

    // FEATURED ARTICLES
    global $featuredArticleOptions;
    $featuredArticles = create_featured_array($featuredArticleOptions);

    // we need to check size, to see if we actually found one
    $numArticles = sizeof($articleArray );
    if( $numArticles == 0){
        $articleArray = array("<img class='d-flex mr-3 lazyload mb-3' src='https://www.bethel.edu/news/images/news-feed-fallback.jpg' alt='news-feed' /><p>We believe everyone has a story to tell. Envision how Bethel could become part of yours.<br/><br/><a class='btn btn-primary' href='https://www.bethel.edu/news/'>Latest News and Stories</a></p>");
    }

    $combinedArray = array($featuredArticles, $articleArray, $numArticles );
    return $combinedArray;
}

////////////////////////////////////////////////////////////////////////////////
// Gathers the info/html of the news article
////////////////////////////////////////////////////////////////////////////////

function inspect_news_article($xml, $categories){
    echo "Inspecting an article</br>";
    $page_info = array(
        "title"                     => (string)$xml->title,
        "teaser"                    => (string)$xml->teaser,
        "display-name"              => (string)$xml->{'display-name'},
        "published"                 => (int)$xml->{'last-published-on'},
        "description"               => (string)$xml->{'description'},
        "path"                      => (string)$xml->path,
        "url"                       => "https://www.bethel.edu$xml->path",
        "date-for-sorting"          => 0,       //timestamp.
        "md"                        => array(),
        "html"                      => "",
        "display-on-feed"           => false,
        "id"                        => (string)$xml['id'],
        "featured-homepage-article" => false,
        "bethel-alert"              => 'No'
    );

    // if the file doesn't exist, skip it.
    if( !file_exists($_SERVER["DOCUMENT_ROOT"] . '/' . $page_info['path'] . '.php') ) {
        return "";
    }

    if( strpos($page_info['path'],"_testing") !== false)
        return "";

    $ds = $xml->{'system-data-structure'};

    // To get the correct definition path.
    $ddp = $ds['definition-path'];

    $options = array('school', 'topic', 'department', 'adult-undergrad-program', 'graduate-program', 'seminary-program', 'office', 'unique-news');
    if( $ddp == "News Article") {
        $page_info['image-path'] = (string)$ds->{'media'}->{'image'}->{'path'};
        // set external path, if available
        if ($ds->{'external-link'}){
            $page_info['path'] = (string)$ds->{'external-link'};
        }
        $page_info['date-for-sorting'] = (int)$ds->{'publish-date'};

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
    } else {
        $page_info['image-path'] = (string)$ds->{'story-metadata'}->{'feed-image'}->{'path'};
        $page_info['date-for-sorting'] = (int)$ds->{'story-metadata'}->{'publish-date'};
        $page_info['bethel-alert'] = $ds->{'story-metadata'}->{'bethel-alert'};
        if( $ds->{'story-metadata'}->{'featured'} == 'Yes' )
            $page_info['featured-homepage-article'] = true;  // this defaults to false above, if it doesn't hit this
    }

    global $DisplayImages;
    if( ($DisplayImages && $DisplayImages === "No") || $page_info['image-path'] == '/')
        $page_info['image'] = '';
    else {
        // We add the mybethel class for the community dashboard
        $add_mybethel_class = '';
        if( strpos($_SERVER['REQUEST_URI'], '_portal/') !== false )
            $add_mybethel_class = 'img-fluid';
        $page_info['image'] = srcset($page_info['image-path'], $print = false, $lazy = true, $classes = $add_mybethel_class, $page_info['title']);
    }

    // if its a bethel alert, always pass it on to be checked by the feed code!
    if($page_info['bethel-alert'] == 'No') {
        $page_info['metadata_articles'] = match_metadata_articles($xml, $categories, $options);
    } else {
        $page_info['metadata_articles'] = true;
    }
    $page_info['is_expired'] = is_expired($page_info['date-for-sorting']);

    if( $page_info['metadata_articles'] && !$page_info['is_expired'] ) {
        $page_info['display-on-feed'] = true;
    }

    $page_info['display-date'] = format_featured_date_news_article($page_info['date-for-sorting']);
    $page_info['html'] = get_news_article_html($page_info);

    return $page_info;
}

// Determine if the news article falls within the given range to be displayed
function is_expired($date_for_sorting){
    $publishDate = $date_for_sorting / 1000;
    $currentDate = time();
    global $ExpireAfterXDays;

    // Check if it falls between the given range.
    if( $ExpireAfterXDays != "" ){
        $ExpiresInSeconds = $ExpireAfterXDays*86400; //converts days to seconds.
        // if $publishDate is greater than $ExpiresInSeconds away from $currentDate, stop displaying it.
        if( $publishDate > ($currentDate - $ExpiresInSeconds)){
            return false;
        }else{
            return true;
        }
    }
    else {
        return false;
    }
}

// todo: we should only need to pass in article
// Returns the html of the news article
function get_news_article_html( $article ){
    echo "getting article html</br>";
    global $DisplayTeaser;
    $twig = makeTwigEnviron('/code/news/twig');
    $html = $twig->render('news_article_feed.html', array(
        'DisplayTeaser'     => $DisplayTeaser,
        'article'           => $article,
        'image'             => $article['image']
    ));

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
    echo "trying to get a featured article</br>";
    $ds = $xml->{'system-data-structure'};
    $imagePath = $page_info['image-path'];
    $date = $ds->{'publish-date'};
    $externalPath = $page_info['external-path'];
    if( $externalPath == "")
        $path = $page_info['path'];
    else
        $path = $externalPath;

    // Only display it if it has an image.
    if( $imagePath != "" && $imagePath != "/")
    {
        // todo: this should really be in a template
        $html = '<div class="mt1 mb2 pa1" style="background: #f4f4f4">';
        $html .= '<span itemscope="itemscope" itemtype="https://schema.org/NewsArticle"><div class="grid left false">';
        $html .= '<div class="grid-cell  u-medium-1-2">';
        $html .= '<div class="grid-pad-1x">';

        $html .= thumborURL($imagePath, "400", true, true, $page_info['description']);

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
    $formattedDate = str_replace("12 p.m.", "Noon", $formattedDate);
    $formattedDate = str_replace("12 a.m", "midnight", $formattedDate);
    return $formattedDate;
}


?>
