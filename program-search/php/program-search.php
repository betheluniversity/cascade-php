<?php
/**
 * Created by PhpStorm.
 * User: ces55739
 * Date: 4/20/16
 * Time: 1:54 PM
 */

// General Todos:
// Todo: On the compare programs, what deliveries do we show? (1) all (2) the next one
// Todo: how do we compare CAS and post trad programs

require $_SERVER["DOCUMENT_ROOT"] . '/code/vendor/autoload.php';
include_once $_SERVER["DOCUMENT_ROOT"] . "/code/general-cascade/macros.php";

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


function call_program_search($input_data){
    // TOdo cache this
    $program_data = integrate_xml_and_csv();

    $programs = search_programs($program_data, $input_data);

    usort($programs, 'program_sort_by_school_then_title');

    // only show the schools that match, and order them as follows
    $uniqueSchools = array_unique(array_map(function ($i) { return $i['md']['school'][0]; }, $programs));
    $school_order = array('College of Arts & Sciences', 'College of Adult & Professional Studies', 'Graduate School', 'Bethel Seminary');
    foreach( $school_order as $key => $school){
        if( !in_array($school, $uniqueSchools))
            unset($school_order[$key]);
    }

    // print the entire table
    echo get_html_for_table($programs, $school_order);
}


function program_sort_by_school_then_title($a, $b) {
    $c = strcmp($a['md']['school'][0], $b['md']['school'][0]);
    if($c != 0) {
        return $c;
    }

    return strcmp($a['title'], $b['title']);
}


function get_html_for_table($programs, $schools){
    $twig = makeTwigEnviron('/code/program-search/twig');
    $html = $twig->render('program-search-table.html', array(
        'programs'=> $programs,
        'schools' => $schools
    ));

    return $html;
}

// Todo: call the functions for creating grid/grid cells
function get_html_for_program_concentration($program, $concentration){
    $twig = makeTwigEnviron('/code/program-search/twig');
    $html = $twig->render('concentration.html', array(
        'title'                 => $concentration['concentration_name'],
        'program_types'         => $program['md']['program-type'],
        'concentration_page'    => $concentration['concentration_page'],
        'deliveries'            => $program['deliveries'],
        'degrees'               => $program['md']['degree'],
        'concentration_code'    => $concentration['concentration_code']
    ));

    return $html;
}


function search_programs($program_data, $data){
    $search_term = strtolower($data[0]);
    $schoolArray = $data[1];
    $deliveryArray = $data[2];
    $degreeType = $data[3];

    $return_values = array();

    // Todo: add the csv data
    // Todo: depending on what adds it to the list, should that effect sorting?
    foreach($program_data as $program){
        // 1) school matches
        if( !(in_array('All', $schoolArray) || in_array('all', $schoolArray)) && !count(array_intersect($schoolArray, $program['md']['school'])) )
            continue;

        // 2) delivery matches -- if F2F is selected and school is CAS, it should be shown
        if( !count(array_intersect($deliveryArray, $program['deliveries'])) ){
            if( !(in_array('Face to Face', $deliveryArray) && in_array('College of Arts & Sciences', $program['md']['school'])) ) {
                continue;
            }
        }

        // 3) degree type matches
        if( !($degreeType == 'All' || $degreeType == 'all') && check_degree_types($program, $degreeType) )
            continue;

        // default -- displaying all if no search term is entered
        if( $search_term == '' ) {
            array_push($return_values, $program);
        }
        // program title matches -- if search key is in program title
        elseif( strpos(strtolower($program['title']), $search_term) !== false ) {
            array_push($return_values, $program);
        }
        // cluster matches -- if search key is in cluster
        elseif( sizeof($program['md']['cluster']) > 0 && in_array($search_term, strtolower($program['md']['cluster'])) ){
            array_push($return_values, $program);
        }
    }
    return $return_values;
}


function check_degree_types($program, $check_degree){
    $degrees = $program['md']['degree'];

    if( sizeof($degrees) == 0 )
        return true;

    foreach( $degrees as $degree ){
        if( strstr($degree, $check_degree ) == false )
            return true;
    }
    return false;
}


function integrate_xml_and_csv(){
    // Get the program_data from xml here
    $program_data = get_program_xml();

    // for each child
    $dir = realpath($_SERVER['DOCUMENT_ROOT'] . '/code/program-search/csv/');

    $files = scandir($dir);
    foreach($files as $name){
        if( $name != '.' && $name != '..') {
            $program_name = str_replace('.csv', '', $name);
            $program_data[$program_name]['csv-data'] = read_csv_file("$dir/$name");
        }
    }

    return $program_data;
}


function read_csv_file($path){
    $return_array = array();
    $column_headers = array();
    $row = 1;

    // Todo: this could probably be done in a couple lines less
    if (($handle = fopen($path, "r")) !== FALSE) {
        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
            $num = count($data);
            if( $row == 1 ) {
                $column_headers = $data;
            } else {
                $temp_data_array = array();
                foreach( $data as $i => $value ){
                    $temp_data_array[$column_headers[$i]] = $value;
                }
                array_push($return_array, $temp_data_array);
            }
            $row++;
        }
        fclose($handle);
    }

    return $return_array;
}


function get_program_xml(){
    $xml = simplexml_load_file($_SERVER["DOCUMENT_ROOT"] . "/_shared-content/xml/programs.xml");
    $programs = array();
    $programs = traverse_folder($xml, $programs);
    return $programs;
}


function traverse_folder($xml, $programs){
    foreach ($xml->children() as $child) {
        $name = $child->getName();
        if ($name == 'system-folder'){
            $programs = traverse_folder($child, $programs);
        }elseif ($name == 'system-block'){
            $page_data = inspect_program($child);

            // Child is the xml in this case.
            // Only add the to the calendar if it is an event.
            $dataDefinition = $child->{'system-data-structure'}['definition-path'];
            if( $dataDefinition == "Blocks/Program")
            {
                $programs[strval($page_data['name'])] = $page_data;
            }
        }
    }
    return $programs;
}


// Todo: Add more to this
// Todo: can we use xpath here or something? its pretty ugly as is.
// Gathers the information of an event page
function inspect_program($xml){
    //echo "inspecting page";
    $page_info = array(
        "name"          =>  strval($xml->name),
        "title"         =>  strval($xml->title),
        "display-name"  =>  strval($xml->{'display-name'}),
        "path"          =>  strval($xml->path),
        "md"            =>  array(),
        "deliveries"    =>  array(),
    );

    // ignore any program found within "_testing" in Cascade
    if( strpos($page_info['path'],"_testing") !== false) // just make "true"?
        return "";

    $ds = $xml->{'system-data-structure'};
    $dataDefinition = $ds['definition-path'];

    if( $dataDefinition == "Blocks/Program")
    {
        // Get the metadata
        foreach ($xml->{'dynamic-metadata'} as $md) {
            $name = strval($md->name);
            $options = array(
                'school',
                'degree',
                'cluster',
                'program-type',
                'department',
                'adult-undergrad-program',
                'graduate-program',
                'seminary-program'
            );

            if (in_array($name, $options)) {
                $page_info['md'][$name] = array();
                foreach ($md->value as $value) {
                    if( strtolower($value) != 'none' && strtolower($value) != 'select' )
                        array_push( $page_info['md'][$name], strval($value) );
                }

            }

        }

        // gather the program data
        $page_info['program_code'] = $ds->{'program'}->{'program_code'};
        $page_info['program_description'] = $ds->{'program'}->{'program_description'};
        $page_info['concentrations'] = array();

        foreach( $ds->{'concentration'} as $concentration){
            $temp_concentration = array();

            $temp_concentration['concentration_code'] = $concentration->{'concentration_code'};
            $temp_concentration['concentration_description'] = recursive_convert_xml_to_string($concentration->{'concentration_description'}->asXML());
            $temp_concentration['concentration_page'] = $concentration->{'concentration_page'}->{'path'};
            $temp_concentration['total_credits'] = $concentration->{'total_credits'};
            $temp_concentration['program_length'] = $concentration->{'program_length'};
            // todo: add courses

            $concentration_title = $concentration->{'concentration_banner'}->{'concentration_name'};
            if( $concentration_title == '' )
                $concentration_title = $page_info['title'];
            $temp_concentration['concentration_name'] = $concentration_title;
            $temp_concentration['cost'] = $concentration->{'concentration_banner'}->{'cost'};

            $temp_concentration['cohorts'] = array();
            foreach($concentration->{'concentration_banner'}->{'cohort_details'} as $cohort_detail){
                $temp_cohort_details = array();

                $temp_cohort_details['semester_start'] = $cohort_detail->{'semester_start'};
                $temp_cohort_details['year_start'] = $cohort_detail->{'year_start'};
                $temp_cohort_details['delivery_subheading'] = $cohort_detail->{'delivery_subheading'};
                $temp_cohort_details['delivery_description'] = $cohort_detail->{'delivery_description'};
                $temp_cohort_details['location'] = $cohort_detail->{'location'};

                // Get all deliveries
                $temp_cohort_details['delivery_label'] = $cohort_detail->{'delivery_label'};
                if( in_array('College of Arts & Sciences', $page_info['md']['school']) )
                    $temp_cohort_details['delivery_label'] = 'Face to Face';
                $delivery_value = trim(strval($temp_cohort_details['delivery_label']));
                if( !in_array($delivery_value, $page_info['deliveries']) )
                    array_push($page_info['deliveries'], $delivery_value);

                // add the entire cohort
                array_push($temp_concentration['cohorts'], $temp_cohort_details);
            }

            // get html for concentration
            $temp_concentration['html'] = get_html_for_program_concentration($page_info, $temp_concentration);
            // add concentration to program
            array_push($page_info['concentrations'], $temp_concentration);
        }
    }

    return $page_info;
}


function recursive_convert_xml_to_string($xml, $string=''){
    if( $xml && $xml != '' && property_exists($xml, 'hasChildren') && $xml->hasChildren() ){
        foreach($xml as $key => $child){
            if( $child->hasChildren() )
                recursive_convert_xml_to_string($child, $string);
            else
                $string .= "<$key>$child</$key>";
        }
    } else {
        $string .= "$xml";
    }

    return $string;
}

