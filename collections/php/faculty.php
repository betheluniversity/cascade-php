<?php
/**
 * Created by PhpStorm.
 * User: ejc84332
 * Date: 3/3/15
 * Time: 2:12 PM
 */


function create_faculty_carousel($categories){


    $faculty_file = $_SERVER["DOCUMENT_ROOT"] . "/_shared-content/xml/faculty-bios.xml";
    $xml = simplexml_load_file($faculty_file);
    $faculty_pages = $xml->xpath("//system-page[system-data-structure[@definition-path='Faculty Bio']]");
    shuffle($faculty_pages);
    $bios = find_matching_bios($faculty_pages, $categories);

//    echo "<pre>";
//    print_r($faculty_pages[0]);
//    echo "</pre>";


    carousel_open("carousel--quote");
    foreach($bios as $bio){
        $ds = $bio->{'system-data-structure'};
        $first = $ds->first;
        $last = $ds->last;
        $title = $ds->{'job-title'};
        $image = "https://www.bethel.edu" . $ds->image->path[0];


        $html =  '<div class="pa1  quote  grayLighter"><div class="grid "><div class="grid-cell  u-medium-3-12"><div class="grid-pad-1x"><div class="quote__avatar">';
        $html .= srcset($image, $print=false);
        $html .= '</div></div></div>';
        $html .= '<div class="grid-cell  u-medium-9-12"><div class="grid-pad-1x">';
        $html .= "<div>$first $last</div>";
        $html .= "<div>$title</div>";
        $html .= "</div></div></div></div>";

        carousel_item($html);
    }
    carousel_close();

    // todo: Display 7 bios that match one of the values in $categories and have the following info:
    //   -  name, job title, image
    //   - Name should link to the bio page.
    // They should be in a carousel (see general-cascade/marcos.php and example above)


  }


function find_matching_bios($xml, $categories){
    $return_bios = array();
    foreach($xml as $bio){
        $md = $bio->{'dynamic-metadata'};
        foreach($md as $data){
            $name = $data->name;
            if($name == 'school'){
                $ds = $bio->{'system-data-structure'};
                $image = $ds->image->path;
                if(in_array($data->value, $categories) && $ds->first && $ds->last && $ds->{'job-title'} && $image[0] != '/'){
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