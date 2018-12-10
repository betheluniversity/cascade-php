<?php
/**
 * Created by PhpStorm.
 * User: ces55739
 * Date: 5/10/16
 * Time: 4:01 PM
 */

require $_SERVER["DOCUMENT_ROOT"] . '/code/vendor/autoload.php';
include_once $_SERVER["DOCUMENT_ROOT"] . "/code/general-cascade/macros.php";

function create_faculty_bio_listing($schools, $cas, $caps, $gs, $sem, $displayFaculty){
    $bios = get_faculty_bio_xml();
    $bios = filter_bios($bios, $schools, $cas, $caps, $gs, $sem);

    // Sort bios
    if( $displayFaculty == 'All programs'){
        $bios = sort_bios_by_lead_and_last_name($bios, false);
    } else {
        $bios = sort_bios_by_lead_and_last_name($bios, true);
    }

    // Print bios
    $found_lead = false;
    $found_faculty = false;
    foreach( $bios as $bio){
        if( !is_array($bio))
            continue;
        if( array_key_exists('is_lead', $bio) ){
            if( $bio['is_lead'] && !$found_lead ) {
                // update title based on school. "Department Chairs" and "Lead Faculty & Program Director"
                if( in_array('College of Arts & Sciences', $schools) )
                    echo '<h1 class="uppercase-underlined mt5">Department Chair</h1>';
                else
                    echo '<h1 class="uppercase-underlined mt5">Program Directors & Lead Faculty</h1>';
                $found_lead = true;
            }
            elseif( !$bio['is_lead'] && !$found_faculty) {
                echo '<h1 class="uppercase-underlined mt5">Faculty</h1>';
                $found_faculty = true;
            }
        }

        echo create_bio_html($bio);
    }
}


function get_faculty_bio_xml(){
    $xml = simplexml_load_file($_SERVER["DOCUMENT_ROOT"] . "/_shared-content/xml/faculty-bios.xml");
    $bios = array();
    $bios = traverse_folder($xml, $bios);
    return $bios;
}


function traverse_folder($xml, $bios){
    $return_bios = array();
    foreach ($xml->children() as $child) {
        $name = $child->getName();
        if ($name == 'system-page'){
            $page_data = inspect_faculty_bio($child);
            $dataDefinition = $child->{'system-data-structure'}['definition-path'];
            if( $dataDefinition == "Faculty Bio" && is_array($page_data) == 1)
            {
                array_push($return_bios, $page_data);
            }
        }
    }
    return $return_bios;
}


function inspect_faculty_bio($xml){
    $page_info = array(
        "path"          =>  strval($xml->path),
        "id"            =>  strval($xml['id']),
        "md"            =>  array(),
        "job-titles"    =>  array(),
        "is_lead"       =>  false, // this is set in filter_bios
        "top_lead"      =>  false,
        "first"         =>  '',
        "last"          =>  ''
    );

    // if the file doesn't exist, skip it.
    if( !file_exists($_SERVER["DOCUMENT_ROOT"] . '/' . $page_info['path'] . '.php') ) {
        return "";
    }

    // ignore any bio found within "_testing" in Cascade
    if( strpos($page_info['path'],"_testing") !== false)
        return "";

    $ds = $xml->{'system-data-structure'};
    $dataDefinition = $ds['definition-path'];

    if( $dataDefinition == "Faculty Bio") {
        if( strval($ds->{'deactivate'}) == 'Yes' )
            return "";

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

        $page_info['first'] = strval($ds->{'first'});
        $page_info['last'] = strval($ds->{'last'});
        $page_info['email'] = strval($ds->{'email'});
        $page_info['started-at-bethel'] = strval($ds->{'started-at-bethel'});
        $page_info['image-path'] = strval($ds->{'image'}->{'path'});

        $faculty_locations = array();
        foreach ($xml->{'faculty_location'} as $location) {
            array_push($faculty_locations, strval($location->value));
        }
        if( sizeof($faculty_locations) == 0)
            array_push($faculty_locations, 'St. Paul');
        sort($faculty_locations);
        $page_info['faculty_location'] = $faculty_locations;

        $job_titles = $ds->{'job-titles'};
        foreach($job_titles as $job_title){
            $temp_job = array();
            $temp_job['school'] = strval($job_title->{'school'});
            $temp_job['department'] = strval($job_title->{'department'});
            $temp_job['adult-undergrad-program'] = strval($job_title->{'adult-undergrad-program'});
            $temp_job['graduate-program'] = strval($job_title->{'graduate-program'});
            $temp_job['seminary-program'] = strval($job_title->{'seminary-program'});
            $temp_job['department-chair'] = strval($job_title->{'department-chair'});
            $temp_job['program-director'] = strval($job_title->{'program-director'});
            $temp_job['lead-faculty'] = strval($job_title->{'lead-faculty'});
            $temp_job['job_title'] = strval($job_title->{'job_title'});

            array_push($page_info['job-titles'], $temp_job);
        }

        // Todo: remove these old job titles, once all are moved over.
        if( $page_info['job-titles'][0]['school'] == '' ){
            foreach( $ds->{'job-title'} as $job_title ) {
                $temp_job = array();

                $temp_job['school'] = $page_info['md']['school'];
                $temp_job['department'] = $page_info['md']['department'];
                $temp_job['adult-undergrad-program'] = $page_info['md']['adult-undergrad-program'];
                $temp_job['graduate-program'] = $page_info['md']['graduate-program'];
                $temp_job['seminary-program'] = $page_info['md']['seminary-program'];
                $temp_job['department-chair'] = '';
                $temp_job['program-director'] = '';
                $temp_job['lead-faculty'] = '';
                $temp_job['job_title'] = strval($job_title);

                array_push($page_info['job-titles'], $temp_job);
            }
        }

        $page_info['highlight'] = strval($ds->{'highlight'});

        // if highlight is > 450 characters, than only show 495 plus the 'read more' text
        if( strlen($page_info['highlight']) >= 450 ) {
            $page_info['highlight'] = substr($page_info['highlight'], 0, 495 );

            // add 'read more' html
            $twig = makeTwigEnviron('/code/faculty-bios/twig');
            $html = $twig->render('faculty-bio-read-more.html', array(
                'bio'   =>  $page_info
            ));

            $page_info['highlight'] = $page_info['highlight'] . $html;
        }
    }

    return $page_info;
}


# todo: this should really be done at the bio level...
function filter_bios($bios, $schools, $cas, $caps, $gs, $sem){
    $return_bios = array();
    foreach( $bios as $bio ){
        // if no school, match ALL bios
        if( !$schools || sizeof($schools) == 0 ) {
            array_push($return_bios, $bio);
        } else {
            foreach ($schools as $school) {
                $temp_array_of_job_titles = get_matched_job_titles_for_bio($bio, $school, $cas, $caps, $gs, $sem);
                $array_of_job_titles = array();
                if( sizeof($temp_array_of_job_titles) ) {
                    foreach( $temp_array_of_job_titles as $job_title){
                        // pass the job title 'lead' up to the bio level
                        if( $job_title['is_lead'] == true)
                            $bio['is_lead'] = true;
                        // pass the job title 'lead' up to the bio level (for diane dahl)
                        if( $job_title['top_lead'] == true)
                            $bio['top_lead'] = true;
                        array_push( $array_of_job_titles, $job_title['title']);
                    }
                    $bio['array_of_job_titles'] = $array_of_job_titles;
                    array_push($return_bios, $bio);
                }
            }
        }
    }

    return $return_bios;
}


// Todo: this could probably be done in fewer lines
function get_matched_job_titles_for_bio($bio, $school, $cas, $caps, $gs, $sem) {
    $matched_job_titles = array();

    if( is_array($bio) && array_key_exists('job-titles', $bio) && is_array($bio['job-titles']) ) {
        foreach ($bio['job-titles'] as $job_title) {
            $display_job_title = get_job_title($job_title, $bio['id']);
            // if school matches
            // A hack for Diane Dahl to appear on any page that includes 'nurs' in the program name
            if ($bio['id'] == 'aab255628c5865131315e7c4685d543b') {
                if (($school == 'College of Arts & Sciences' && (check_substring_in_array('nurs', $cas) || (sizeof($cas) == 1 && in_array('None', $cas))))
                    || ($school == 'College of Adult & Professional Studies' && (check_substring_in_array('nurs', $caps) || (sizeof($caps) == 1 && in_array('None', $caps))))
                    || ($school == 'Graduate School' && (check_substring_in_array('nurs', $gs) || (sizeof($gs) == 1 && in_array('None', $gs))))) {
                    array_push($matched_job_titles, $display_job_title);
                    break;
                }
            } else {
                if (str_replace('&', 'and', $school) == $job_title['school']) {
                    // depending on the school, check the associated list for program
                    if ($school == 'College of Arts & Sciences') {
                        if (in_array($job_title['department'], $cas) || (sizeof($cas) == 1 && in_array('None', $cas))) {
                            array_push($matched_job_titles, $display_job_title);
                        }
                    } elseif ($school == 'College of Adult & Professional Studies' || (gettype($school) == 'array' && in_array('College of Adult & Professional Studies', $school))) {

                        if (in_array($job_title['adult-undergrad-program'], $caps) || (sizeof($caps) == 1 && in_array('None', $caps))) {
                            array_push($matched_job_titles, $display_job_title);
                        }
                    } elseif ($school == 'Graduate School' || (gettype($school) == 'array' && in_array('Graduate School', $school))) {
                        if (in_array($job_title['graduate-program'], $gs) || (sizeof($gs) == 1 && in_array('None', $gs))) {
                            array_push($matched_job_titles, $display_job_title);
                        }
                    } elseif ($school == 'Bethel Seminary' || (gettype($school) == 'array' && in_array('Bethel Seminary', $school))) {
                        if (in_array($job_title['seminary-program'], $sem) || (sizeof($sem) == 1 && in_array('None', $sem))) {
                            array_push($matched_job_titles, $display_job_title);
                        }
                    }
                }
            }
        }
    }
    return $matched_job_titles;
}


function create_bio_html($bio){
    if( !is_array($bio)){
        if( $bio['image-path'] != '/') {
            $alt_text = $bio['first'] . ' ' . $bio['last'];
            $bio_image = srcset($bio['image-path'], false, true, 'image--round', "$alt_text");
        } else
            $bio_image = "<img src='https://www.bethel.edu/cdn/images/default-avatar.svg' class='image--round' alt='A default silhouette for faculty without images.' />";
        $twig = makeTwigEnviron('/code/faculty-bios/twig');
        $twig->addFilter(new Twig_SimpleFilter('format_job_titles','format_job_titles'));
        $html = $twig->render('faculty-bio.html', array(
            'bio'                   =>  $bio,
            'bio_image'             =>  $bio_image
        ));

        return $html;
    } else {
        return '';
    }
}


function get_job_title($job_title, $id){
    // This is a current hack to make sure Diane Dahl appears at the top, whenever she should appear
    if( $id == 'aab255628c5865131315e7c4685d543b') {
        $is_lead = true;
        $top_lead = true;
    }
    else {
        $is_lead = false;
        $top_lead = false;
    }

    if( $job_title['department-chair'] == 'Yes' )
        return array('top_lead' => $top_lead, 'is_lead' => true, 'title' => 'Department Chair');
    elseif( $job_title['program-director'] == 'Yes' )
        return array('top_lead' => $top_lead, 'is_lead' => true, 'title' => 'Program Director');
    elseif( $job_title['lead-faculty'] == 'Program Director' || $job_title['lead-faculty'] == 'Lead Faculty' )
        return array('top_lead' => $top_lead, 'is_lead' => true, 'title' => $job_title['lead-faculty']);
    else
        return array('top_lead' => $top_lead, 'is_lead' => $is_lead, 'title' => $job_title['job_title']);
}


function sort_bios_by_lead_and_last_name($bios, $top_lead_sort){
    $top_lead = array();
    $is_lead = array();
    $last = array();

    // Obtain a list of columns
    foreach ($bios as $key => $row) {
        if (array_key_exists('top_lead', $row))
            $top_lead[$key] = $row['top_lead'];

        if (array_key_exists('is_lead', $row))
            $is_lead[$key] = $row['is_lead'];

        if (array_key_exists('last', $row))
            $last[$key] = $row['last'];
    }

    // Sort the data with leads on top, then alpha sort
    // code gotten from http://stackoverflow.com/questions/4582649/php-sort-array-by-two-field-values
    if( $top_lead_sort ) {
        array_multisort($top_lead, SORT_DESC, $is_lead, SORT_DESC, $last, SORT_ASC, $bios);
    } else {
        array_multisort($is_lead, SORT_DESC, $last, SORT_ASC, $bios);
    }
    return $bios;
}


// format array to comma separated list with 'and' before the last element
function format_job_titles($job_titles){
    if(!is_array($job_titles))
        return '';

    // remove duplicates
    $job_titles = array_unique($job_titles);

    // code from -- http://stackoverflow.com/questions/8586141/implode-array-with-and-add-and-before-last-item
    return join(' and ', array_filter(array_merge(array(join(', ', array_slice($job_titles, 0, -1))), array_slice($job_titles, -1)), 'strlen'));
}

function check_substring_in_array($substring, $array){
    foreach ($array as $element) {
        if (strpos(strtolower($element), strtolower($substring)) !== FALSE) { // Yoshi version
            return true;
        }
    }
    return false;
}
