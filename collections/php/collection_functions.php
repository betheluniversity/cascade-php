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
    if( strstr(getcwd(), "staging/public") ){
        $destinationName = "staging";
    }
    else{ // Live site.
        $destinationName = "www";
    }


    function show_profile_story_collection($numItems, $School, $Topic, $CAS, $CAPS, $GS, $SEM){
        $categories = array( $School, $Topic, $CAS, $CAPS, $GS, $SEM );

        $collectionArray = get_xml_collection($_SERVER["DOCUMENT_ROOT"] . "/_shared-content/xml/profile-stories.xml", $categories);


        display_x_elements_from_array($collectionArray, $numItems);

        return;
    }

    function show_quote_collection($numItems, $School, $Topic, $CAS, $CAPS, $GS, $SEM){
        include_once $_SERVER["DOCUMENT_ROOT"] . "/code/quotes/php/get-quotes.php";
        $categories = array( $School, $Topic, $CAS, $CAPS, $GS, $SEM );
        $collectionArray = get_xml_collection($_SERVER["DOCUMENT_ROOT"] . "/_shared-content/xml/quotes.xml", $categories);

        display_x_elements_from_array($collectionArray, $numItems);

        return;
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
                // Code to make it a carousel
                $html = '<div class="slick-item">';
                $html .= '<div class="pa1  quote  grayLighter">';
                $html .= get_quote_html($xml);
                $html .= '</div></div>';
                $page_info['html'] = $html;
            }
        }

    }

    // Returns the profile stories html
    function get_profile_stories_html( $xml){
        global $destinationName;
        $ds = $xml->{'system-data-structure'};
        // The image that shows up in the 'column' view.
        $imagePath = $ds->{'images'}->{'homepage-image'}->path;
        $viewerTeaser = $ds->{'viewer-teaser'};
        $homepageTeaser = $ds->{'homepage-teaser'};
        if($viewerTeaser == "")
        {
            $teaser = $homepageTeaser;
        }
        else
        {
            $teaser = $viewerTeaser;
        }
        $quote = $ds->{'quote'};
        $html = "<div class='slick-item' style='width:100%'>";
            $html .= '<a href="http://bethel.edu'.$xml->path.'">';

                $html .= "<img src='$imagePath' style='width:100%' />";
                $html .= '<figure class="feature__figure">';
                $html .= '<blockquote class="feature__blockquote">'.$quote.'</blockquote>';
                $html .= '<figcaption class="feature__figcaption">'.$teaser.'</figcaption>';

                $html .= '</figure>';
            $html .= '</a>';
        $html .= "</div>";


        return $html;
    }

?>
