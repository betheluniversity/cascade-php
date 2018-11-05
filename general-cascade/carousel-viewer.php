<?php
/**
 * Created by PhpStorm.
 * User: ces55739
 * Date: 8/25/14
 * Time: 3:30 PM
 */

    function get_carousel_viewer(){
        echo "hello";
    }

    class Carousel {

        private $SchoolValues;
        private $DeptValues;

        private $Type;
        private $NumItems;

        private $CarouselItems;

        public function __contruct($schoolMetadata, $deptMetadata, $type, $numItems){
            $this->SchoolValues = $schoolMetadata;
            $this->DeptValues = $deptMetadata;

            $this->NumItems = $numItems;
            $this->Type = $type;
            if( $type == "Proof points")
            {
                if( strstr(getcwd(), "/staging") ){
                    $this->CarouselItems = get_xml_carousel_items("/var/www/staging/_shared-content/xml/proof-points.xml", $schoolMetadata, $deptMetadata);
                }
                else{ //if( strstr(getcwd(), "cms.pub") )
                    $this->CarouselItems = get_xml_carousel_items("/var/www/cms.pub/_shared-content/xml/proof-points.xml", $schoolMetadata, $deptMetadata);
                }
            }
            elseif( $type == "Quotes")
            {
                if( strstr(getcwd(), "/staging") ){
                    $this->CarouselItems = get_xml_carousel_items("/var/www/staging/_shared-content/xml/quotes.xml", $schoolMetadata, $deptMetadata);
                }
                else{ //if( strstr(getcwd(), "cms.pub") )
                    $this->CarouselItems = get_xml_carousel_items("/var/www/cms.pub/_shared-content/xml/quotes.xml", $schoolMetadata, $deptMetadata);
                }
            }
            elseif( $type == "Profile Stories")
            {
                if( strstr(getcwd(), "/staging") ){
                    $this->CarouselItems = get_xml_carousel_items("/var/www/staging/_shared-content/xml/profile-stories.xml", $schoolMetadata, $deptMetadata);
                }
                else{ //if( strstr(getcwd(), "cms.pub") )
                    $this->CarouselItems = get_xml_carousel_items("/var/www/cms.pub/_shared-content/xml/profile-stories.xml", $schoolMetadata, $deptMetadata);
                }
            }
        }

        public function makeCarousel($type, $numItems){

            // html = header

            //for( $arrayOfItems by $numItems )
                // html .= wrapper
                // html .= $arrayOfItems[$i]['html'];
                // html .= ending wrapper



            // html .= footer

            //return html;

        }

        // Traverse through the proof points
        function traverse_folder_carousel($xml, $carouselItems, $schoolMetadata, $deptMetadata){
            foreach ($xml->children() as $child) {

                $name = $child->getName();

                if ($name == 'system-folder'){
                    $carouselItems = traverse_folder_carousel($child, $carouselItems, $schoolMetadata, $deptMetadata);
                }elseif ($name == 'system-block'){
                    // Set the page data.
                    $carouselItem = inspect_carousel_item($child, $schoolMetadata, $deptMetadata);

                    if( $carouselItem['match-school'] == "Yes" || $carouselItem['match-dept'] == "Yes")
                        array_push($carouselItems, $carouselItem);
                }
            }

            return $carouselItems;
        }

        // Gathers the info/html of the proof point
        function inspect_carousel_item($xml, $schoolMetadata, $deptMetadata){
            $block_info = array(
                "display-name" => $xml->{'display-name'},
                "published" => $xml->{'last-published-on'},
                "description" => $xml->{'description'},
                "path" => $xml->path,
                "html" => "",
                "display" => "No",
                "premium-proof-point" => "No",
                "match-school" => "No",
                "match-dept" => "No",
            );

            $ds = $xml->{'system-data-structure'};
            $dataDefinition = $ds['definition-path'];
            if( $dataDefinition == "Blocks/Quotes")
            {
                $block_info['html'] = "QUOTE";
                $block_info['match-school'] = match_metadata_carousel($xml, $schoolMetadata);
                $block_info['match-dept'] = match_metadata_carousel($xml, $deptMetadata);
            }
            elseif( $dataDefinition == "Profile Story")
            {
                $block_info['html'] = "PROFILE STORY";
                $block_info['match-school'] = match_metadata_carousel($xml, $schoolMetadata);
                $block_info['match-dept'] = match_metadata_carousel($xml, $deptMetadata);
            }
            return $block_info;
        }

    }

    // Matches the metadata of the page against the metadata of the proof point
    function match_metadata_carousel($xml, $category){
        foreach ($xml->{'dynamic-metadata'} as $md){

            $name = $md->name;

            $options = array('school', 'department');

            foreach($md->value as $value ){
                if($value == "Select" || $value == "none"){
                    continue;
                }
                if (in_array($name,$options)){
                    if (in_array($value, $category)){
                        return "Yes";
                    }
                }
            }
        }
        return "No";
    }

?>