<?php
/**
 * Created by PhpStorm.
 * User: ces55739
 * Date: 7/29/14
 * Time: 2:59 PM
 */

// This file can be removed soon. It has been replaced by quote-logic.php and quotes.php
// Salesforce is using this format here: https://cms.bethel.edu/entity/open.act?id=7e1da6148c58651364871d5b01ae949b&type=format&

    // Globals
    $PageSchool;
    $PageDepartment;
    $PageCAPS;
    $PageGS;
    $PageSem;

    $DefaultQuote;

    // Staging Site
    if( strstr(getcwd(), "staging/public") ){

        $destinationName = "staging";
    }
    else{ // Live site.
        $destinationName = "www";
    }
    include_once $_SERVER["DOCUMENT_ROOT"] . "/code/php_helper_for_cascade.php";

    // The controller for this section of PHP
    function get_quotes($maxNumToFind){

        global $destinationName;
        $quotesArray = get_xml_quotes($_SERVER["DOCUMENT_ROOT"] . "/_shared-content/xml/quotes.xml" );

        // Convert the single array into the x(or 4) number of arrays needed.
        $quotesArrays = divide_into_arrays_quotes($quotesArray);

        // $proofPoints should be an array of arrays.
        $quotesToDisplay = get_x_quotes( $quotesArrays, $maxNumToFind);
        return $quotesToDisplay;
    }

    // Takes a xml file and converts to an array of quotes
    function get_xml_quotes($fileToLoad ){
        $xml = simplexml_load_file($fileToLoad);
        $pages = array();
        $pages = traverse_folder_quotes($xml, $pages);
        return $pages;
    }

    // Traverses through to return an array of displayable quotes.
    function traverse_folder_quotes($xml, $quotes){
        foreach ($xml->children() as $child) {

            $name = $child->getName();

            if ($name == 'system-folder'){
                $quotes = traverse_folder_quotes($child, $quotes);
            }elseif ($name == 'system-block'){
                // Set the page data.
                $quote = inspect_block_quotes($child);

                if( $quote['display'] != "No" )
                    array_push($quotes, $quote);
            }
        }

        return $quotes;
    }
    // Gets the correct info/html of the quote
    function inspect_block_quotes($xml){
        $block_info = array(
            "display-name" => $xml->{'display-name'},
            "published" => $xml->{'last-published-on'},
            "description" => $xml->{'description'},
            "path" => $xml->path,
            "md" => array(),
            "html" => "",
            "display" => "No",
        );
        $ds = $xml->{'system-data-structure'};
        $dataDefinition = $ds['definition-path'];
        if( $dataDefinition == "Blocks/Quote")
        {
            global $PageSchool;
            global $PageDepartment;
            global $PageCAPS;
            global $PageGS;
            global $PageSem;

            if( in_array("College of Arts & Sciences", $PageSchool))
                $block_info['display'] = match_metadata_quote($xml, $PageSchool, $PageDepartment, "department");
            elseif( in_array("College of Adult & Professional Studies", $PageSchool) )
                $block_info['display'] = match_metadata_quote($xml, $PageSchool, $PageCAPS, "adult-undergrad-program");
            elseif( in_array( "Graduate School", $PageSchool) )
                $block_info['display'] = match_metadata_quote($xml, $PageSchool, $PageGS, "graduate-program");
            elseif( in_array( "Bethel Seminary", $PageSchool) )
                $block_info['display'] = match_metadata_quote($xml, $PageSchool, $PageSem, "seminary-program");
            elseif( in_array("Bethel University", $PageSchool) )
                $block_info['display'] = match_metadata_quote($xml, $PageSchool, $PageSem, "school");

            // Get html
            $block_info['html'] = get_quote_html($xml);

            // Determine which quote should be the 'Default' Quote.
            if( $xml->name == "bu__edgren"){
                global $DefaultQuote;
                $DefaultQuote = $block_info;
            }
        }
        return $block_info;
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

    // Compares the metadata of the quote to the metadata of the page
    function match_metadata_quote($xml, $blockSchoolArray, $blockDeptArray, $deptType){
        $quoteSchoolArray = array();
        $quoteDeptArray = array();
        $quoteHasDepts = true;
        foreach ($xml->{'dynamic-metadata'} as $md){
            // Remove the "select" value.
            $newmd = array();
            foreach($md->value as $value )
            {
                if( "select" == $value || "Select" == $value )
                    continue;
                array_push($newmd, $value);
            }

            // Get the corresponding arrays
            $name = $md->name;
            if ( $name == "school" ){
                $quoteSchoolArray = $newmd;
            }
            elseif( $name == $deptType){
                if( sizeof($newmd) == 0)
                    $quoteHasDepts = false;
                else
                    $quoteDeptArray = $newmd;

            }
        }

        $sameSchools = array_intersect($quoteSchoolArray, $blockSchoolArray);
        $sameDepts = array_intersect($quoteDeptArray, $blockDeptArray);

        if( sizeof($sameSchools) > 0 && sizeof($sameDepts) > 0 && $quoteHasDepts){
            return "school and dept";
        }
        elseif( sizeof($sameSchools) > 0 && sizeof( $quoteDeptArray) == 0){
            return "school default";
        }
        elseif( in_array("Bethel University", $quoteSchoolArray ) ){
            return "bethel default";
        }
        else
            return "No";
    }

    // Returns x random quotes from the array of arrays of quotes.
    // Not very well constructed.
    // Down the road, this should probably be rewritten.
    function get_x_quotes($quotesArrays, $maxNumToFind){
        $finalQuotes = array();

        foreach( $quotesArrays as $quoteArray)
        {
            if( sizeof( $quoteArray) == 0 )
                continue;
            while( sizeof($quoteArray) > 0)
            {
                if( $maxNumToFind <= 0){
                    break 2;
                }

                $randomIndex = rand(0, sizeof($quoteArray));
                $quote = $quoteArray[$randomIndex];

                if( $quote != null)
                {
                    array_push( $finalQuotes, $quote );
                    $maxNumToFind--;
                }

                unset($quoteArray[$randomIndex]);
                $quoteArray = array_values($quoteArray);
            }
            break;
        }

        return $finalQuotes;
    }

    // Divides an array of quotes into an array of arrays of quotes.
    // This allows for a sections of quotes to have different priorities for being chosen.
    function divide_into_arrays_quotes($quotesArrays){
        $first = array();
        $second = array();
        $third = array();
        foreach( $quotesArrays as $quote){
            if( $quote['display'] == "school and dept")
            {
                array_push($first, $quote);
            }
            elseif( $quote['display'] == "school default")
            {
                array_push($second, $quote);
            }
            elseif( $quote['display'] == "bethel default" )
            {
                array_push($third, $quote );
            }
        }

        return array($first, $second, $third);
    }




?>
