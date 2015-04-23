<?php
/**
 * Created by PhpStorm.
 * User: ces55739
 * Date: 3/30/15
 * Time: 9:49 AM
 */

// Gets the correct info/html of the quote
function inspect_block_quotes($xml, $Topic, $CAS, $CAPS, $GS, $SEM){
    $block_info = array(
        "display-name" => $xml->{'display-name'},
        "published" => $xml->{'last-published-on'},
        "description" => $xml->{'description'},
        "path" => $xml->path,
        "html" => "",
        "display" => "No",
        "match-school" => false, // this is deprecated. We do not use this anymore.
        "match-dept" => false,
        "match-topic" => false,
    );
    $ds = $xml->{'system-data-structure'};
    $dataDefinition = $ds['definition-path'];
    if( $dataDefinition == "Blocks/Quote")
    {
        // First get one that matches the specific school dept
        if( match_metadata_quotes($xml, $CAS) || match_metadata_quotes($xml, $CAPS) || match_metadata_quotes($xml, $GS) || match_metadata_quotes($xml, $SEM)  )
            $block_info['match-dept'] = true;
        // Now that we are desparate, just get one that has a topic thats the same.
        $block_info['match-topic'] = match_metadata_quotes($xml, $Topic);
        // Get html
        $block_info['html'] = get_quote_html($xml);
        //Determine which quote should be the 'Default' Quote.
        if( $xml->name == "bu__edgren"){
            global $DefaultQuote;
            $DefaultQuote = $block_info;
        }
    }
    return $block_info;
}
// Matches the metadata of the page against the metadata of the proof point
function match_metadata_quotes($xml, $block_value_array)
{
    foreach( $block_value_array as $block_value){
        foreach ($xml->{'dynamic-metadata'} as $md) {
            $name = $md->name;
            foreach ($md->value as $value) {
                if ($value == "Select" || $value == "none") {
                    continue;
                }
                if (htmlspecialchars($value) == htmlspecialchars($block_value)) {
                    return true;
                }
            }
        }
    }
    return false;
}
// Gets x random proof points from the array of arrays of quotes
function get_x_quotes($quotesArrays, $numToFind){
    $finalQuotes = array();
    foreach( $quotesArrays as $quoteArray) {
        // randomize the quotes grabbed
        shuffle($quoteArray);
        // grab quotes
        foreach($quoteArray as $quote){
            array_push($finalQuotes, $quote);
            $numToFind--;
            // if X quotes are already grabbed, break out
            if( $numToFind == 0)
                return $finalQuotes;
        }
        // If quotes were grabbed, then don't grab any more!!!
        if( sizeof( $quoteArray) >= 1 )
            return $finalQuotes;
    }
    return $finalQuotes;
}
// Divide the array of proof points into arrays of dept/school and premium/not-premium.
// This allows for a priority of what proof points to use.
function divide_into_arrays_quotes($quotesArrays){
    $dept = array();
    $topic = array();
    foreach( $quotesArrays as $quote){
        if( $quote['match-dept'])
        {
            array_push($dept, $quote);
        }
        elseif( $quote['match-topic'])
        {
            array_push($topic, $quote);
        }
    }
    $finalQuoteArrays = array($dept, $topic);
    return $finalQuoteArrays;
}
