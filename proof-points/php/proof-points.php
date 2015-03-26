<?php
/**
 * Created by PhpStorm.
 * User: ces55739
 * Date: 3/9/15
 * Time: 10:36 AM
 */

/*
    1) create render proof point function
    2) create a get_proof_points( $numProofPoints )
        a) Pull ALL proof points\
        b) return X of them
    3) add to the /code/general-cascade/carousel-viewer.php file to allow for proof-point carousels.

    // Page metadata
    $School = array();
    $Topic = array();
    $CAS = array();
    $CAPS = array();
    $GS = array();
    $SEM = array();
 */

    include_once 'proof-point-logic.php';
    function show_proof_point_collection($numItems, $School, $Topic, $CAS, $CAPS, $GS, $SEM, $pageSchool='', $pageDepartment=''){

        echo "<!-- new proof points -->";

        $categories = array( $School, $Topic, $CAS, $CAPS, $GS, $SEM );
        $xml = simplexml_load_file($_SERVER['DOCUMENT_ROOT'] ."/_shared-content/xml/proof-points.xml");
        $proof_points = $xml->xpath("//system-block");
        $matches = array();

        foreach($proof_points as $proof_point_xml){
            $ppInfo = inspect_block_proof_points($proof_point_xml, $pageSchool, $pageDepartment);
            if( $ppInfo['match-school'] || $ppInfo['match-dept']){
                array_push($matches, $ppInfo);
            }
        }

        // sort into premium, subject, school, etc...
        $proofPointsArrays = divide_into_arrays_proof_points($matches);

        // Get random selection of $numItems proof points
        $proofPointsToDisplay = get_x_proof_points( $proofPointsArrays, $numItems );

        $numProofPoints = count($proofPointsToDisplay);

        //Output structure
        gridOpen("proof-points proof-point-collection");

        foreach($proofPointsToDisplay as $finalPP){
            gridCellOpen("medium 1-$numProofPoints animate animate--fadeIn");
            echo $finalPP['html'];
            gridCellClose();
        }
        gridClose();
    }

    // Returns the html of the proof point
    function get_proof_point_html($xml){
        $ds = $xml->{'system-data-structure'};
        $type = $ds->{'proof-point'}->{'type'};
        $html = "";
        if($type == "Text"){
            $html = text_pp_html($ds);
        }
        elseif($type == "Number"){
            $html = number_pp_html($ds);
        }
        return $html;
    }

function number_pp_html($ds){
    $number = $ds->{'proof-point'}->{'number-group'}->{'number-field'};
    if(!$number){
        $number = $ds->{'proof-point'}->{'number-group'}->{'number'};
    }
    $textBefore = $ds->{'proof-point'}->{'number-group'}->{'text-before'};
    $textAfter = $ds->{'proof-point'}->{'number-group'}->{'text-after'};
    $animate = $ds->{'proof-point'}->{'number-group'}->{'animate'};

    $textBelow = $ds->{'proof-point'}->{'number-group'}->{'text-below'};
    $source = $ds->{'proof-point'}->{'number-group'}->{'source'};

    $html = '<div class="proof-point  center">';
    $html .= '<p class="proof-point__text">';

    $html .= '<span class="proof-point__number">';

    if($textBefore){
        $html .= $textBefore;
    }
    //UPDATE THIS BEFORE RESPONSIVE IS LIVE
    $html .= "<span class='odometer' data-final-number='$number'>0</span>";
    if($textAfter){
        $html .= $textAfter;
    }

    $html .= '</span><br>';

    $html .= $textBelow;
    $html .= '</p>';
    if ($source != "")
        $html .= '<cite class="proof-point__cite">-' . $source . '</cite>';
    $html .= '</div>';
    return $html;
}

function text_pp_html($ds){
    $mainText = $ds->{'proof-point'}->{'text'}->{'main-text'};
    $source = $ds->{'proof-point'}->{'text'}->{'source'};
    $html = '<div class="proof-point  center">';
    $html .= '<p class="h2 mb0">' . $mainText . '</p>';
    if ($source != "")
        $html .= '<cite class="proof-point__cite">-' . $source . '</cite>';
    $html .= '</div>';
    return $html;
}


