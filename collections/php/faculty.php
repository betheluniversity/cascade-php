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
    $xml = autoCache("simplexml_load_file", array($faculty_file), 'faculty_carousel_xml');
    $faculty_pages = $xml->xpath("//system-page[system-data-structure[@definition-path='Faculty Bio']]");
    shuffle($faculty_pages);
    $bios = find_matching_bios($faculty_pages, $categories);

    $carousel_items = "";
    foreach($bios as $bio){
        $ds = $bio->{'system-data-structure'};
        $first = $ds->first;
        $last = $ds->last;
        $title = $ds->{'job-title'};
        $path = $bio->path;
        $image = "https://www.bethel.edu" . $ds->image->path[0];
        $html = create_twig_html($image, $first, $last, $title, $path);

        $carousel_items .= carousel_item($html, "", null, false);
    }
    carousel_create("carousel--employee js-rotate-order-carousel js-load-on-demand", $carousel_items);
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

function create_twig_html($image, $first, $last, $title, $path){

    $twig = makeTwigEnviron('/code/collections/twig');
    $thumbURL = thumborURL($image, '150', $lazy=false, $print=false);

    $html = $twig->render('faculty.html', array(
        'first'     => $first,
        'last'      => $last,
        'title'     => $title,
        'path'      => $path,
        'thumbURL'  => $thumbURL
    ));
    return $html;
}