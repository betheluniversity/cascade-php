<?php
/**
 * Created by PhpStorm.
 * User: ces55739
 * Date: 7/29/14
 * Time: 2:59 PM
 */

    // Globals
    $PageSchool;
    $PageDepartment;

    $DefaultQuote;
    // Staging Site
    if( strstr(getcwd(), "staging/public") ){
        include_once "/var/www/staging/public/code/php_helper_for_cascade.php";
        $destinationName = "staging";
    }
    else{ // Live site.
        include_once "/var/www/cms.pub/code/php_helper_for_cascade.php";
        $destinationName = "www";
    }

    // The controller for this section of PHP
    function get_quotes($maxNumToFind){
        global $PageSchool;
        global $PageDepartment;

        global $destinationName;
        if( $destinationName == "staging/public" ){
            $quotesArray = get_xml_quotes("/var/www/staging/public/_shared-content/xml/quotes.xml", $PageSchool, $PageDepartment);
        }
        else{
            $quotesArray = get_xml_quotes("/var/www/cms.pub/_shared-content/xml/quotes.xml", $PageSchool, $PageDepartment);
        }

        // Convert the single array into the x(or 4) number of arrays needed.
        $quotesArrays = divide_into_arrays_quotes($quotesArray);

        // $proofPoints should be an array of arrays.
        $quotesToDisplay = get_x_quotes( $quotesArrays, $maxNumToFind);
        return $quotesToDisplay;
    }

    // Takes a xml file and converts to an array of quotes
    function get_xml_quotes($fileToLoad, $PageSchool, $PageDepartment ){
        $xml = simplexml_load_file($fileToLoad);
        $pages = array();
        $pages = traverse_folder_quotes($xml, $pages, $PageSchool, $PageDepartment);
        return $pages;
    }

    // Traverses through to return an array of displayable quotes.
    function traverse_folder_quotes($xml, $quotes, $PageSchool, $PageDepartment){
        foreach ($xml->children() as $child) {

            $name = $child->getName();

            if ($name == 'system-folder'){
                $quotes = traverse_folder_quotes($child, $quotes, $PageSchool, $PageDepartment);
            }elseif ($name == 'system-block'){
                // Set the page data.
                $quote = inspect_block_quotes($child, $PageSchool, $PageDepartment);

                if( $quote['match-school'] == "Yes" || $quote['match-dept'] == "Yes")
                    array_push($quotes, $quote);
            }
        }

        return $quotes;
    }

    // Gets the correct info/html of the quote
    function inspect_block_quotes($xml, $PageSchool, $PageDepartment){
        $block_info = array(
            "display-name" => $xml->{'display-name'},
            "published" => $xml->{'last-published-on'},
            "description" => $xml->{'description'},
            "path" => $xml->path,
            "md" => array(),
            "html" => "",
            "display" => "No",
            "match-school" => "No",
            "match-dept" => "No",
        );
        $ds = $xml->{'system-data-structure'};
        $dataDefinition = $ds['definition-path'];
        if( $dataDefinition == "Blocks/Quote")
        {
            $block_info['match-school'] = match_metadata_quote($xml, $PageSchool);
            $block_info['match-dept'] = match_metadata_quote($xml, $PageDepartment);

            // Get html
            $block_info['html'] = get_quote_html($block_info, $xml);

            // Determine which quote should be the 'Default' Quote.
            if( $xml->name == "bu__edgren"){
                global $DefaultQuote;
                $DefaultQuote = $block_info;
            }
        }
        return $block_info;
    }

    // returns the html of the quote.
    function get_quote_html( $block_info, $xml){
        $ds = $xml->{'system-data-structure'};

        $title = $xml->{'title'};
        $imagePath = $ds->{'image'}->{'path'};
        $text = $ds->{'quote'};
        $source = $ds->{'source'};

        if( $imagePath != "/" && $imagePath != "")
        {
            //render image here.
            $html = '<div class="grid ">';
                $html .= '<div class="grid-cell  u-medium-2-12">';
                $html .= '<div class="medium-grid-pad-1x">';
                $html .= '<div class="quote__avatar">';

                global $destinationName;
                $html .= render_image($imagePath, $title, "delayed-image-load", "200", $destinationName);
                $html .= '</div></div></div>';

                $html .= '<div class="grid-cell  u-medium-10-12">';
                $html .= '<div class="medium-grid-pad-1x">';
                $html .= '<p class="quote__text">'.$text.'</p>';
                if( $source != "")
                    $html .= '<cite class="quote__source">–'.$source.'</cite>';
                $html .= '</div></div>';
            $html .= '</div>';
        }
        else
        {
            $html = '<div class="grid ">';
                $html .= '<div class="grid-cell  u-medium-12-12">';
                $html .= '<div class="medium-grid-pad-1x">';
                $html .= '<p class="quote__text">'.$text.'</p>';
                if( $source != "")
                    $html .= '<cite class="quote__source">–'.$source.'</cite>';
                $html .= '</div></div>';
            $html .= '</div>';
        }

        return $html;
    }

    // Compares the metadata of the quote to the metadata of the page
    function match_metadata_quote($xml, $category){
        foreach ($xml->{'dynamic-metadata'} as $md){

            $name = $md->name;

            $options = array('school', 'department');

            foreach($md->value as $value ){
                if($value == "Select" || $value == "none"){
                    continue;
                }
                if (in_array($name,$options)){
                    if (in_array($value, $category)){
                        return "Yes";
                    }
                }
            }
        }
        return "No";
    }

    // Returns x random quotes from the array of arrays of quotes.
    // Not very well constructed.
    // Down the road, this should probably be rewritten.
    function get_x_quotes($quotesArrays, $maxNumToFind){
        $finalQuotes = array();

        foreach( $quotesArrays as $quoteArray)
        {
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
        }
        if( sizeof($finalQuotes) == 0)
        {
            global $DefaultQuote;
            array_push($finalQuotes, $DefaultQuote);
        }

        return $finalQuotes;
    }

    // Divides an array of quotes into an array of arrays of quotes.
    // This allows for a sections of quotes to have different priorities for being chosen.
    function divide_into_arrays_quotes($quotesArrays){
        $school = array();
        $dept = array();

        foreach( $quotesArrays as $quote){
            if( $quote['match-dept'] == "Yes")
            {
                array_push($dept, $quote);
            }
            elseif( $quote['match-school'] == "Yes")
            {
                array_push($school, $quote);
            }
        }

        return array($dept, $school);
    }




?>