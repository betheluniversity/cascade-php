<?php
/**
 * Created by PhpStorm.
 * User: ces55739
 * Date: 8/12/15
 * Time: 3:12 PM
 */

// Todo:
// read in xml
// find all that match
// create twig and return them

include_once $_SERVER['DOCUMENT_ROOT'] . '/code/general-cascade/macros.php';
if( array_key_exists("date", $_GET) )
    $date = $_GET["date"];
else //just in case no date is passed over.
    $date = date('d-m-Y');

// fix date to be formatted correctly
$date_array = explode('-', $date);
if( strlen($date_array[0]) == 1)
    $date = '0'.$date;

get_proof_points($date);

function get_proof_points($date){
    $xml = simplexml_load_file($_SERVER['DOCUMENT_ROOT'] ."/_shared-content/xml/e-announcements.xml");
    $e_announcements = $xml->xpath("//system-page");
    $matches = array();

    foreach($e_announcements as $e_announcement_xml){
        $info = inspect_block_e_announcements($e_announcement_xml);
        if( strcmp(date($info['first-date']), $date) == 0 || strcmp(date($info['second-date']), $date) == 0 ){
            array_push($matches, $info);
        }
    }

    //sort alpha
    usort($matches, "alpha_sort");

    // Get random selection of $numItems proof points
    foreach( $matches as $match){
        echo $match['html'];
    }

    if( sizeof($matches) == 0)
        echo "<p>There are no E-Announcements for this day.</p>";
}


function inspect_block_e_announcements($xml){
    $page_info = array(
        "html" => "",
        "first-date" => false,
        "second-date" => false,
    );

    $ds = $xml->{'system-data-structure'};
    $page_info['first-date'] = $ds->{'first-date'};
    $page_info['second-date'] = $ds->{'second-date'};

    $title = $xml->{'title'};
    $message = $xml->{'system-data-structure'}->{'message'};

    $md = $xml->{'dynamic-metadata'};
    $roles_array = array();
    foreach( $md as $roles){
        if( $roles->{'name'} == 'banner-roles'){
            foreach( $roles->{'value'} as $role)
                array_push($roles_array, $role);
        }
    }
    $roles_string = implode(", ", $roles_array);

    // For some reason, I was unable to get twig to work with this. So I defaulted to building it here in php.
    $page_info['html'] = '<h3 style="margin:0;padding:0;font-family:"Lucida Grande",Helvetica,Arial,sans-serif;margin-top:10px;font-size:12px;margin-top:30px">' . $title . '</h3>';
    $page_info['html'] .= $message;
    $page_info['html'] .= '<span style="color:#777;font-size:10px">' . $roles_string . '</span>';

    return $page_info;
}

function alpha_sort($a, $b)
{
    if ($a == $b) {
        return 0;
    }
    return ($a < $b) ? -1 : 1;
}