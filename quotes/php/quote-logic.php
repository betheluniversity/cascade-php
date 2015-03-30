<?php
/**
 * Created by PhpStorm.
 * User: ces55739
 * Date: 3/30/15
 * Time: 9:49 AM
 */

    // Gets the correct info/html of the quote
    function inspect_block_quotes($xml, $School, $Topic, $CAS, $CAPS, $GS, $SEM){
        $block_info = array(
            "display-name" => $xml->{'display-name'},
            "published" => $xml->{'last-published-on'},
            "description" => $xml->{'description'},
            "path" => $xml->path,
            "html" => "",
            "display" => "No",
            "match-school" => false,
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
            // next, grab a GENERIC one from the school ( no depts tagged )
            $block_info['match-school'] = match_generic_school_quotes($xml, $School);
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

    function match_generic_school_quotes($xml, $schools){
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

        // returns true if there are no depts and the school matches.
        if (sizeof(array_diff_assoc($schoolsArray, $schools)) == 0) {
            return true;
        }
        return false;
    }


    // Gets x random proof points from the array of arrays of quotes
    // Not very well constructed.
    // Down the road, this should probably be rewritten.
    function get_x_quotes($quotesArrays, $numToFind){
        $finalQuotes = array();
        foreach( $quotesArrays as $quoteArray)
        {
            $sizeOfArray = sizeof($quoteArray);
            while( $sizeOfArray > 0)
            {
                if( $numToFind <= 0){
                    break 2;
                }
                $randomIndex = rand(0,$sizeOfArray);
                $quote = $quoteArray[$randomIndex];
                if( $quote != null)
                {
                    array_push( $finalQuotes, $quote );
                    $numToFind--;
                }
                unset($quoteArray[$randomIndex]);
                array_values($quoteArray);
                $sizeOfArray = sizeof($quoteArray);
            }
        }
        return $finalQuotes;
    }


    // Divide the array of proof points into arrays of dept/school and premium/not-premium.
    // This allows for a priority of what proof points to use.
    function divide_into_arrays_quotes($quotesArrays){
        $school = array();
        $dept = array();
        foreach( $quotesArrays as $quote){
            if( $quote['match-dept'])
            {
                array_push($dept, $quote);
            }
            elseif( $quote['match-school'])
            {
                array_push($school, $quote);
            }
        }
        $finalQuoteArrays = array($dept, $school);
        return $finalQuoteArrays;
    }