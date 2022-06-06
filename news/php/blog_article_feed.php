<?php

// Values are passed in from Cascade
$numPosts;

$includeTitle;
$feedTitle;

$categories;
$metadata;

$hasMoreLink;
$linkType;
$linkTest;
$readMoreLink;

$includeBlogLink;
$customLinkText;

// Assigned when xml is loaded
$allNamespaces;
$twigEnv;


////////////////////////////////////////////////////////////////////////////////////////
/// SETTERS FOR VELOCITY CODE
////////////////////////////////////////////////////////////////////////////////////////

// Called by Velocity. Returns the blog feed in the form of an array of html objects.
function create_blog_feed()
{
    // Load RSS+XML File.
    $feed = file_get_contents($_SERVER["DOCUMENT_ROOT"] . "/_testing/anna-h/blog/_feeds/blog-articles-xml.xml");
    if(!$feed){
        could_not_load_xml();
    }

    $xml = simplexml_load_string($feed);
    if(!$xml){
        could_not_load_xml();
    }

    // Find all namespaces in the document (these may be needed when feed is created.)
    global $allNamespaces;
    $allNamespaces = $xml->getDocNamespaces(TRUE);

    // Gather the essential post information from the RSS+XML document provided
    $postInfo = get_only_desired_elements($xml);

    // Use the post info to create a formatted html element for each post.
    global $twigEnv;
    $twigEnv = makeTwigEnviron('/code/news/twig');
    $postsInHTML = reformat_post_info($postInfo);

    return $postsInHTML;
}


// Sets the number of posts to display.
function set_num_posts($from_cascade){
    global $numPosts;
    $numPosts = (int) $from_cascade;
}


// Creates the text for the link to the full post.
function set_read_more_link($type, $text){
    global $readMoreLink;

    // If this feed will use "read more" links
    if ($type == 1){
        if(strlen($text) == 0){
            $text = "Read More";
        }

    // Not using "read more" links
    } else {
        $text = '';
    }

    $readMoreLink = $text;
}


// Creates the array of metadata this feed will display for each post.
function set_metadata_cats($creator, $pubDate, $categories, $description, $image){
    global $metadata;

    $metadata['creator'] = $creator;
    $metadata['pub date'] = $pubDate;
    $metadata['categories'] = $categories;
    $metadata['description'] = $description;
    $metadata['image'] = $image;
}


// Helper for set_categories_cats(). Associates the actual category names with their abbreviated ones.
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


// Creates the array of post categories that this feed will include.
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


////////////////////////////////////////////////////////////////////////////////////////
/// AGGREGATE USEFUL POST INFO
////////////////////////////////////////////////////////////////////////////////////////

// Checks if a given post is within the categories this feed is looking for
function post_matches_cats($post){
    global $categories;
    foreach($post->category as $cat){
        if(in_array($cat, $categories)){
            return true;
        }
    }
    return false;
}


// Converts the description's SimpleXMLObject into a multidimensional array (for easier use/debugging.)
function get_description_as_array($item)
{
    $descToString = "<root>$item->description</root>".PHP_EOL;
    $stringToObj = simplexml_load_string($descToString);
    $objToJson = json_encode($stringToObj);
    $jsonToArr = json_decode($objToJson, TRUE);

    return $jsonToArr;
}


// Aggregates posts according to metadata specs, for a given SimpleXMLObject.
function get_only_desired_elements($xml)
{
    global $readMoreLink, $metadata, $numPosts, $allNamespaces, $categories;
    $retArray = array();
    $itemsAr = $xml->channel->children(); // This contains both posts and other info about the channel.
    $numItems = 0;

    foreach($itemsAr as $item){ // LOOP THROUGH ALL IT
        if($numItems == $numPosts) { // Stop looking for posts if the desired quantity of posts has been met.
            break;
        }

        if($item->getName() == 'item'){ // Things not called "item" in the XML represent things other than posts.
            if($categories['all'] || post_matches_cats($item)){ // Checks if the feed accepts this category of post.

                // Convert information about the description into an easier format
                $description = get_description_as_array($item);

                // Set all post information which will definitely be included
                $retArray[$numItems]['title'] = (string) $item->title;
                $retArray[$numItems]['link'] = (string) $description['a']['@attributes']['href'];
                $retArray[$numItems]['read more'] = $readMoreLink;

                // Set all post information which may not be included.
                $retArray[$numItems] = array('creator' => 'hidden',
                    'categories' => 'hidden',
                    'pub date' => 'hidden',
                    'description' => 'hidden',
                    'image' => 'hidden');

                if ($metadata['creator']) {
                    $dcNamespace = $item->children($allNamespaces['dc']);
                    $retArray[$numItems]['creator'] = (string)$dcNamespace->creator[0];
                }

                if ($metadata['categories']){
                    $tempCats = array();
                    foreach($item->category as $cat){
                        $tempCats[] = (string)$cat;
                    }
                    $retArray[$numItems]['categories'] = implode(", ", $tempCats);
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

                // Increment number of posts.
                $numItems++;
            }
        }
    }

    // Return results.
    return $retArray;
}


////////////////////////////////////////////////////////////////////////////////////////
/// FORMATTING
////////////////////////////////////////////////////////////////////////////////////////

// Passes post info through Twig and returns it as an array of html.
function reformat_post_info($raw_post_info_ar){
    $formatted = array();
    foreach($raw_post_info_ar as $post){
        $formatted[] = get_post_html($post);
    }
    return $formatted;
}


// Helper for reformat_post_info(). Returns the html of the news article from Twig.
function get_post_html( $post){
    global $twigEnv;
    $html = $twigEnv->render('blog_post_feed.html', array('post' => $post));
    return $html;
}

// Outputs if a problem occurs.
function could_not_load_xml(){
    echo "could not return blog feed";
}

?>
