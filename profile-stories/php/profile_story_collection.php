<?php
/**
 * Created by PhpStorm.
 * User: ces55739
 * Date: 9/2/14
 * Time: 11:28 AM
 */
    // Todo: I am pretty certain we don't use profile story collections.

    include_once $_SERVER["DOCUMENT_ROOT"] . "/code/php_helper_for_cascade.php";

    function show_individual_profile_stories($stories)
    {
        $file = $_SERVER["DOCUMENT_ROOT"] . "/_shared-content/xml/profile-stories.xml";
        $xml = autoCache("simplexml_load_file", array($file));

        $html = "";
        foreach($stories as $story){
            $search = "//system-page[path='/$story']";
            $results = $xml->xpath($search);
            $val = get_profile_stories_html($results[0]);
            $html .= carousel_item($val, "", null, $print=false);
        }
        carousel_create("js-rotate-order-carousel  js-load-on-demand", $html);
    }

    function show_profile_story_collection($numItems, $School, $Topic, $CAS, $CAPS, $GS, $SEM){
        $categories = array( $School, $Topic, $CAS, $CAPS, $GS, $SEM );

        //todo Clean up using $_SERVER

        $profileStoriesArray = autoCache("get_xml_profile_stories", array($_SERVER["DOCUMENT_ROOT"] . "/_shared-content/xml/profile-stories.xml"));

        $html = "";
        foreach( $profileStoriesArray as $profileStory )
        {
            $html .= carousel_item($profileStory, "", null, $print=false);
        }
        carousel_create("js-rotate-order-carousel  js-load-on-demand", $html);
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

                if( $profileStory['display'] == "Metadata Matches"){
                    array_push($profileStories, $profileStory['html']);
                }
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
            $page_info['html'] = get_profile_stories_html($xml);
            $page_info['display'] = match_metadata_profile_stories($xml, $categories);

        }
        return $page_info;
    }

    // Returns the profile stories html
    function get_profile_stories_html( $xml){
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

        $twig = makeTwigEnviron('/code/profile-stories/twig');
        $html = $twig->render('profile_story_collection.html', array(
            'path' => $xml->path,
            'thumborURL' => thumborURL($imagePath, 1500, true, false, $teaser),
            'quote' => $quote,
            'teaser' => $teaser)
        );

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
