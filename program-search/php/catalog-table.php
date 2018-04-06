<?php

require $_SERVER["DOCUMENT_ROOT"] . '/code/vendor/autoload.php';
include_once $_SERVER["DOCUMENT_ROOT"] . "/code/program-search/php/program-search-functions.php";

function get_catalog_table($path, $page_title){
    $path = "/" . $path;
    $programs_xml = get_program_xml();
    foreach ($programs_xml as $index => $program){
        foreach($program["concentrations"] as $L2_index => $concentration){
            if( strlen($concentration["catalog_url"]) > 0 ){
                if( strcmp($path, $concentration["concentration_page"]->{"path"}) == 0 || strcmp($path . 'index', $concentration["concentration_page"]->{"path"}) == 0 ){
                    $destination_url = str_replace("/index.xml", "/#academicplanstext", $concentration["catalog_url"]);
                    $twig = makeTwigEnviron('/code/program-search/twig');
                    $html = $twig->render('catalog-table.html', array(
                        'destination_url'=> $destination_url,
                        'page_title' => $page_title
                    ));
                    echo $html;
                    // $xml = file_get_contents($L2_Value["catalog_url"]);
                    // $xml_object = new SimpleXMLElement($xml);
                    // echo $xml_object -> text[0];
                }
            }
        }
    }
}