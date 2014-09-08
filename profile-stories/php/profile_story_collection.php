<?php
/**
 * Created by PhpStorm.
 * User: ces55739
 * Date: 9/2/14
 * Time: 11:28 AM
 */
    global $destinationName;

    function show_profile_story_collection($School, $Topic, $CAS, $CAPS, $GS, $SEM){
        $categories = array( $School, $Topic, $CAS, $CAPS, $GS, $SEM );
        global $destinationName;


        //todo Clean up using $_SERVER
        //todo Do we need Destination name?

        if( strstr(getcwd(), "staging/public") ){
            include_once "/var/www/staging/public/code/php_helper_for_cascade.php";
            $profileStoriesArray = get_xml_profile_stories("/var/www/staging/public/_shared-content/xml/profile-stories.xml", $categories);

            $destinationName = "staging";
        }
        else{ // Live site.
            include_once "/var/www/cms.pub/code/php_helper_for_cascade.php";
            $profileStoriesArray = get_xml_profile_stories("/var/www/cms.pub/_shared-content/xml/profile-stories.xml", $categories);

            $destinationName = "www";
        }

        foreach( $profileStoriesArray as $profileStory )
        {
            echo $profileStory;
        }
        return;
    }

    // Converts and xml file to an array of profile stories
    function get_xml_profile_stories($fileToLoad, $categories ){
        $xml = simplexml_load_file($fileToLoad);
        $profileStories = array();

        $profileStories = traverse_folder_profile_stories($xml, $profileStories, $categories);
        return $profileStories;
    }

    // Traverse through the xml structure.
    function traverse_folder_profile_stories($xml, $profileStories, $categories){
        foreach ($xml->children() as $child) {

            $name = $child->getName();

            if ($name == 'system-folder'){
                $profileStories = traverse_folder_profile_stories($child, $profileStories, $categories);
            }elseif ($name == 'system-page'){
                // Set the page data.
                $profileStory = inspect_page_profile_stories($child, $categories);

                if( $profileStory['display'] == "Metadata Matches")
                    array_push($profileStories, $profileStory['html']);
            }
        }

        return $profileStories;
    }

    // Gathers the info/html of the page.
    function inspect_page_profile_stories($xml, $categories){
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
        if( $dataDefinition == "Profile Story")
        {
            // Get html
            $page_info['html'] = get_profile_stories_html($page_info, $xml);

            $page_info['display'] = match_metadata_profile_stories($xml, $categories);

        }
        return $page_info;
    }

    // Returns the profile stories html
    function get_profile_stories_html( $block_info, $xml){
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
        $html = '<a class="carousel-item" href="http://bethel.edu'.$xml->path.'">';
            $img_path = "//cdn1.bethel.edu/resize/unsafe/{width}x0/smart/http://www.bethel.edu$imagePath";
            $html .= "<div class='delayed-image-load' data-class='feature__img' data-src='$img_path' data-alt='$imgDesc'></div>";
            $html .= '<figure class="feature__figure">';
            $html .= "<blockquote class='feature__blockquote'>$quote</blockquote>";
            $html .= '<figcaption class="feature__figcaption">'.$teaser.'</figcaption>';
            $html .= '</figure>';
        $html .= '</a>';

        return $html;
    }

    // matches the metadata of the page to the metadata of the profile stories
    function match_metadata_profile_stories($xml, $categories){
        foreach ($xml->{'dynamic-metadata'} as $md){

            $name = $md->name;

            foreach($md->value as $value ){
                if($value == "Select" || $value == "none"){
                    continue;
                }

                if( $name == "school")
                {
                    if (in_array($value, $categories[0])){
                        return "Metadata Matches";
                    }
                }
                elseif( $name == "topic")
                {
                    if (in_array($value, $categories[1])){
                        return "Metadata Matches";
                    }
                }
                elseif( $name == "department")
                {
                    if (in_array($value, $categories[2])){
                        return "Metadata Matches";
                    }
                }
                elseif( $name == "adult-undergrad-program")
                {
                    if (in_array($value, $categories[3])){
                        return "Metadata Matches";
                    }
                }
                elseif( $name == "graduate-program")
                {
                    if (in_array($value, $categories[4])){
                        return "Metadata Matches";
                    }
                }
                elseif( $name == "seminary-program")
                {
                    if (in_array($value, $categories[5])){
                        return "Metadata Matches";
                    }
                }
            }
        }
        return "No";
    }


?>
