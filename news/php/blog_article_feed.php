<?php

// Values are passed in from Cascade
$numPosts;
$includeTitle;
$feedTitle;
$categories;
$metadata;
$linkType;
$linkTest;
$includeBlogLink;
$customLinkText;

// Assigned when xml is loaded
$allNamespaces;

function print_s($thing){
    echo '<pre>';
    echo print_r($thing);
    echo '</pre>';
}

function set_num_posts($from_cascade){
    global $numPosts;
    $numPosts = (int) $from_cascade;
}

function set_metadata_cats($creator, $pubDate, $categories, $description, $image){
    global $metadata;

    $metadata['creator'] = $creator;
    $metadata['pub date'] = $pubDate;
    $metadata['categories'] = $categories;
    $metadata['description'] = $description;
    $metadata['image'] = $image;
}

function setup_individual_category($var_value, $correct_string){
    global $categories;
    if($var_value == 1){
        $categories[] = "$correct_string";
    }
}


function set_categories_cats($academics, $admissions, $col_exploration, $col_life, $fin_aid, $careers, $advice, $prof_roles, $spiritual, $study, $wellbeing, $all){
    setup_individual_category($academics, "Academics");
    setup_individual_category($admissions, "Admissions Process");
    setup_individual_category($col_exploration, "College Exploration");
    setup_individual_category($col_life, "College Life");
    setup_individual_category($fin_aid, "Financial Aid");
    setup_individual_category($careers, "Jobs and Careers");
    setup_individual_category($advice, "Life Advice");
    setup_individual_category($prof_roles, "Professional Roles");
    setup_individual_category($spiritual, "Spiritual Growth");
    setup_individual_category($study, "Study Skills");
    setup_individual_category($wellbeing, "Wellbeing");
    setup_individual_category($all, "All");
}

function post_matches_cats($post){
    global $categories;
    echo '</br></br>post is in categories: </br>';
    foreach($post->category as $cat){
        echo "    " . $cat . "</br>";
    }
    echo 'feed wants categories: </br>';
    foreach($categories as $cat){
        echo "    " . $cat . "</br>";
    }
    return true;
}

function get_description_as_array($item)
{
    $descToString = "<root>$item->description</root>".PHP_EOL;
    $stringToObj = simplexml_load_string($descToString);
    $objToJson = json_encode($stringToObj);
    $jsonToArr = json_decode($objToJson, TRUE);

    return $jsonToArr;
}

function get_as_array($item)
{
    //$descToString = "<root>$item</root>".PHP_EOL;
    $stringToObj = simplexml_load_string($item);
    $objToJson = json_encode($stringToObj);
    $jsonToArr = json_decode($objToJson, TRUE);

    return $jsonToArr;
}


function get_only_desired_elements($xml)
{
    global $metadata, $numPosts, $allNamespaces, $categories;
    $retArray = array();
    $itemsAr = $xml->channel->children();
    $numItems = 0;

    foreach($itemsAr as $item){
        if($numItems == $numPosts) {
            break;
        }
        if($item->getName() == 'item'){

            $description = get_description_as_array($item);
            $retArray[$numItems]['title'] = (string) $item->title;
            $retArray[$numItems]['link'] = (string) $description['a']['@attributes']['href'];

            if($categories['all'] || post_matches_cats($item)){
                if ($metadata['creator']) {
                    $dcNamespace = $item->children($allNamespaces['dc']);
                    $retArray[$numItems]['creator'] = (string)$dcNamespace->creator[0];
                }
                if ($metadata['pub date']) {
                    $retArray[$numItems]['pub date'] = (string)$item->pubDate;
                }
                if ($metadata['description']) {
                    $retArray[$numItems]['description'] = (string)$description['p'][0];
                }
                if ($metadata['image']) {
                    $retArray[$numItems]['image'] = (string)$description['a']['img']['@attributes']['src'];
                }
            }
            $numItems++;
        }
    }
    return $retArray;
}


function create_blog_feed()
{
    global $allNamespaces;
    echo "CURRENT AS OF JUNE 2 12:19</br></br>";

    $feed = file_get_contents($_SERVER["DOCUMENT_ROOT"] . "/_testing/anna-h/blog/_feeds/blog-articles-xml.xml");
    $xml = simplexml_load_string($feed);

    $allNamespaces = $xml->getDocNamespaces(TRUE);

    $retArray = get_only_desired_elements($xml);


    return $retArray;
}


function create_blog_feed_array()
{

    $feed = file_get_contents($_SERVER["DOCUMENT_ROOT"] . "/_testing/anna-h/blog/_feeds/blog-articles-xml.xml");
    $xml = simplexml_load_string($feed);
    $xmlAsJson = json_encode($xml);
    $xmlArray = json_decode($xmlAsJson, TRUE);
    return $xmlArray;
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
