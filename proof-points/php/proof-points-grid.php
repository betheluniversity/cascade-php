<?php
    require $_SERVER["DOCUMENT_ROOT"] . '/code/vendor/autoload.php';
    include_once 'proof-point-logic.php';
    include_once $_SERVER['DOCUMENT_ROOT'] . "/code/general-cascade/macros.php";

    function show_proof_point_collection($numItems, $School, $Topic, $CAS, $CAPS, $GS, $SEM){
        $proofPointsToDisplay = get_proof_points($numItems, $School, $Topic, $CAS, $CAPS, $GS, $SEM);
        $numProofPoints = count($proofPointsToDisplay);
        $toReturn = "";

        foreach($proofPointsToDisplay as $finalPP){
            $toReturn .= createGridCell("", $finalPP);
        }
        echo createGrid("proofPoints", $toReturn);
    }

    function get_proof_points($numItems, $School, $Topic, $CAS, $CAPS, $GS, $SEM){
        // $categories = array( $School, $Topic, $CAS, $CAPS, $GS, $SEM );
        $xml = simplexml_load_file($_SERVER['DOCUMENT_ROOT'] ."/_shared-content/xml/proof-points.xml");
        $proof_points = $xml->xpath("system-block");
        $matches = array();

        foreach($proof_points as $proof_point_xml){
            $ppInfo = inspect_block_proof_points($proof_point_xml, $School, $Topic, $CAS, $CAPS, $GS, $SEM);
            if( ($ppInfo['match-school'] || $ppInfo['match-dept'] || $ppInfo['match-topic']) && !$ppInfo['hide'] ){
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
        $id = $xml['id'];
        $ds = $xml->{'system-data-structure'};
        $type = $ds->{'proof-point'}->{'type'};
        $html = "";
        if($type == "Text"){
            $html = text_pp_html($ds, $id);
        }
        elseif($type == "Number"){
            $html = number_pp_html($ds, $id);
        }
        return $html;
    }

function number_pp_html($ds, $id){
    $main_text_number = $ds->{'proof-point'}->{'number-group'}->{'main-text-number'};
    $text_below = $ds->{'proof-point'}->{'number-group'}->{'text-below'};
    $source = $ds->{'proof-point'}->{'number-group'}->{'source'};

    $twig = makeTwigEnviron('/code/proof-points/twig');
    $html = $twig->render('number.html', array(
        'main_text_number' => $main_text_number,
        'text_below' => $text_below,
        'source' => $source,
        'id' => $id));
    return $html;
}

function text_pp_html($ds, $id){
    $html = "";
    $mainText = $ds->{'proof-point'}->{'text'}->{'main-text'};
    $source = $ds->{'proof-point'}->{'text'}->{'source'};

    $twig = makeTwigEnviron('/code/proof-points/twig');
    $html = $twig->render('text.html', array(
        'mainText' => $mainText,
        'source' => $source,
        'id' => $id));
    return $html;
}
