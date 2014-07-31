<?php
/**
 * Created by PhpStorm.
 * User: ces55739
 * Date: 7/31/14
 * Time: 5:08 PM
 */

    function create_profile_story_hub(){
        $profileStoriesArray = array(
            "bu" => array(),
            "cas" => array(),
            "gs" => array(),
            "sem" => array(),
            "caps" => array(),
        );

        ///////////////// Change to cms.pub instead of staging/public??
        $profileStories = get_xml_profile_story_hub("/var/www/staging/public/_shared-content/xml/profile-stories.xml");

        // Divide the single large array in the 4-5 school arrays, then put them back together.
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

    function get_xml_profile_story_hub($fileToLoad ){
        $xml = simplexml_load_file($fileToLoad);
        $profileStories = array();
        $profileStories = traverse_folder_profile_story_hub($xml, $profileStories);
        return $profileStories;
    }

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

    function get_profile_story_hub_html( $page_info, $xml){
        $ds = $xml->{'system-data-structure'};
        $imagePath = $ds->{'images'}->{'homepage-image'}->path;
        $viewerTeaser = $ds->{'viewer-teaser'};
        $homepageTeaser = $ds->{'homepage-teaser'};
        $quote = $ds->{'quote'};
        $html = '<p><a href="http://www.bethel.edu/'.$xml->path.'">'.$page_info['title'].'</a></p>';
        $html .= '<img src="//cdn1.bethel.edu/resize/unsafe/400x0/smart/http://staging.bethel.edu'.$imagePath.'" class="image-replace" alt="" data-src="//cdn1.bethel.edu/resize/unsafe/{width}x0/smart/http://staging.bethel.edu'.$imagePath.'" width="400">';
        if( $viewerTeaser != "")
            $html .= '<p>'.$viewerTeaser.'</p>';
        elseif( $homepageTeaser != "")
            $html .= '<p>'.$homepageTeaser.'</p>';

        return $html;
    }

    function match_metadata_profile_story_hub($xml, $page_info){
        foreach ($xml->{'dynamic-metadata'} as $md){

            $name = $md->name;

            foreach($md->value as $value ){
                if($value == "Select" || $value == "select"){
                    continue;
                }
                if( $name == "school"){
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