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
// Todo: remove $School. But you also need to remove it from the format: https://cms.bethel.edu/entity/open.act?id=e255c37e8c586513100ee2a71077c3b4&type=format
//       Then all pages with quote carousels need to be republished.
function show_quote_collection($numItems, $School, $Topic, $CAS, $CAPS, $GS, $SEM){
    echo "<!-- new quotes -->";

    $xml = simplexml_load_file($_SERVER['DOCUMENT_ROOT'] ."/_shared-content/xml/quotes.xml");
    $quotes = $xml->xpath("//system-block");
    $matches = array();

    foreach($quotes as $quote_xml){
        $quote_info = inspect_block_quotes($quote_xml, $Topic, $CAS, $CAPS, $GS, $SEM);
        if( $quote_info['match-dept'] || $quote_info['match-topic'] ){
            array_push($matches, $quote_info);
        }
    }

    // sort into dept, school, etc...
    $quotesArrays = divide_into_arrays_quotes($matches);
    // Get random selection of $numItems proof points
    $quotesToDisplay = get_x_quotes( $quotesArrays, $numItems );

    if( sizeof($quotesToDisplay) > 0) {
        //Output structure
        $html = "";
        foreach ($quotesToDisplay as $finalQuote) {
            $html .= carousel_item($finalQuote['html'], "", null, false);
        }
        carousel_create("", $html);
    }
}

// returns the html of the quote.
function get_quote_html($xml){
    $ds = $xml->{'system-data-structure'};

    $imagePath = $ds->{'image'}->{'path'};
    $text = $ds->{'quote'};
    $source = $ds->{'source'};

    if( $imagePath != "/" && $imagePath != "") {
        $thumbURL = thumborURL($imagePath, 200, true, false);
    }else {
        $thumbURL = "";
    }

    $twig = makeTwigEnviron('/code/quotes/twig');
    $html = $twig->render('quote.html', array(
        'thumbURL' => $thumbURL,
        'text' => $text,
        'source' => $source));


    return $html;
}
