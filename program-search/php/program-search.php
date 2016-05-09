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
    $program_data = autoCache("get_program_xml", array(), 'program-data1', 4);

    $programs = search_programs($program_data, $input_data);
    usort($programs, 'program_sort_by_school_then_title');

    // The order of which the degrees are shown
    // Holds the real name, and the name that will match all of the specific type.
    //   i.e. 'Master' vs 'Master's'
    $degrees_array = array(
        'Associate'     =>  array('name' =>'Associate', 'programs' => array()),
        'Bachelor'      =>  array('name' =>'Bachelor', 'programs' => array()),
        'License'       =>  array('name' =>'License', 'programs' => array()),
        'Certificate'   =>  array('name' =>'Certificate', 'programs' => array()),
        'Master'        =>  array('name' =>"Master's", 'programs' => array()),
        'Doctor'        =>  array('name' =>'Doctorate', 'programs' => array())
    );

    // sort programs into each degree
    // Todo: this needs to be cleaned up
    foreach( $programs as $program){
        foreach( $degrees_array as $key => $degree ){
            foreach( $program['program']['md']['degree'] as $program_degree) {
                if (strpos($program_degree, $key) !== false) {
                    array_push($degrees_array[$key]['programs'], $program);
                }
            }
        }
    }

    // if none of a degree type exists, don't show it
    foreach( $degrees_array as $key => $degree ){
        if( sizeof($degree['programs']) == 0 ){
            unset($degrees_array[$key]);
        }
    }

    // print the entire table
    echo get_html_for_table($degrees_array);
}

// Todo: On the compare programs, what deliveries do we show? (1) all (2) the next one available
function call_compare_programs($program_id_list){
    $program_data = autoCache("get_program_xml", array(), 'program-data2', 300);

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
