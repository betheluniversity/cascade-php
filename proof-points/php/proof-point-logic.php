<?php
/**
 * Created by PhpStorm.
 * User: ejc84332
 * Date: 3/10/15
 * Time: 9:11 AM
 *
 * This is the logic behind curating proof points. Still kinda messy, could it be streamlined?
 *
 * todo: add cacheing where possible
 */



    // Gets x random proof points from the array of arrays of proof points
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
                    $numToFind--;
                }
                unset($proofPointArray[$randomIndex]);
                array_values($proofPointArray);
                $sizeOfArray = sizeof($proofPointArray);
            }
        }
        return $finalProofPoints;
    }

    // Matches the metadata of the page against the metadata of the proof point
    function match_metadata_proof_points($xml, $block_value){
        foreach ($xml->{'dynamic-metadata'} as $md){
            $name = $md->name;
            foreach($md->value as $value ){
                if($value == "Select" || $value == "none"){
                    continue;
                }
                if ($name == "school" || $name == "department"){
                    if ($value == $block_value){
                        return true;
                    }
                }
            }
        }
        return false;
    }

    // Gathers the info/html of the proof point
    function inspect_block_proof_points($xml, $PageSchool, $PageDepartment){
        $block_info = array(
            "html" => "",
            "display" => false,
            "premium" => false,
            "match-school" => false,
            "match-dept" => false,
        );
        $ds = $xml->{'system-data-structure'};
        $dataDefinition = $ds['definition-path'];

        $nodes = $xml->{'dynamic-metadata'};
        foreach( $nodes as $node){
            if( $node->name == "premium-proof-point")
            {
                if(!$node->value)
                {
                    $block_info['premium'] = false;
                }
            }
        }
        $block_info['match-school'] = match_metadata_proof_points($xml, $PageSchool);
        $block_info['match-dept'] = match_metadata_proof_points($xml, $PageDepartment);
        // Get html
        $block_info['html'] = get_proof_point_html($xml);

        return $block_info;
    }

    // Divide the array of proof points into arrays of dept/school and premium/not-premium.
    // This allows for a priority of what proof points to use.
    function divide_into_arrays_proof_points($proofPointsArrays){
        $schoolPremium = array();
        $school = array();
        $deptPremium = array();
        $dept = array();
        foreach( $proofPointsArrays as $proofPoint){
            if( $proofPoint['match-dept'])
            {
                if($proofPoint['premium'])
                {
                    array_push($deptPremium, $proofPoint);
                }
                else{
                    array_push($dept, $proofPoint);
                }
            }
            elseif( $proofPoint['match-school'])
            {
                if($proofPoint['premium'])
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