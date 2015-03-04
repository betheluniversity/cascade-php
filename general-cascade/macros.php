<?php
/**
 * Created by PhpStorm.
 * User: ejc84332
 * Date: 2/26/15
 * Time: 1:32 PM
 */


function pre($content){
    echo "<pre>";
    echo $content;
    echo "</pre>";
}


function carousel_open($class = ""){
    echo "<div class='slick-carousel $class'>";
}

function carousel_close(){
    // basic for now but just in case it gets more complicated
    echo "</div>";
}

function carousel_item($content, $link = null){
    echo '<div class="slick-item">';
    if($link){
        echo "<a href='$link'>$content</a>";
    }else{
        echo $content;
    }
    echo '</div>';
}

function srcset($end_path, $print=true){
    $rand = rand(1,4);
    $content = "<img srcset='
                //cdn$rand.bethel.edu/resize/unsafe/1400x0/smart/$end_path 1400w,
                //cdn$rand.bethel.edu/resize/unsafe/1200x0/smart/$end_path 1200w,
                //cdn$rand.bethel.edu/resize/unsafe/1000x0/smart/$end_path 1000w,
                //cdn$rand.bethel.edu/resize/unsafe/800x0/smart/$end_path 800w,
                //cdn$rand.bethel.edu/resize/unsafe/600x0/smart/$end_path 600w,
                //cdn$rand.bethel.edu/resize/unsafe/400x0/smart/$end_path 400w,
                //cdn$rand.bethel.edu/resize/unsafe/200x0/smart/$end_path 200w'
                src='//cdn$rand.bethel.edu/resize/unsafe/320x0/smart/$end_path'></img>";
    if($print){
        echo $content;
    }else{
        return $content;
    }

}

function xml2array($xml){
    $arr = array();
    foreach ($xml as $element){
        $tag = $element->getName();
        $e = get_object_vars($element);
        if (!empty($e)){
            $arr[$tag] = $element instanceof SimpleXMLElement ? xml2array($element) : $e;
        }else{
            $arr[$tag] = trim($element);
        }
    }
    return $arr;
}