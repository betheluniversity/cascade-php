<?php
/**
 * Created by PhpStorm.
 * User: cav28255
 * Date: 5/8/15
 * Time: 9:31 AM
 */
include_once $_SERVER["DOCUMENT_ROOT"] . "/code/general-cascade/feed_helper.php";

function create_list($categories){
    echo "<ol>";
    get_xml($_SERVER["DOCUMENT_ROOT"] . "/_shared-content/xml/events.xml", $categories, "check_if_all_day");
    echo "</ol>";
    return;
}


function check_if_all_day($xml, $categories){
    $ds = $xml->{'system-data-structure'};

    if( $ds['definition-path'] == "Event")
    {
        $id = $xml['id'];
        $path = "https://cms.bethel.edu/entity/edit.act?id=$id&type=page";
        $dates = $ds->{'event-dates'};

        if($dates->{'all-day'}->{"value"} == "Yes")
        {
            $page_info["display-on-feed"] = true;
            echo "<li><a href='http://www.bethel.edu$xml->path'>$xml->path</a> --- <a href='$path'>$xml->title</a></li>";
        }
    }


    return;
}