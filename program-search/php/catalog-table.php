<?php

require $_SERVER["DOCUMENT_ROOT"] . '/code/vendor/autoload.php';
include_once $_SERVER["DOCUMENT_ROOT"] . "/code/program-search/php/program-search-functions.php";

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

function get_program_code($path){
    $program_code = get_value_from_concentration($path, "program_code");
    if( $program_code != null ){
        return $program_code;
    }
    return null;
}

function get_page_catalog_url($path){
    $catalog_url = get_value_from_concentration($path, "catalog_url");
    if( $catalog_url != null ){
        $destination_url = str_replace("/index.xml", "/#academicplanstext", $catalog_url);
        return $destination_url;
    }
    return "https://catalog.bethel.edu";
}

function get_base_catalog_url($path){
    $catalog_url = get_value_from_concentration($path, "catalog_url");
    if( $catalog_url != null ){
        $base_url = str_replace("/index.xml", "", $catalog_url);
        return $base_url;
    }
    return null;
}

function get_value_from_concentration($path, $key){
    $concentration = get_concentration($path);
    if( $concentration != null && array_key_exists($key, $concentration) ){
        return $concentration[$key];
    }
    return null;
}

function get_concentration($path){
    // Allow lookup by path for pages in _testing
    $path = str_replace("_testing/", "", $path);

    $path = "/" . $path;
    $programs_xml = get_program_xml();
    foreach ($programs_xml as $index => $program){
        foreach($program["concentrations"] as $L2_index => $concentration){
            if( strlen($concentration["catalog_url"]) > 0 ){
                if( strcmp($path, $concentration["concentration_page"]->{"path"}) == 0 || strcmp($path . 'index', $concentration["concentration_page"]->{"path"}) == 0 ){
                    return $concentration;
                }
            }
        }
    }
    return null;
}
