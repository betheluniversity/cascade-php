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
                    array_push( $finalProofPoints, $proofPoint['html'] );
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
    function match_metadata_proof_points($xml, $block_value_array)
    {
        foreach( $block_value_array as $block_value){
            foreach ($xml->{'dynamic-metadata'} as $md) {
                $name = $md->name;
                foreach ($md->value as $value) {
                    if (strtolower($value) != "select" && strtolower($value) != "none") {
                        if (htmlspecialchars($value) == htmlspecialchars($block_value)) {
                            return true;
                        }
                    }
                }
            }
        }
        return false;
    }

    function match_generic_school_proof_points($xml, $schools){
        $schoolsArray = array();



        foreach ($xml->{'dynamic-metadata'} as $md) {
            foreach ($md->value as $value) {
                if ($value == "Select" || $value == "none" || $value == "None" || $value == "") {
                    continue;
                }

                // Add schools to an array to check later
                if ($md->name == "school") {
                    array_push($schoolsArray, htmlspecialchars($value));
                }

                // if there are any depts, they are not generic. therefore, don't include.
                if ($md->name == "department" || $md->name == "adult-undergrad-program" || $md->name == "graduate-program" || $md->name == "seminary-program") {
                    return false;
                }
            }
        }

        // Fix the values on $schools (it likes to store & as &amp;
        for( $i = 0; $i < sizeof($schools); $i++){
            $schools[$i] = htmlspecialchars($schools[$i]);
        }

        // returns true if the two arrays are equal
        if (sizeof(array_diff_assoc($schoolsArray, $schools)) == 0 ) {
            return true;
        }
        return false;
    }

    // Gathers the info/html of the proof point
    function inspect_block_proof_points($xml, $School, $Topic, $CAS, $CAPS, $GS, $SEM ){

        $block_info = array(
            "html" => "",
            "display" => false,
            "premium" => false,
            "match-school" => false,
            "match-dept" => false,
            "match-topic" => false,
            "animate"   =>  'No',
            "hide" => false
        );
        $ds = $xml->{'system-data-structure'};
        $dataDefinition = $ds['definition-path'];
        $block_info['animate'] = $ds->{'proof-point'}->{'number-group'}->{'animate'};

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
        if($ds->{'hide'} == 'Yes'){
            $block_info['hide'] = true;
        }
        // First get one that matches the specific school dept
        if( match_metadata_proof_points($xml, $CAS) || match_metadata_proof_points($xml, $CAPS) || match_metadata_proof_points($xml, $GS) || match_metadata_proof_points($xml, $SEM)  )
            $block_info['match-dept'] = true;

        // next, grab a GENERIC one from the school ( no depts tagged )
        $block_info['match-school'] = match_generic_school_proof_points($xml, $School);

        // Now that we are desparate, just get one that has a topic thats the same.
        $block_info['match-topic'] = match_metadata_proof_points($xml, $Topic);


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
        $topicPremium = array();
        $topic = array();
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
            elseif( $proofPoint['match-topic'])
            {
                if($proofPoint['premium'])
                {
                    array_push($topicPremium, $proofPoint);
                }
                else{
                    array_push($topic, $proofPoint);
                }
            }
        }
        $finalProofPointArrays = array($deptPremium, $dept, $schoolPremium, $school);
        return $finalProofPointArrays;
    }