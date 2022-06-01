<?php

// Values are passed in from Cascade
$numPosts = 3;
$includeTitle;
$feedTitle;
$categories;
$metadata;
$linkType;
$linkTest;
$includeBlogLink;
$customLinkText;

function set_metadata_cats($creator, $pubDate, $categories, $description, $image){
    global $metadata;

    $metadata['creator'] = $creator;
    $metadata['pub date'] = $pubDate;
    $metadata['categories'] = $categories;
    $metadata['description'] = $description;
    $metadata['image'] = $image;
}

function set_categories_cats($academics, $admissions, $col_exploration, $col_life, $fin_aid, $careers, $advice, $prof_roles, $spiritual, $study, $wellbeing, $all){
    global $categories;

    $categories['academics'] = $academics;
    $categories['admissions'] = $admissions;
    $categories['col exploration'] = $col_exploration;
    $categories['col life'] = $col_life;
    $categories['fin aid'] = $fin_aid;
    $categories['careers'] = $careers;
    $categories['advice'] = $advice;
    $categories['prof roles'] = $prof_roles;
    $categories['spiritual'] = $spiritual;
    $categories['study'] = $study;
    $categories['wellbeing'] = $wellbeing;
    $categories['all'] = $all;
}

//TODO Move to feed_helper later (ACH)
function get_blog_rss_xml($fileToLoad){
    echo "in get_blog_rss_xml</br>";
    $feed = file_get_contents($fileToLoad);
    $xml = simplexml_load_string($feed);
    if(!$xml){
        echo "returning due to !xml</br>";
        return;
    }
//    $pages = array();
//    $func = "inspect_news_article";
//
    $pages = traverse_blog_rss($xml);
    echo "survived traverse_blog_rss</br>";
//    return $pages;
}

function traverse_blog_as_json($xml)
{
    $xmlAsJson = json_encode($xml);
    $xmlArray = json_decode($xmlAsJson, TRUE);
    print_r($xmlArray);
}


function get_only_desired_elements($xml)
{
    global $metadata, $numPosts;
    $retArray = array();
    $itemsAr = $xml->channel->children();
    $numItems = 0;

    foreach($itemsAr as $item){
        if($numItems == $numPosts) {
            break;
        }
        if($item->getName() == 'item'){

            $retArray[$numItems]['title'] = (string) $item->title;

            if($metadata['creator']){
                $retArray[$numItems]['creator'] = (string) $item->dc->creator;
            }
            if($metadata['pub date']) {
                $retArray[$numItems]['pub date'] = (string) $item->pubDate;
            }
            if($metadata['description']) {
                //$desc = $item->description->attributes();
//                $attribAr = (array) $item->description->attributes();
//                $attribArTwo = $attribAr['@attributes'];
//                $it = (string)$item->description;
                echo 'fgiygsy';
                foreach ($item->description->attributes() as $aaa => $bbb){
                    echo '~~~'. $aaa . $bbb . '</br>';
                }
//                echo '</br></br>' . $item->title . '</br>';
//                var_dump($attribAr);
//                echo '</br>';
//                var_dump($attribArTwo);
//                echo '</br>';
//                var_dump($desc);
//                echo '</br>';
//                echo strrpos($it, 'src');
                //$retArray[$numItems]['description'] = (string)$item->description;
            }
            $numItems++;
        }
    }
    return $retArray;
}


function create_blog_feed()
{
    echo "NEW SANITY CHECK: WORKS AS OF JUNE 1 2:36</br></br>";

    $feed = file_get_contents($_SERVER["DOCUMENT_ROOT"] . "/_testing/anna-h/blog/_feeds/blog-articles-xml.xml");
    $xml = simplexml_load_string($feed);

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
