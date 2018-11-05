<?php
/**
 * Created by PhpStorm.
 * User: ces55739
 * Date: 9/2/14
 * Time: 11:28 AM
 */
include_once $_SERVER["DOCUMENT_ROOT"] . "/code/php_helper_for_cascade.php";
//todo Do we need Destination name?
global $destinationName;
// Destination name makes it easier to modify the url from "www.bethel.edu" and "staging.bethel.edu"
if( strstr(getcwd(), "/staging") ){
    $destinationName = "staging";
}
else{ // Live site.
    $destinationName = "www";
}
// Converts and xml file to an array of profile stories
function get_xml_collection($fileToLoad, $categories ){
    $xml = simplexml_load_file($fileToLoad);
    $collection = array();
    $collection = traverse_folder_collection($xml, $collection, $categories);
    return $collection;
}
// Traverse through the xml structure.
function traverse_folder_collection($xml, $collection, $categories){
    foreach ($xml->children() as $child) {
        $name = $child->getName();
        if ($name == 'system-folder'){
            $collection = traverse_folder_collection($child, $collection, $categories);
        }elseif ($name == 'system-page' || $name == 'system-block'){
            // Set the page data.
            $collectionElement = inspect_page_collection($child, $categories);
            if( $collectionElement['display'] == "Yes")
            {
                array_push($collection, $collectionElement['html']);
            }
        }
    }
    return $collection;
}
// Gathers the info/html of the page.
function inspect_page_collection($xml, $categories){
    $page_info = array(
        "display-name" => $xml->{'display-name'},
        "published" => $xml->{'last-published-on'},
        "description" => $xml->{'description'},
        "path" => $xml->path,
        "md" => array(),
        "html" => "",
        "display" => "No",
    );
    $ds = $xml->{'system-data-structure'};
    $dataDefinition = $ds['definition-path'];
    ## This is a carousel
    if( $dataDefinition == "Profile Story")
    {
        $page_info['display'] = match_robust_metadata($xml, $categories);
        if( $page_info['display'] == "Yes")
        {
            $page_info['html'] = get_profile_stories_html($xml);
        }
    }
    ## This is a carousel
    else if( $dataDefinition == "Blocks/Quote")
    {
        $page_info['display'] = match_robust_metadata($xml, $categories);
        if( $page_info['display'] == "Yes" )
        {
            //todo hasn't been tested yet
            $twig = makeTwigEnviron('/code/general-cascade/twig');
            $html = $twig->render('flickity--cell.html', array(
                'html' => get_quote_html($xml)));
            $page_info['html'] = $html;
        }
    }
    return $page_info;
}
// Returns the profile stories html
function get_profile_stories_html( $xml){
    //todo put this is metadata-check
    $twig = makeTwigEnviron('/code/collections/twig');
    global $destinationName;
    $ds = $xml->{'system-data-structure'};
    // The image that shows up in the 'column' view.
    $imagePath = $ds->{'images'}->{'homepage-image'}->path;
    $viewerTeaser = $ds->{'viewer-teaser'};
    $homepageTeaser = $ds->{'homepage-teaser'};
    if($viewerTeaser == "") {
        $teaser = $homepageTeaser;
    } else {
        $teaser = $viewerTeaser;
    }
    $quote = $ds->{'quote'};
    //todo test
    $html = $twig->render('get_profile_stories_html.html', array(
        'quote' => $quote,
        'teaser' => $teaser,
        'image' => srcset($imagePath, false)
    ));
    return $html;
}
?>
