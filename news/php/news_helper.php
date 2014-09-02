<?php
/**
 * Created by PhpStorm.
 * User: ejc84332
 * Date: 8/28/14
 * Time: 2:40 PM
 */


function get_news_xml(){
    $xml = simplexml_load_file($_SERVER["DOCUMENT_ROOT"] . '/_shared-content/xml/articles.xml');
    // for each page, add an item to an array with relevant info. For now, title, image url, publish date, and teaser.
    $pages = array();
    $pages = traverse_folder($xml, $pages);


    usort($pages, function($a, $b) {
        return intval($a['date'][0]) < intval($b['date'][0]);
    });

    return $pages;
}


function traverse_folder($xml, $pages){
    foreach ($xml->children() as $child) {
        $name = $child->getName();
        if ($name == 'system-folder'){
            $pages = traverse_folder($child, $pages);
        }elseif ($name == 'system-page'){

            $dataDefinition = $child->{'system-data-structure'}['definition-path'];
            if( $dataDefinition == "News Article")
            {
                $page_info = get_news_item_details($child);
                array_push($pages, $page_info);
                $x = 1;
            }
        }
    }
    return $pages;
}

function get_news_item_details($page){

    $ds = $page->{'system-data-structure'};
    $md = $page->{'dynamic-metadata'};
    $page_info = array(
        "title" => $page->{'title'},
        "path" => $page->{'path'},
        "teaser" => $page->{'teaser'},
        "date" => $ds->{'publish-date'},
        "image" => $ds->{'media'}->{'image'}->{'path'},
        "categories" => array(),
    );

    foreach ($page->{'dynamic-metadata'} as $md){

        $name = $md->name;
        if ($name == "unique-news"){
            foreach($md->value as $value ){
                array_push($page_info['categories'], $value);
            }
        }
    }

    return $page_info;
}