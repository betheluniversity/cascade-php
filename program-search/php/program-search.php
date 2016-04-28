<?php
/**
 * Created by PhpStorm.
 * User: ces55739
 * Date: 4/20/16
 * Time: 1:54 PM
 */

// General Todos:
// Todo: On the compare programs, what deliveries do we show? (1) all (2) the next one available

require $_SERVER["DOCUMENT_ROOT"] . '/code/vendor/autoload.php';
include_once $_SERVER["DOCUMENT_ROOT"] . "/code/general-cascade/macros.php";
include_once $_SERVER["DOCUMENT_ROOT"] . "/code/program-search/php/program-search-functions.php";

route_to_functions();

function route_to_functions(){
    $inputs = json_decode( file_get_contents( "php://input" ));
    $function_name = $inputs[0];
    $data = $inputs[1];

    if( $function_name == 'program-search-results')
        call_program_search($data);
    elseif( $function_name == 'compare-programs')
        call_compare_programs($data);
}


function call_program_search($input_data){
    $program_data = get_program_xml();

    $programs = search_programs($program_data, $input_data);
    usort($programs, 'program_sort_by_school_then_title');

    // get unique schools
    // only show the schools that match, and order them as follows
    $uniqueSchools = array_unique(array_map(function ($i) { return $i['program']['md']['school'][0]; }, $programs));
    $school_order = array('College of Arts & Sciences', 'College of Adult & Professional Studies', 'Graduate School', 'Bethel Seminary');
    foreach( $school_order as $key => $school){
        if( !in_array($school, $uniqueSchools))
            unset($school_order[$key]);
    }

    // print the entire table
    echo get_html_for_table($programs, $school_order);
}


function call_compare_programs($program_id_list){
    $programs_to_compare = array();

    // Todo: make this less nasty through cache, and also how it is gathered?
    $programs = get_program_xml();
    foreach($programs as $program){
        foreach($program['concentrations'] as $concentration){
            if( $concentration['concentration_code'] != '' && in_array($concentration['concentration_code'], $program_id_list)){
                array_push($programs_to_compare, array($program, $concentration));
            }
        }
    }
    $twig = makeTwigEnviron('/code/program-search/twig');
    $html = $twig->render('compare-programs.html', array(
        'program_concentrations'=> $programs_to_compare,
    ));

    echo $html;
}
