<?php
/**
 * Created by PhpStorm.
 * User: ces55739
 * Date: 4/20/16
 * Time: 1:54 PM
 */

// Todo: in case this overall needs to be sped up: http://nickology.com/2012/07/03/php-faster-array-lookup-than-using-in_array/
// ^^ This would involve making $concentration['concentration_code']='asdfasdf' become $concentration['concentration_code']['asdfasdf'] = 1


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
//    $startTime = microtime(true);

//    $program_data = autoCache("get_program_xml", array(), 4);
    $program_data = get_program_xml();

    $programs = search_programs($program_data, $input_data);
    usort($programs, 'program_sort_by_titles');

    // The order of which the degrees are shown
    $final_degrees_array = array(
        "Associate's Degrees"   =>  array(),
        "Bachelor's Degrees"    =>  array(),
        "Master's Degrees"      =>  array(),
        'Doctoral Degrees'      =>  array(),
        'License'               =>  array(),
        'Certificate'           =>  array()
    );
    // sort programs into each degree
    foreach( $programs as $program){
        foreach( $program['program']['md']['degree'] as $program_degree) { // loop through program degrees
            foreach( $final_degrees_array as $degree_name => $program_array ){ // loop through degree holder
                // Find the shortest substring that will still match all degree types
                // i.e. Master's MATCHES Master Of Arts
                $degree_check = substr($degree_name, 0, 6);
                // if it matches and hasn't been added yet
                if (strpos($program_degree, $degree_check) !== false && !in_array($program, $final_degrees_array[$degree_name])) {
                    array_push($final_degrees_array[$degree_name], $program);
                }
            }
        }
    }

    // if none of a degree type exists, don't show it
    foreach( $final_degrees_array as $degree_name => $program_array ){
        if( sizeof($program_array) == 0 ){
            unset($final_degrees_array[$degree_name]);
        }
    }


//    echo "Elapsed time is: ". (microtime(true) - $startTime + 0.2) ." seconds, for search term: '" . $input_data[0] . "'";

    // print the entire table
    echo get_html_for_table($final_degrees_array);
}

// Todo: On the compare programs, what deliveries do we show? (1) all (2) the next one available
function call_compare_programs($program_id_list){
//    $program_data = autoCache("get_program_xml", array(), 300);
    $program_data = get_program_xml();

    $programs_to_compare = array();
    foreach($program_data as $program){
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
