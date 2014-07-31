<?php
/**
 * Created by PhpStorm.
 * User: ces55739
 * Date: 7/29/14
 * Time: 2:59 PM
 */

    // Page metadata
    $School;
    $Topic;

    // Stories that will be added for sure.
    // An array of names(strings).
    $userSelectedProfileStories;

    // Should stories be added based on metadata.
    $AddMetadata;

    function get_profile_stories(){
        ///////////////// Change to cms.pub instead of staging/public??
        $profileStoriesArray = get_xml_profile_stories("/var/www/staging/public/_shared-content/xml/profile-stories.xml");

        return $profileStoriesArray;
    }

    function get_xml_profile_stories($fileToLoad ){
        $xml = simplexml_load_file($fileToLoad);
        $profileStories = array();
        $profileStories = traverse_folder_profile_stories($xml, $profileStories);
        return $profileStories;
    }

    function traverse_folder_profile_stories($xml, $profileStories){
        foreach ($xml->children() as $child) {

            $name = $child->getName();

            if ($name == 'system-folder'){
                $profileStories = traverse_folder_profile_stories($child, $profileStories);
            }elseif ($name == 'system-page'){
                // Set the page data.
                $profileStory = inspect_page_profile_stories($child);

                if( $profileStory['display'] == "Yes")
                    array_push($profileStories, $profileStory['html']);
            }
        }

        return $profileStories;
    }

    function inspect_page_profile_stories($xml){
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

            $page_info['display'] = match_metadata_profile_stories($xml);

            global $userSelectedProfileStories;
            if( in_array($xml->name, $userSelectedProfileStories) )
            {
                $page_info['display'] = "Yes";
            }
        }
        return $page_info;
    }

    function get_profile_stories_html( $block_info, $xml){
        $ds = $xml->{'system-data-structure'};
        $imagePath = $ds->{'images'}->{'homepage-image'}->path;
        $viewerTeaser = $ds->{'viewer-teaser'};
        $quote = $ds->{'quote'};
        $html = '<a class="carousel-item" href="http://www.bethel.edu/'.$xml->path.'">';
            $html .= '<img src="//cdn1.bethel.edu/resize/unsafe/3000x0/smart/http://staging.bethel.edu'.$imagePath.'" class="image-replace" alt="" data-src="//cdn1.bethel.edu/resize/unsafe/{width}x0/smart/http://staging.bethel.edu'.$imagePath.'" width="3000">';
            $html .= '<figure class="feature__figure--sulley">';
            $html .= '<blockquote class="feature__blockquote--sulley">“'.$quote.'”</blockquote>';
            $html .= '<figcaption class="feature__figcaption--sulley">'.$viewerTeaser.'</figcaption>';
            $html .= '</figure>';
        $html .= '</a>';

        return $html;
    }

    function match_metadata_profile_stories($xml){
        global $School;
        global $Topic;
        foreach ($xml->{'dynamic-metadata'} as $md){

            $name = $md->name;

            foreach($md->value as $value ){
                if($value == "Select" || $value == "none"){
                    continue;
                }
                if( $name == "school")
                {
                    if (in_array($value, $School)){
                        return "Yes";
                    }
                }
                elseif( $name == "topic")
                {
                    if (in_array($value, $Topic)){
                        return "Yes";
                    }
                }
            }
        }
        return "No";
    }


?>