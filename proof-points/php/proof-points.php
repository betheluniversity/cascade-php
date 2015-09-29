<?php
/**
 * Created by PhpStorm.
 * User: ces55739
 * Date: 3/9/15
 * Time: 10:36 AM
 */
    require $_SERVER["DOCUMENT_ROOT"] . '/code/vendor/autoload.php';
    include_once 'proof-point-logic.php';
    include_once $_SERVER['DOCUMENT_ROOT'] . "/code/general-cascade/macros.php";


    function show_proof_point_collection($numItems, $School, $Topic, $CAS, $CAPS, $GS, $SEM){

        $proofPointsToDisplay = get_proof_points($numItems, $School, $Topic, $CAS, $CAPS, $GS, $SEM);

        $numProofPoints = count($proofPointsToDisplay);

        $toReturn = "";
        foreach($proofPointsToDisplay as $finalPP){
            // Add an if to add/remove animate depending on the PP
            $toReturn .= createGridCell("medium 1-$numProofPoints animate animate--fadeIn", $finalPP);
        }
        echo createGrid("proof-points proof-point-collection test", $toReturn);

    }

    function get_proof_points($numItems, $School, $Topic, $CAS, $CAPS, $GS, $SEM){
//        $categories = array( $School, $Topic, $CAS, $CAPS, $GS, $SEM );
        $xml = simplexml_load_file($_SERVER['DOCUMENT_ROOT'] ."/_shared-content/xml/proof-points.xml");
        $proof_points = $xml->xpath("system-block");
        $matches = array();

        foreach($proof_points as $proof_point_xml){
            $ppInfo = inspect_block_proof_points($proof_point_xml, $School, $Topic, $CAS, $CAPS, $GS, $SEM);
            if( $ppInfo['match-school'] || $ppInfo['match-dept'] || $ppInfo['match-topic'] ){
                array_push($matches, $ppInfo);
            }
        }

        // sort into premium, subject, school, etc...
        $proofPointsArrays = divide_into_arrays_proof_points($matches);

        // Get random selection of $numItems proof points
        return get_x_proof_points( $proofPointsArrays, $numItems );
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
    $html = "";
    $number = $ds->{'proof-point'}->{'number-group'}->{'number-field'};
    if(!$number){
        $number = $ds->{'proof-point'}->{'number-group'}->{'number'};
    }
    $textBefore = $ds->{'proof-point'}->{'number-group'}->{'text-before'};
    $textAfter = $ds->{'proof-point'}->{'number-group'}->{'text-after'};
    $animate = $ds->{'proof-point'}->{'number-group'}->{'animate'};

    $textBelow = $ds->{'proof-point'}->{'number-group'}->{'text-below'};
    $source = $ds->{'proof-point'}->{'number-group'}->{'source'};

    $twig = makeTwigEnviron('/code/proof-points/twig');
    $html = $twig->render('number_pp_html.html', array(
        'textBefore' => $textBefore,
        'textAfter' => $textAfter,
        'textBelow' => $textBelow,
        'source' => $source,
        'number' => $number,
        'animate'   =>  $animate));

    return $html;
}

function text_pp_html($ds){
    $html = "";
    $mainText = $ds->{'proof-point'}->{'text'}->{'main-text'};
    $source = $ds->{'proof-point'}->{'text'}->{'source'};

    $twig = makeTwigEnviron('/code/proof-points/twig');
    $html = $twig->render('text_pp_html.html', array(
        'mainText' => $mainText,
        'source' => $source));

    return $html;
}


