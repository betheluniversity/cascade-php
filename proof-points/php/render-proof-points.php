<?php
/**
 * Created by PhpStorm.
 * User: ces55739
 * Date: 7/25/14
 * Time: 1:13 PM
 */

    // Globals
    $PageSchool;
    $PageDepartment;

    function get_proof_points($numToFind){
        $finalProofPoints = array();
        global $PageSchool;
        global $PageDepartment;

        ///////////////// Change to cms.pub instead of staging/public??
        $proofPointsArray = get_xml("/var/www/staging/public/_shared-content/xml/proof-points1.xml", $PageSchool, $PageDepartment);

        // Convert the single array into the x(or 4) number of arrays needed.
        $proofPointsArrays = divide_into_arrays($proofPointsArray);

        // $proofPoints should be an array of arrays.
        $proofPointsToDisplay = get_x_proof_points( $proofPointsArrays, $numToFind );

        return $proofPointsToDisplay;
    }

    function get_xml($fileToLoad, $PageSchool, $PageDepartment ){
        $xml = simplexml_load_file($fileToLoad);
        $pages = array();
        $pages = traverse_folder($xml, $pages, $PageSchool, $PageDepartment);
        return $pages;
    }

    function traverse_folder($xml, $proofPoints, $PageSchool, $PageDepartment){
        foreach ($xml->children() as $child) {

            $name = $child->getName();

            if ($name == 'system-folder'){
                $proofPoints = traverse_folder($child, $proofPoints, $PageSchool, $PageDepartment);
            }elseif ($name == 'system-block'){
                // Set the page data.
                $proofPoint = inspect_block($child, $PageSchool, $PageDepartment);

                //////////////// NEED TO DO /////////////////
                // Add to 1 of 4 arrays.
                if( $proofPoint['match-school'] == "Yes" || $proofPoint['match-dept'] == "Yes")
                    array_push($proofPoints, $proofPoint);
            }
        }

        return $proofPoints;
    }

    function inspect_block($xml, $PageSchool, $PageDepartment){
        $block_info = array(
            "display-name" => $xml->{'display-name'},
            "published" => $xml->{'last-published-on'},
            "description" => $xml->{'description'},
            "path" => $xml->path,
            "md" => array(),
            "html" => "",
            "display" => "No",
            "premium" => "No",
            "match-school" => "No",
            "match-dept" => "No",
        );

        $ds = $xml->{'system-data-structure'};
        $dataDefinition = $ds['definition-path'];
        if( $dataDefinition == "Blocks/Proof Point")
        {
            $nodes = $xml->{'dynamic-metadata'};
            foreach( $nodes as $node){
                if( $node->name == "premium-proof-point")
                {
                    if( $node->value == "Yes")
                    {
                        $block_info['premium'] = "Yes";
                    }
                }
            }

            $block_info['match-school'] = match_metadata($xml, $PageSchool);
            $block_info['match-dept'] = match_metadata($xml, $PageDepartment);

            // Get html
            $block_info['html'] = get_proof_point_html($block_info, $ds);
        }
        return $block_info;
    }

    function get_proof_point_html( $block_info, $ds){
        $type = $ds->{'proof-point'}->{'type'};
        $html = "";

        if( $type == "Text")
        {
            $mainText = $ds->{'proof-point'}->{'text'}->{'main-text'};
            $source = $ds->{'proof-point'}->{'text'}->{'source'};

            $html = '<div class="proof-point  center">';
            $html .= '<p class="h2 mb0">'.$mainText.'</p>';
            if( $source != "")
                $html .= '<cite class="proof-point__cite">-'.$source.'</cite>';
            $html .= '</div>';
        }
        elseif( $type == "Number"){
            $number = $ds->{'proof-point'}->{'number-group'}->{'number'};
            $textBelow = $ds->{'proof-point'}->{'number-group'}->{'text-below'};
            $source = $ds->{'proof-point'}->{'number-group'}->{'source'};

            $html = '<div class="proof-point  center">';
            $html .= '<p class="proof-point__text">';
            $html .= '<span class="proof-point__number">'.$number.'</span><br>';
            $html .= $textBelow;
            $html .= '</p>';
            if( $source != "")
                $html .= '<cite class="proof-point__cite">-'.$source.'</cite>';

            $html .= '</div>';
        }

        return $html;
    }

    function match_metadata($xml, $category){
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

    // Not very well constructed.
    // Down the road, this should probably be rewritten.
    function get_x_proof_points($proofPointsArrays, $numToFind){
        $finalProofPoints = array();
        foreach( $proofPointsArrays as $proofPointArray)
        {
            $sizeOfArray = sizeof($proofPointArray);
            while( $sizeOfArray > 0)
            {
                if( $numToFind <= 0){
                    break 2;
                }

                $randomIndex = rand(0,$sizeOfArray);
                $proofPoint = $proofPointArray[$randomIndex];

                if( $proofPoint != null)
                {
                    array_push( $finalProofPoints, $proofPoint );
                    unset($proofPointArray[$randomIndex]);
                    array_filter($proofPointArray);
                    $numToFind--;
                }
                else
                {
                    unset($proofPointArray[$randomIndex]);
                    array_filter($proofPointArray);
                }

                $sizeOfArray = sizeof($proofPointArray);
            }
        }
        return $finalProofPoints;
    }

    function divide_into_arrays($proofPointsArrays){
        $schoolPremium = array();
        $school = array();
        $deptPremium = array();
        $dept = array();

        foreach( $proofPointsArrays as $proofPoint){
            if( $proofPoint['match-dept'] == "Yes")
            {
                if($proofPoint['premium'] == "Yes")
                {
                    array_push($deptPremium, $proofPoint);
                }
                else{
                    array_push($dept, $proofPoint);
                }
            }
            elseif( $proofPoint['match-school'] == "Yes")
            {
                if($proofPoint['premium'] == "Yes")
                {
                    array_push($schoolPremium, $proofPoint);
                }
                else{
                    array_push($school, $proofPoint);
                }
            }
        }

        $finalProofPointArrays = array($deptPremium, $dept, $schoolPremium, $school);
        return $finalProofPointArrays;
    }



?>