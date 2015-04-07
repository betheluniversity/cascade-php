<?php
/**
 * Created by PhpStorm.
 * User: ces55739
 * Date: 4/7/15
 * Time: 12:32 PM
 */


function renderSiteNavigation($currentPagePath){
    $xml = get_xml($_SERVER["DOCUMENT_ROOT"] . "/_shared-content/xml/all-cascade.xml" );
}

function get_xml($fileToLoad){
    $xml = simplexml_load_file($fileToLoad);
    $pages = array();
    $pages = traverse_folder($xml, $pages);
    return $pages;
}

function traverse_folder($xml, $pages){
    foreach ($xml->children() as $child) {
        if( $child->{'system-data-structure'}->{'dynamic-metadata'}->{'hide-from-nav'} == "Do not hide") {

        }
        $name = $child->getName();

        if ( $name == 'system-folder'){
            // get metadata from folder.
            array_push($pages, traverse_folder($child, $pages));
        }elseif ( $name == 'system-page'){

                array_push($pages, $child);
        }elseif( $name == 'system-block' ){
            // block with name="setup"
            if( $child->{'system-data-structure'}->{'definition-path'} == "Setup Block" ){
                array_push($pages, $child);
            }
        }
    }

    return $pages;
}