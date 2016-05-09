<?php
/**
 * Created by PhpStorm.
 * User: ces55739
 * Date: 4/28/16
 * Time: 9:20 AM
 */


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

        // a double check to make sure degree is set in metadata
        if( !array_key_exists('degree', $page_info['md']) )
            $page_info['md']['degree'] = array();

        // gather the program data
        $page_info['program_code'] = $ds->{'program'}->{'program_code'};
        $page_info['program_description'] = $ds->{'program'}->{'program_description'};
        $page_info['concentrations'] = array();

        // todo: add courses?
        foreach( $ds->{'concentration'} as $concentration){
            $temp_concentration = array();

            $temp_concentration['concentration_code'] = $concentration->{'concentration_code'};
            $temp_concentration['concentration_description'] = recursive_convert_xml_to_string($concentration->{'concentration_description'}->asXML());
            $temp_concentration['concentration_page'] = $concentration->{'concentration_page'}->{'path'};
            $temp_concentration['total_credits'] = $concentration->{'total_credits'};
            $temp_concentration['program_length'] = $concentration->{'program_length'};
            $temp_concentration['concentration_name'] = strval($concentration->{'concentration_banner'}->{'concentration_name'});
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


function program_sort_by_school_then_title($a, $b) {
    // compare by school
    $c = strcmp($a['program']['md']['school'][0], $b['program']['md']['school'][0]);
    if($c != 0) {
        return $c;
    }

    // compare by concentration_name or by title
    if( $a['concentration']['concentration_name'] != '')
        $aName = $a['concentration']['concentration_name'];
    else
        $aName = $a['program']['title'];

    if( $b['concentration']['concentration_name'] != '')
        $bName = $b['concentration']['concentration_name'];
    else
        $bName = $b['program']['title'];

    return strcmp($aName, $bName);
}


function get_html_for_table($degrees_array){
    $twig = makeTwigEnviron('/code/program-search/twig');
    $html = $twig->render('program-search-table.html', array(
        'degrees_array'=> $degrees_array
    ));

    return $html;
}

// Todo: call the functions for creating grid/grid cells instead?
function get_html_for_program_concentration($program, $concentration){
    $twig = makeTwigEnviron('/code/program-search/twig');
    $html = $twig->render('concentration.html', array(
        'concentration_name'    => $concentration['concentration_name'],
        'title'                 => $program['title'],
        'program_types'         => $program['md']['program-type'],
        'concentration_page'    => $concentration['concentration_page'],
        'deliveries'            => $program['deliveries'],
        'degrees'               => $program['md']['degree'],
        'concentration_code'    => $concentration['concentration_code'],
        'schools'               => $program['md']['school']
    ));

    return $html;
}


function search_programs($program_data, $data){
    // gather the input data
    $search_term = trim(strtolower($data[0]));
    $schoolArray = $data[1];
    $deliveryArray = $data[2];
    $degreeType = $data[3];

    // Get the csv data as single csv file
    $csv_data = read_csv_file($_SERVER['DOCUMENT_ROOT'] . '/code/program-search/csv/test.csv');
//    $csv_data = autoCache("read_csv_file", array($_SERVER['DOCUMENT_ROOT'] . '/code/program-search/csv/test.csv'), 'program-search-csv-data');
    $return_values = array();

    // Todo: depending on what adds it to the list, should that effect sorting?
    // for example, if cluster matches, should that be lower on the search? (or should highlighting do anything?)
    foreach($program_data as $program){
        // 1) school does not match
        if( !(in_array('All', $schoolArray) || in_array('all', $schoolArray)) && !count(array_intersect($schoolArray, $program['md']['school'])) )
            continue;

        // 2) delivery does not match -- if F2F is selected and school is CAS, it should be shown
        if( !count(array_intersect($deliveryArray, $program['deliveries'])) ){
            if( !(in_array('Face to Face', $deliveryArray) && in_array('College of Arts & Sciences', $program['md']['school'])) ) {
                continue;
            }
        }

        // 3) degree type does not match
        if( !($degreeType == 'All' || $degreeType == 'all') && check_degree_types($program, $degreeType) )
            continue;

        foreach( $program['concentrations'] as $concentration){
            $values_to_push = array('program' => $program, 'concentration' => $concentration);
            $cluster_lower_case = array_map('strtolower', $program['md']['cluster']);

            // If the $search_term matches, then add it
            // default -- displaying all if no search term is entered
            if( $search_term == '' ) {
                array_push($return_values, $values_to_push);
            }
            // Concentration name matches (contains) -- if search key is in concentration name
            elseif( $concentration['concentration_name'] != '' && strpos(strtolower($concentration['concentration_name']), $search_term) !== false ) {
                array_push($return_values, $values_to_push);
            }
            // program title matches (contains) -- if search key is in program title
            elseif( strpos(strtolower($program['title']), $search_term) !== false ) {
                array_push($return_values, $values_to_push);
            }
            // cluster matches -- if search key is in cluster
            elseif( sizeof($program['md']['cluster'] > 0) && in_array($search_term, $cluster_lower_case) ){
                array_push($return_values, $values_to_push);
            }
            // csv matches
            // Todo: should this be an exact match or partial match?
            elseif( search_csv_values($csv_data, $concentration['concentration_code'], $search_term) ){
                array_push($return_values, $values_to_push);
            }
        }
    }
    return $return_values;
}


function search_csv_values($csv_data, $concentration_code, $search_term){
    foreach($csv_data as $row){
        if( $search_term == trim(strtolower($row['Tag'])) && $concentration_code == $row['Concentration Code'] )
            return true;
    }
    return false;
}


function read_csv_file($path){
    $path = realpath($path);

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