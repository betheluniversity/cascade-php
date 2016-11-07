<?php

include_once $_SERVER["DOCUMENT_ROOT"] . "/code/program-search/php/program-search-functions.php";

function get_catalog_table($path){
    $path = "/" . $path;
    $programs_xml = get_program_xml();
    foreach ($programs_xml as $key => $value){
        foreach($value["concentrations"] as $L2_Key => $L2_Value){
            if( !empty($L2_Value["catalog_url"]) && strlen($L2_Value["catalog_url"]) > 0 ){
                if( strcmp($path, $L2_Value["concentration_page"]->{"path"}) == 0 ) {
                    echo "<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt 
                    ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris 
                    nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit 
                    esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in 
                    culpa qui officia deserunt mollit anim id est laborum.</p>";
                    $destination_url = str_replace("/index.xml", "/#academicplanstext", $L2_Value["catalog_url"]);
                    echo "<a href=\"" . $destination_url . "\" class=\"btn\">View Schedule</a><br/>";
                    // $xml = file_get_contents($L2_Value["catalog_url"]);
                    // $xml_object = new SimpleXMLElement($xml);
                    // echo $xml_object -> text[0];
                }
            }
        }
    }
}