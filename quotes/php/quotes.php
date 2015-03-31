<?php
/**
 * Created by PhpStorm.
 * User: ces55739
 * Date: 3/30/15
 * Time: 9:26 AM
 */


include_once 'quote-logic.php';
include_once $_SERVER['DOCUMENT_ROOT'] . '/code/general-cascade/macros.php';
//include_once $_SERVER["DOCUMENT_ROOT"] . "/php_helper_for_cascade.php";
function show_quote_collection($numItems, $School, $Topic, $CAS, $CAPS, $GS, $SEM){
    echo "<!-- new quotes -->";

    $categories = array( $School, $Topic, $CAS, $CAPS, $GS, $SEM );
    $xml = simplexml_load_file($_SERVER['DOCUMENT_ROOT'] ."/_shared-content/xml/quotes.xml");
    $quotes = $xml->xpath("//system-block");
    $matches = array();

    foreach($quotes as $quote_xml){
        $quote_info = inspect_block_quotes($quote_xml, $School, $Topic, $CAS, $CAPS, $GS, $SEM);
        if( $quote_info['match-school'] || $quote_info['match-dept'] || $quote_info['match-topic'] ){
            array_push($matches, $quote_info);
        }
    }

    // sort into dept, school, etc...
    $quotesArrays = divide_into_arrays_quotes($matches);

    // Get random selection of $numItems proof points
    $quotesToDisplay = get_x_quotes( $quotesArrays, $numItems );


    if( sizeof($quotesToDisplay) > 0) {
        //Output structure
        carousel_open("carousel--quote");
        foreach ($quotesToDisplay as $finalQuote) {
            echo $finalQuote['html'];
        }
        carousel_close();
    }
}

// returns the html of the quote.
function get_quote_html($xml){
    $ds = $xml->{'system-data-structure'};

    $title = $xml->{'title'};
    $imagePath = $ds->{'image'}->{'path'};
    $text = $ds->{'quote'};
    $source = $ds->{'source'};


    $html = '<div class="pa1  quote  grayLighter">';
    $html .= '<div class="grid ">';
    if( $imagePath != "/" && $imagePath != "")
    {
        //render image here.
        $html .= '<div class="grid-cell  u-medium-2-12">';
        $html .= '<div class="medium-grid-pad-1x">';
        $html .= '<div class="quote__avatar">';

        global $destinationName;
        $html .= thumborURL($imagePath, "200", true, false);
        $html .= '</div></div></div>';

        $html .= '<div class="grid-cell  u-medium-10-12">';
        $html .= '<div class="medium-grid-pad-1x">';
        $html .= '<p class="quote__text">'.$text.'</p>';
        if( $source != "")
            $html .= '<cite class="quote__source">–'.$source.'</cite>';
        $html .= '</div></div>';
    }
    else
    {
        $html .= '<div class="grid-cell  u-medium-12-12">';
        $html .= '<div class="medium-grid-pad-1x">';
        $html .= '<p class="quote__text">'.$text.'</p>';
        if( $source != "")
            $html .= '<cite class="quote__source">–'.$source.'</cite>';
        $html .= '</div></div>';
    }

    $html .= '</div>';
    $html .= '</div>';

    return $html;
}

