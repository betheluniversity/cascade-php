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

$readMoreLink;

function print_s($thing){
    echo '<pre>';
    echo print_r($thing);
    echo '</pre>';
}


function set_num_posts($from_cascade){
    global $numPosts;
    $numPosts = (int) $from_cascade;
}


function set_read_more_link($type, $text){
    global $readMoreLink;
    echo "</br>Include Blog Link:" . $type;
    echo "</br>Custom Link Text" . $text;
    $readMoreLink = $text;
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
        if($correct_string == "all"){
            $categories[] = TRUE;
        } else {
            $categories[] = "$correct_string";
        }
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

    global $categories;
    var_dump($categories);
}


function post_matches_cats($post){
    global $categories;
    foreach($post->category as $cat){
        if(in_array($cat, $categories)){
            return true;
        }
    }
    return false;
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
    $descToString = "<root>$item</root>".PHP_EOL;
    $stringToObj = simplexml_load_string($descToString);
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
            if($categories['all'] || post_matches_cats($item)){

                $description = get_description_as_array($item);
                $retArray[$numItems] = array('creator' => 'hidden',
                                            'categories' => 'hidden',
                                            'pub date' => 'hidden',
                                            'description' => 'hidden',
                                            'image' => 'hidden');
                $retArray[$numItems]['title'] = (string) $item->title;
                $retArray[$numItems]['link'] = (string) $description['a']['@attributes']['href'];



                if ($metadata['creator']) {
                    $dcNamespace = $item->children($allNamespaces['dc']);
                    $retArray[$numItems]['creator'] = (string)$dcNamespace->creator[0];
                }
                if ($metadata['categories']){
                    $retArray[$numItems]['categories'] = " ";
                    foreach($item->category as $cat){
                        $retArray[$numItems]['categories'] = (string)$cat;
                    }
                }
                if ($metadata['pub date']) {
                    $retArray[$numItems]['pub date'] = (string) $item->pubDate;
                }
                if ($metadata['description']) {
                    $retArray[$numItems]['description'] = (string)$description['p'][0];
                }
                if ($metadata['image']) {
                    $retArray[$numItems]['image'] = (string)$description['a']['img']['@attributes']['src'];
                }
                $numItems++;
            }
        }
    }
    return $retArray;
}


function create_blog_feed()
{
    global $allNamespaces, $readMoreLink;
    echo "CURRENT AS OF JUNE 6 9:09</br></br>";

    $feed = file_get_contents($_SERVER["DOCUMENT_ROOT"] . "/_testing/anna-h/blog/_feeds/blog-articles-xml.xml");
    $xml = simplexml_load_string($feed);

    $allNamespaces = $xml->getDocNamespaces(TRUE);

    $postInfo = get_only_desired_elements($xml);
    $postsInHTML = reformat_post_info($postInfo);

    return $postsInHTML;
}


function reformat_post_info($raw_post_info_ar){
    $formatted = array();
    foreach($raw_post_info_ar as $post){
        $formatted[] = get_post_html($post);
    }
    return $formatted;
}


// Returns the html of the news article
function get_post_html( $post){
    $twig = makeTwigEnviron('/code/news/twig');
    $html = $twig->render('blog_post_feed.html', array('post' => $post));

    return $html;
}


?>
