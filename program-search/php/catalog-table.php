<?php

require $_SERVER["DOCUMENT_ROOT"] . '/code/vendor/autoload.php';
include_once $_SERVER["DOCUMENT_ROOT"] . "/code/program-search/php/program-search-functions.php";

function get_page_catalog_url($path){

    // Allow lookup by path for pages in _testing
    $path = str_replace("_testing/", "", $path);

    $path = "/" . $path;
    $programs_xml = get_program_xml();
    foreach ($programs_xml as $index => $program){
        foreach($program["concentrations"] as $L2_index => $concentration){
            if( strlen($concentration["catalog_url"]) > 0 ){
                if( strcmp($path, $concentration["concentration_page"]->{"path"}) == 0 || strcmp($path . 'index', $concentration["concentration_page"]->{"path"}) == 0 ){
                    $destination_url = str_replace("/index.xml", "/#academicplanstext", $concentration["catalog_url"]);
                    return $destination_url;
                }
            }
        }
    }
    return "https://catalog.bethel.edu";
}

function get_catalog_url($path){
    $destination_url = get_page_catalog_url($path);
    echo '<a target="_blank" class="btn" href="' . $destination_url . '">See plans</a>';
}

function get_catalog_table($path, $page_title){
    $destination_url = get_page_catalog_url($path);
    $twig = makeTwigEnviron('/code/program-search/twig');
    $html = $twig->render('catalog-table.html', array(
        'destination_url'=> $destination_url,
        'page_title' => $page_title
    ));
    echo $html;
}
