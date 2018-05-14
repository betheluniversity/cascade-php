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

require $_SERVER["DOCUMENT_ROOT"] . '/code/vendor/autoload.php';
include_once $_SERVER['DOCUMENT_ROOT'] . '/code/general-cascade/macros.php';
if( array_key_exists("date", $_GET) )
    $date = date($_GET["date"]);
else //just in case no date is passed over.
    $date = date('d-m-Y');

// fix date to be formatted correctly
$date_array = explode('-', $date);
if( strlen($date_array[0]) == 1)
    $date = '0'.$date;

get_e_announcements($date);

function get_e_announcements($date){
    $xml = simplexml_load_file($_SERVER['DOCUMENT_ROOT'] ."/_shared-content/xml/e-announcements.xml");
    $e_announcements = $xml->xpath("//system-block");
    $matches = array();
    foreach($e_announcements as $e_announcement_xml){
        $info = inspect_block_e_announcements($e_announcement_xml);
        if( $date == $info['first-date'] || $date == $info['second-date']){
            array_push($matches, $info);
        }
    }

    //sort alpha
    // Todo: update sort to be by publish date.
    usort($matches, "alpha_sort");
    foreach( $matches as $match){
        echo $match['html'];
    }

    if( sizeof($matches) == 0)
        echo "<p>There are no E-Announcements for this day.</p>";
}


function remove_tags($xml){
    $xml = str_replace('<first-date>', '', $xml);
    $xml = str_replace('</first-date>', '', $xml);
    $xml = str_replace('<second-date>', '', $xml);
    $xml = str_replace('</second-date>', '', $xml);
    return $xml;
}


function inspect_block_e_announcements($xml){
    $page_info = array(
        "html" => "",
        "first-date" => false,
        "second-date" => false,
    );

    $ds = $xml->{'system-data-structure'};
    $page_info['first-date'] = remove_tags($ds->{'first-date'}->asXML());
    $page_info['second-date'] = remove_tags($ds->{'second-date'}->asXML());

    $title = $xml->{'title'};
    // Todo: this is including <message></message> tags. Those should be removed.
    $message = $ds->{'message'}->asXML();

    $md = $xml->{'dynamic-metadata'};
    $roles_array = array();
    foreach( $md as $roles){
        if( $roles->{'name'} == 'banner-roles'){
            foreach( $roles->{'value'} as $role)
                array_push($roles_array, $role);
        }
    }
    $roles_string = implode(", ", $roles_array);

    // Todo: move this to twig.
    $content = createGridCell('u-large-2-3',"<h3 class='mb1'>$title</h3>" . strip_tags($message, "<div><a><p><b><strong><br><br ><em>") . "<div style='color:#777;font-size:10px'>$roles_string</div>" );

    $page_info['html'] = createGrid('grid--center mt4', $content);

    return $page_info;
}

function alpha_sort($a, $b)
{
    if ($a == $b) {
        return 0;
    }
    return ($a < $b) ? -1 : 1;
}