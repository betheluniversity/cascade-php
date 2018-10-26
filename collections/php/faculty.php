<?php
/**
 * Created by PhpStorm.
 * User: ejc84332
 * Date: 3/3/15
 * Time: 2:12 PM
 */
//todo convert to grid and gridcell macros

function create_faculty_carousel($categories){
    $faculty_file = $_SERVER["DOCUMENT_ROOT"] . "/_shared-content/xml/faculty-bios.xml";
    $xml = autoCache("simplexml_load_file", array($faculty_file));
    $faculty_pages = $xml->xpath("//system-page[system-data-structure[@definition-path='Faculty Bio']]");
    shuffle($faculty_pages);
    $bios = find_matching_bios($faculty_pages, $categories);
    $carousel_items = "";
    foreach($bios as $bio){
        $ds = $bio->{'system-data-structure'};
        $first = $ds->first;
        $last = $ds->last;
        $path = $bio->path;
        $image = "https://thumbor.bethel.edu" . $ds->image->path[0];
        $title = $ds->{'job-title'};
        $titles = $ds->{'job-titles'};
        if($titles[0]->{'school'} != "") {
            $jobsAsSting = create_new_job_titles($titles);
            $html = create_twig_html($image, $first, $last, $jobsAsSting, $path);
        }else {
            $html = create_twig_html($image, $first, $last, $title, $path);
        }
        $carousel_items .= carousel_item($html, "", null, false);
    }
    carousel_create("flickity  carousel--employee", $carousel_items);
    // todo: Display 7 bios that match one of the values in $categories and have the following info:
    //   -  name, job title, image
    //   - Name should link to the bio page.
    // They should be in a carousel (see general-cascade/marcos.php and example above)
}
function find_matching_bios($xml, $categories){
    $return_bios = array();
    foreach($xml as $bio){
        $ds = $bio->{'system-data-structure'};

        if( strval($ds->deactivate) == 'Yes' ) {
            continue;
        }

        // if the file doesn't exist, skip it.
        if( !file_exists('/var/www/cms.pub/' . $bio->{'path'} . '.php') ) {
            continue;
        }

        $md = $bio->{'dynamic-metadata'};
        foreach($md as $data){
            $name = $data->name;
            if($name == 'school') {
                $ds = $bio->{'system-data-structure'};
                $image = $ds->image->path;

                if (in_array($data->value, $categories) && $ds->first && $ds->last && $ds->{'job-titles'}->{'school'}[0] && $image[0] != '/') {
                    array_push($return_bios, $bio);
                    break;
                }
            }
        }
        if(count($return_bios) > 7){
            break;
        }
    }
    return $return_bios;
}



function create_twig_html($image, $first, $last, $title, $path){

    $twig = makeTwigEnviron('/code/collections/twig');
    $thumbURL = thumborURL($image, '150', $lazy=true, $print=false, $alt_text=$first . ' ' . $last);

    $html = $twig->render('faculty.html', array(
        'first'     => $first,
        'last'      => $last,
        'title'     => $title,
        'path'      => $path,
        'thumbURL'  => $thumbURL
    ));
    return $html;
}

function create_new_job_titles($titles){
    $job_map = array();
    $job_title_array = array();
    $school_array = array();
    $program_array = array();

    foreach($titles as $title){
        array_push($school_array, $title->{'school'});

        if($title->{'department'}){
            array_push($program_array, $title->{'department'});
        }elseif($title->{'adult-undergrad-program'}){
            array_push($program_array, $title->{'adult-undergrad-program'});
        }elseif($title->{'graduate-program'}){
            array_push($program_array, $title->{'graduate-program'});
        }elseif($title->{'seminary-program'}){
            array_push($program_array, $title->{'seminary-program'});
        }else{
            array_push($program_array, '');
        }

        if($title->{'department-chair'} == 'Yes'){
            array_push($job_title_array, 'Department Chair');
        }elseif($title->{'program-director'} == 'Yes'){
            array_push($job_title_array, 'Program Director');
        }elseif($title->{'lead-faculty'} == 'Lead Faculty' || $title->{'lead-faculty'} == 'Program Director'){
            array_push($job_title_array, $title->{'lead-faculty'});
        }elseif($title->{'job_title'}){
            array_push($job_title_array, $title->{'job_title'});
        }else{
            array_push($job_title_array, " ");
        }
    }
    for($i = 0; $i < sizeof($school_array); $i++) {
        $currentSchool = $school_array[$i];
        $currentProgram = $program_array[$i];
        $currentJob = $job_title_array[$i];
        if( is_string($currentSchool) )
            if(!array_key_exists($currentSchool, $job_map)){
                $job_map["$currentSchool"] = array();
            }
            if(!array_key_exists($currentSchool, $job_map["$currentSchool"])){
                $job_map["$currentSchool"]["$currentProgram"] = array();
            }
            if(!array_key_exists($currentSchool, $job_map["$currentSchool"]["$currentProgram"])){
                array_push($job_map["$currentSchool"]["$currentProgram"], $currentJob);
            }
        }
    }
    $jobsAsString = "";
    foreach($job_map as $key => $value){
        foreach($value as $key2 => $value2) {
            $size = sizeof($value2);
            for($j = 0; $j < $size; $j++) {
                if($j == ($size-1) && $size > 1){
                    $jobsAsString = $jobsAsString . " and ";
                }
                $jobsAsString = $jobsAsString . "$value2[$j]";
                if($j < ($size-1) && $size > 2){
                    $jobsAsString = $jobsAsString . ", ";
                }
            }
            if($key2 != "None") {
                $jobsAsString = $jobsAsString . " in $key2 ";
            }
        }
        
    }
    return $jobsAsString;

}
