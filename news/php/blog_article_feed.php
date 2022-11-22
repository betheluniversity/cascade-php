<?php

////////////////////////////////////////////////////////////////////////////////////////
/// GLOBAL VARIABLES
////////////////////////////////////////////////////////////////////////////////////////

//// ASSIGNED WITH VELOCITY CODE ////
// Number of posts to display (between 1 and 10)
global $numPosts;
// Feed title
global $includeTitle;
global $feedTitle;
global $completeTitle;
// What kinds of post this feed displays
global $categories;
// Whether or not to meet post the post num
global $meetNumPosts;
// What information to display about a given post
global $metadata;
// Link to the full post
global $hasMoreLink;
global $linkType;
global $linkText;
global $readMoreLink;
// Link to the blog
global $includeBlogLink;
global $customLinkText;
// Assigned when xml is loaded in to avoid recalculating in loops
global $allNamespaces;
global $twigEnv;


////////////////////////////////////////////////////////////////////////////////////////
/// HELPERS FOR VELOCITY CODE
////////////////////////////////////////////////////////////////////////////////////////

// Called by Velocity. Returns the blog feed in the form of an array of html objects.
function create_blog_feed()
{
    // Load RSS+XML File.
    $feed = file_get_contents($_SERVER["DOCUMENT_ROOT"] . "/_rss-feed/blog-wp/blog-articles-xml.xml");
    $xml = simplexml_load_string($feed);
    if(!$xml || !$feed){
        return could_not_load_xml();
    }

    // Find all namespaces in the document (these may be needed when feed is created.)
    global $allNamespaces;
    $allNamespaces = $xml->getDocNamespaces(TRUE);

    // Gather the essential post information from the RSS+XML document provided
    $postInfo = get_only_desired_elements($xml);

    global $meetNumPosts, $numPosts;
    if($meetNumPosts && count($postInfo) < $numPosts){
        unset($postInfo);
        $postInfo = get_only_desired_elements($xml, TRUE);
    }

    // Use the post info to create a formatted html element for each post.
    global $twigEnv;
    $twigEnv = makeTwigEnviron('/code/news/twig');
    $postsInHTML = reformat_post_info($postInfo);

    return $postsInHTML;
}


// Sets the number of posts to display.
function set_num_posts($from_cascade)
{
    global $numPosts;
    $numPosts = (int) $from_cascade;
}


// Sets the title of the feed
function set_title($included, $custom)
{
    global $completeTitle;

    // If this feed will display the title
    if($included == 1){
        // Only use the default if no custom text was specified
        if(strlen($custom) == 0){
            $completeTitle = "Latest Blog Posts";
        } else {
            $completeTitle = $custom;
        }

    // Not displaying a title
    } else {
        $completeTitle = "";
    }
}


// Creates the text for the link to the full post.
function set_read_more_link($type, $text)
{
    global $readMoreLink;

    // If this feed will use "read more" links
    if ($type == 1){
        if(strlen($text) == 0){
            $text = "Read More";
        } // Only use the default if no custom text was specified

    // Not using "read more" links
    } else {
        $text = '';
    }

    $readMoreLink = $text;
}


// Creates the array of metadata this feed will display for each post.
function set_metadata_cats($creator, $pubDate, $categories, $description, $image)
{
    global $metadata;

    $metadata['creator'] = $creator;
    $metadata['pub date'] = $pubDate;
    $metadata['categories'] = $categories;
    $metadata['description'] = $description;
    $metadata['image'] = $image;
}


// Helper for set_categories_cats(). Associates the actual category names with their abbreviated ones.
function setup_individual_category($var_value, $correct_string)
{
    global $categories;
    if($var_value == 1){
        if($correct_string == "all"){
            $categories[] = TRUE;
        } else {
            $categories[] = "$correct_string";
        }
    }
}


// Sets the boolean value representing the policy about matching the desired number of posts in the feed
function set_meet_post_num($meetPosts)
{
    global $meetNumPosts;
    if ($meetPosts == 1){
        $meetNumPosts = TRUE;
        return;
    }
    $meetNumPosts = FALSE;
}


// Creates the array of post categories that this feed will include.
function set_categories_cats($academics, $admissions, $col_exploration, $col_life, $fin_aid, $careers, $advice, $prof_roles, $spiritual, $study, $undergrad, $wellbeing, $all, $admin){
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
    setup_individual_category($undergrad, "Undergrad");
    setup_individual_category($all, "All");
    setup_individual_category($admin, "Admin");
}


////////////////////////////////////////////////////////////////////////////////////////
/// AGGREGATE USEFUL POST INFO
////////////////////////////////////////////////////////////////////////////////////////

// Checks if a given post is within the categories this feed is looking for
function post_matches_cats($post)
{
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
    // Item --> String (root must be added to make XML valid)
    $descToString = "<root>$item->description</root>".PHP_EOL;

    // String --> XML
    $stringToObj = simplexml_load_string($descToString);

    // XML --> Array
    $objToJson = json_encode($stringToObj);
    $jsonToArr = json_decode($objToJson, TRUE);
    return $jsonToArr;
}


// Aggregates posts according to metadata specs, for a given SimpleXMLObject.
function get_only_desired_elements($xml, $noCats = FALSE)
{
    global $readMoreLink, $metadata, $numPosts, $allNamespaces, $categories;
    $retArray = array();
    $itemsAr = $xml->channel->children(); // This contains both posts and other info about the channel.
    $numItems = 0;

    foreach($itemsAr as $item){ // LOOP THROUGH ALL ITEMS
        if($numItems == $numPosts) { // Stop looking for posts if the desired quantity of posts has been met.
            break;
        }

        if($item->getName() == 'item'){ // Things not called "item" in the XML represent things other than posts.
            if($noCats || post_matches_cats($item)){ // Checks if the feed accepts this category of post.

                // Convert information about the description into an easier format
                $description = get_description_as_array($item);

                // Set all post information which may not be included.
                $retArray[$numItems] = array('creator' => 'hidden',
                    'categories' => 'hidden',
                    'pub date' => 'hidden',
                    'description' => 'hidden',
                    'image' => 'hidden');

                // Set all post information which will definitely be included.
                $retArray[$numItems]['title'] = (string) $item->title;
                $retArray[$numItems]['link'] = (string) $description['a']['@attributes']['href'];
                $retArray[$numItems]['read more'] = $readMoreLink;

                // Return to the categories which may or may not be included and set their values accordingly.
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
                    $retArray[$numItems]['pub date'] = format_pub_date((string) $item->pubDate);
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
function reformat_post_info($raw_post_info_ar)
{
    $formatted = array();
    foreach($raw_post_info_ar as $post){
        $formatted[] = get_post_html($post);
    }
    return $formatted;
}


// Helper for reformat_post_info(). Returns the html of the news article from Twig.
function get_post_html( $post)
{
    global $twigEnv;
    $html = $twigEnv->render('blog_post_feed.html', array('post' => $post));
    return $html;
}


// Returns an html representation of the heading, to be called by the Velocity code.
function display_heading()
{
    global $completeTitle;
    return "<h2>" . $completeTitle . "</h2>";
}


// Outputs if a problem occurs.
function could_not_load_xml()
{
    return array("<p>There was a problem loading this Blog Feed.</p>");
}


////////////////////////////////////////////////////////////////////////////////////////
/// Date and Time
////////////////////////////////////////////////////////////////////////////////////////

// Reformats a given publication date to look nicer when displayed
function format_pub_date($dateStr)
{
    $d = array();

    $d['day'] = (int)substr($dateStr, 5, 2);
    $d['mon'] = get_pretty_month(substr($dateStr, 8, 3));
    $d['yer'] = (int)substr($dateStr, 12, 4);
    $d['our'] = (int)substr($dateStr, 17, 2);
    $d['time'] = get_pretty_time($d['our']);

    return "{$d['mon']} {$d['day']}, {$d['yer']} | {$d['time']}";
}


// Helper for date formatting
function get_pretty_month($monStr)
{
    $retMon = "";
    switch($monStr){
        case 'Jan': $retMon = 'January'; break;
        case 'Feb': $retMon = 'February'; break;
        case 'Mar': $retMon = 'March'; break;
        case 'Apr': $retMon = 'April'; break;
        case 'May': $retMon = 'May'; break;
        case 'Jun': $retMon = 'June'; break;
        case 'Jul': $retMon = 'July'; break;
        case 'Aug': $retMon = 'August'; break;
        case 'Sep': $retMon = 'September'; break;
        case 'Oct': $retMon = 'October'; break;
        case 'Nov': $retMon = 'November'; break;
        case 'Dec': $retMon = 'December'; break;
    }
    return $retMon;
}


// Helper for date formatting
function get_pretty_day_of_week($dowStr)
{
    $retDoW = "";
    switch($dowStr){
        case 'Mon': $retDoW = 'Monday'; break;
        case 'Tue': $retDoW = 'Tuesday'; break;
        case 'Wed': $retDoW = 'Wednesday'; break;
        case 'Thu': $retDoW = 'Thursday'; break;
        case 'Fri': $retDoW = 'Friday'; break;
        case 'Sat': $retDoW = 'Saturday'; break;
        case 'Sun': $retDoW = 'Sunday'; break;
    }
    return $retDoW;
}


// Helper for time formatting
function get_pretty_time($hour)
{
    $modHour = $hour % 12;
    if($modHour == $hour){
        if($modHour == 0){
            $modHour = 12;
        }
        $retHour = "{$modHour} a.m.";
    } else {
        if($modHour == 0){
            $modHour = 12;
        }
        $retHour = "{$modHour} p.m.";
    }
    return $retHour;
}

?>