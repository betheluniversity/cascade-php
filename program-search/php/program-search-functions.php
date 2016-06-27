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
                array_push( $programs, $page_data );
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
        "actual-title"  =>  strval($xml->title)
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

            $temp_concentration['concentration_code'] = strval($concentration->{'concentration_code'});
            $temp_concentration['concentration_description'] = recursive_convert_xml_to_string($concentration->{'concentration_description'}->asXML());
            $temp_concentration['concentration_page'] = $concentration->{'concentration_page'};
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

            // if block has a display-name, use that. Then use the concentration page title, then use the title of the block
            if( $page_info['display-name'] != '' ){
                $temp_concentration['title'] = $page_info['display-name'];
            }
            elseif( $temp_concentration['concentration_page']->{'path'} != '/' ) {
                $temp_concentration['title'] = strval($temp_concentration['concentration_page']->{'title'});
                $temp_concentration['title'] = str_replace('Program Details', '', $temp_concentration['title']);
                $temp_concentration['title'] = str_replace('Concentration', '', $temp_concentration['title']);
                $temp_concentration['title'] = str_replace('Major', '', $temp_concentration['title']);
            }
            else {
                $temp_concentration['title'] = $page_info['title'];
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


// compare by md.title of concentration pages or by program title
function program_sort_by_titles($a, $b) {
    return strcmp($a['concentration']['title'], $b['concentration']['title']);
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
    $twig->addFilter(new Twig_SimpleFilter('convert_degrees_to_shorthand','convert_degrees_to_shorthand'));
    $html = $twig->render('concentration.html', array(
        'concentration'         => $concentration,
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
    $schoolArray = explode(',',$schoolArray[0]);
    $deliveryArray = $data[2];
    $degreeType = $data[3];

        // Get the csv data as single csv file
//    $csv_data = read_csv_file($_SERVER['DOCUMENT_ROOT'] . '/code/program-search/csv/programs-test.csv');
    $csv_data = read_csv_file('/var/www/cms.pub/code/program-search/csv/programs.csv');
//    $csv_data = autoCache("read_csv_file", array($_SERVER['DOCUMENT_ROOT'] . '/code/program-search/csv/test.csv'), 'program-search-csv-data');
    $return_values = array();


    // csv matches
    $csv_elements_to_add = search_csv_values($csv_data, $search_term );

    // Todo: depending on what adds it to the list, should that effect sorting?
    // for example, if cluster matches, should that be lower on the search? (or should we highlight anything?)
    foreach($program_data as $program){
        // 1) school does not match
        if( !count(array_intersect($schoolArray, $program['md']['school'])) )
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
            elseif( in_array($concentration['concentration_code'], $csv_elements_to_add) || in_array($program['name'], $csv_elements_to_add) ) {
                array_push($return_values, $values_to_push);
            }
        }
    }

    return $return_values;
}


function search_csv_values($csv_data, $search_term ){
    $unwanted_search_keys = array(
        'bachelor of art',
        'bachelor of arts'
    );
    print_r('<table>');
    print_r('<thead><tr><th>Program</th><th>Matching Tag</th></tr></thead>');
    $return_element = array();
    foreach ($csv_data as $row) {
        // ignore key
        $has_unwanted_key = false;
        foreach( $unwanted_search_keys as $unwanted_search_key ){
            if ( strpos(trim(strtolower($row['tag'])), $unwanted_search_key) !== false ) {
                $has_unwanted_key = True;
            }
        }
        // Version 1) exact match
//        if ($search_term == trim(strtolower($row['tag'])) && !in_array($row['key'], $return_element) )
//            array_push($return_element, $row['key']);

        // Version 2) sub match
//            if ( (strpos(trim(strtolower($row['tag'])), $search_term) !== false && !in_array($row['key'], $return_element)) && !$has_unwanted_key ) {
//                array_push($return_element, $row['key']);
//            }

        // Version 3) sub match with extra check for spaces before
        $does_it_match = array();
        preg_match("/(.*^ | |^)$search_term/", trim(strtolower($row['tag'])) , $does_it_match);
        print_r('<tr>');
        if ( ( sizeof($does_it_match) > 0 && !in_array($row['key'], $return_element)) && !$has_unwanted_key ) {
            print_r('<td>');
            print_r($row['key']);
            print_r('</td>');
            print_r('<td>');
            print_r($row['tag']);
            print_r('</td>');
            array_push($return_element, $row['key']);
        }
        print_r('</tr>');

        // Todo: add special cases for the 'types' that it matches?
    }
    print_r('</table>');
    return $return_element;
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


function convert_degrees_to_shorthand($degrees){
    foreach($degrees as $degree){
        if( $degree == "Associate of Arts")
            return 'A.A.';
        elseif( $degree == "Bachelor of Arts")
            return 'B.A.';
        elseif( $degree == "Bachelor of Music")
            return 'B.M.';
        elseif( $degree == "Bachelor of Science")
            return 'B.S.';
        elseif( $degree == "Bachelor of Fine Arts")
            return 'B.F.A';
        elseif( $degree == "Master of Arts")
            return 'M.A.';
        elseif( $degree == "Master of Science")
            return 'M.S.';
        elseif( $degree == "Master of Divinity")
            return 'M.Div';
        elseif( $degree == "Doctor of Ministry")
            return 'D.Min';
        elseif( $degree == "Doctor of Education")
            return 'Ed.D.';
        elseif( $degree == "Certificate")
            return 'Certificate';
        elseif( $degree == "Post-grad Certificate")
            return 'Post-graduate Certificate';
        elseif( $degree == "License")
            return 'License';
    }
}