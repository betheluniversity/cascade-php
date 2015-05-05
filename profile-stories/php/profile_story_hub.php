<?php
/**
 * Created by PhpStorm.
 * User: ces55739
 * Date: 7/31/14
 * Time: 5:08 PM
 */

    // Staging Site
    if( strstr(getcwd(), "staging/public") ){
        $destinationName = "staging";
    }
    else{ // Live site.
        $destinationName = "www";
    }

    include_once $_SERVER["DOCUMENT_ROOT"] . "/code/php_helper_for_cascade.php";

    // The controller for this section of PHP
    function create_profile_story_hub(){
        $profileStoriesArray = array(
            "bu" => array(),
            "cas" => array(),
            "gs" => array(),
            "sem" => array(),
            "caps" => array(),
        );

        global $destinationName;
        if( $destinationName == "staging" ){
            $profileStories = autoCache("get_xml_profile_story_hub", array($_SERVER["DOCUMENT_ROOT"] . "/_shared-content/xml/profile-stories.xml"), 'profile_story_hub_staging'.$_SERVER['REQUEST_URI']);
        }
        else{
            $profileStories = autoCache("get_xml_profile_story_hub", array("/var/www/cms.pub/_shared-content/xml/profile-stories.xml"), 'profile_story_hub'.$_SERVER['REQUEST_URI']);
        }
        // Divide the single large array in the 4-5 school arrays, then put them back together.
        // I would prefer a dynamic way to do this.
        foreach($profileStories as $profileStory)
        {
            if ( $profileStory['bu'] == "Yes" ){
                array_push($profileStoriesArray['bu'], $profileStory['html'] );
            }
            if ( $profileStory['cas'] == "Yes" ){
                array_push($profileStoriesArray['cas'], $profileStory['html'] );
            }
            if ( $profileStory['gs'] == "Yes" ){
                array_push($profileStoriesArray['gs'], $profileStory['html'] );
            }
            if ( $profileStory['sem'] == "Yes" ){
                array_push($profileStoriesArray['sem'], $profileStory['html'] );
            }
            if ( $profileStory['caps'] == "Yes" ){
                array_push($profileStoriesArray['caps'], $profileStory['html'] );
            }
        }

        return $profileStoriesArray;
    }

    // Takes a xml file and converts to an array of profile stories
    function get_xml_profile_story_hub($fileToLoad ){
        $xml = simplexml_load_file($fileToLoad);
        $profileStories = array();
        $profileStories = traverse_folder_profile_story_hub($xml, $profileStories);
        return $profileStories;
    }

    // Traverses through to return an array of displayable profile stories
    // It currently returns ALL profile stories.
    function traverse_folder_profile_story_hub($xml, $profileStories){
        foreach ($xml->children() as $child) {

            $name = $child->getName();

            if ($name == 'system-folder'){
                $profileStories = traverse_folder_profile_story_hub($child, $profileStories);
            }elseif ($name == 'system-page'){
                // Set the page data.
                $profileStory = inspect_page_profile_story_hub($child);

                array_push($profileStories, $profileStory);
            }
        }

        return $profileStories;
    }

    // Gathers the info/html of the profile story
    function inspect_page_profile_story_hub($xml){
        $page_info = array(
            "title" => $xml->title,
            "display-name" => $xml->{'display-name'},
            "published" => $xml->{'last-published-on'},
            "description" => $xml->{'description'},
            "path" => $xml->path,
            "html" => "",
            "bu" => "No",
            "cas" => "No",
            "gs" => "No",
            "sem" => "No",
            "caps" => "No",
        );

        $ds = $xml->{'system-data-structure'};
        $dataDefinition = $ds['definition-path'];
        if( $dataDefinition == "Profile Story")
        {
            // Get html
            $page_info['html'] = get_profile_story_hub_html($page_info, $xml);

            //put into array
            $page_info = match_metadata_profile_story_hub($xml, $page_info);
        }
        return $page_info;
    }

    // Returns the html of the profile story
    function get_profile_story_hub_html( $page_info, $xml){
        $ds = $xml->{'system-data-structure'};
        $imagePath = $ds->{'images'}->{'homepage-image'}->path;
        $viewerTeaser = $ds->{'viewer-teaser'};
        $homepageTeaser = $ds->{'homepage-teaser'};

        // Should this quote be used? It seems to take up too much space.
        $quote = $ds->{'quote'};

        global $destinationName;

        $twig = makeTwigEnviron('/code/profile_stories/twig');
        $html = $twig->render('profile_story_hub.html', array(
            'destinationName' => $destinationName,
            'viewerTeaser' => $viewerTeaser,
            'homepageTeaser' => $homepageTeaser,
            'title' => $page_info['title'],
            'path' => $xml->path,
            'thumborURL' => thumborURL($imagePath, 400, false, false)));

        return $html;
    }

    // Divides the profile stories into 5 categories of schools based on metadata.
    function match_metadata_profile_story_hub($xml, $page_info){
        foreach ($xml->{'dynamic-metadata'} as $md){

            $name = $md->name;

            foreach($md->value as $value ){
                if($value == "Select" || $value == "select"){
                    continue;
                }
                if( $name == "school"){
                    // I would prefer a dynamic way to do this.
                    if ( $value == "Bethel University" ){
                        $page_info['bu'] = "Yes";
                    }
                    elseif ( $value == "College of Arts & Sciences" ){
                        $page_info['cas'] = "Yes";
                    }
                    elseif ( $value == "Graduate School" ){
                        $page_info['gs'] = "Yes";
                    }
                    elseif ( $value == "Bethel Seminary" ){
                        $page_info['sem'] = "Yes";
                    }
                    elseif ( $value == "College of Adult & Professional Studies" ){
                        $page_info['caps'] = "Yes";
                    }
                }
            }
        }
        return $page_info;
    }

?>