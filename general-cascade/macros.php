<?php
/**
 * Created by PhpStorm.
 * User: ejc84332
 * Date: 2/26/15
 * Time: 1:32 PM
 */

function pre($content){
    $twig = makeTwigEnviron('/code/general-cascade/twig');
    echo $twig->render('pre.html', array('content' => $content));
}

function carousel_create($class = "", $content)
{
   $twig = makeTwigEnviron('/code/general-cascade/twig');
    echo $twig->render('carousel.html', array(
        'class' => $class,
        'content' => $content));
}

// $print is to support image banks not echo-ing the carousel_item call.
function carousel_item($content, $classes, $link = null, $print=true){

    $twig = makeTwigEnviron('/code/general-cascade/twig');
    $render_content = $twig->render('carousel_item.html', array(
        'classes' => $classes,
        'link' => $link,
        'content' => $content
    ));

    if($print){
        echo $render_content;
    }else{
        return $render_content;
    }


}

function srcset($end_path, $print=true){
    if( strpos($end_path,"www.bethel.edu") == false ) {
        $end_path = "https://www.bethel.edu/$end_path";
    }

    $twig = makeTwigEnviron('/code/general-cascade/twig');
    $content = $twig->render('srcset.html', array(
        'end_path' => $end_path));

    if($print){
        echo $content;
    }else{
        return $content;
    }

}


function thumborURL($end_path, $width, $lazy=false, $print=true){

    $twig = makeTwigEnviron('/code/general-cascade/twig');
    $html = $twig->render('thumorURL.html', array(
        'end_path' => $end_path,
        'width' => $width,
        'lazy' => $lazy
    ));

    if($print){
        echo $html;
    }else{
        return $html;
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



function createGrid($classes, $content){
    $twig = makeTwigEnviron('/code/general-cascade/twig');
    return $twig->render('grid.html', array(
        'classes' => $classes,
        'content' => $content));

}

function gridCellOpen($classes){
    echo "<div class='grid-cell $classes'>";
}

function gridCellClose(){
    echo "</div>";
}

//classes is a string
function gridOpen($classes){
    echo "<div class='grid $classes'>";
}

function gridClose(){
    echo "</div>";
}

function createGridCell($classes, $content){
    $twig = makeTwigEnviron('/code/general-cascade/twig');
    return $twig->render('gridCell.html', array(
        'classes' => $classes,
        'content' => $content));
}


function checkInPath($url, $name){
    $path = $_SERVER['REQUEST_URI'];
    $pos = 0 === strpos($path, $url);
    //is this an index false positive?
    if($pos && $name == "Home"){
        $pos = $url == $_SERVER['REQUEST_URI'];
    }

    $twig = makeTwigEnviron('/code/general-cascade/twig');
    echo $twig->render('checkInPath.html', array(
        'pos' => $pos,
        'name' => $name,
        'url' => $url));
}

function navListItem($pageStartsWith, $test_starts_with, $path, $label, $classes=''){

    //twig version
    $twig = makeTwigEnviron('/code/general-cascade/twig');
    echo $twig->render('navListItem.html', array(
       'pageStartsWith' => $pageStartsWith,
        'test_starts_with' => $test_starts_with,
        'path' => $path,
        'label' => $label,
        'classes' => $classes));
}

function makeTwigEnviron($path){

    $loader = new Twig_Loader_Filesystem($_SERVER["DOCUMENT_ROOT"] . $path);
    $twig = new Twig_Environment($loader);
    return $twig;

}

function autoCache($func, $inputs, $cache_name = null, $cache_time = 300)
{

    //if no cache_name is passed in it defaults to all inputs strung together with -
    if (!$cache_name) {
        $cache_name = $inputs[0];
        reset($inputs);
        while (next($inputs) !== FALSE) {
            $cache_name .= "-" . current($inputs);
        }
    }

    $cache = new Memcache;
    $cache->connect('localhost', 11211);
    $data = $cache->get($cache_name);
    error_log("\n\nNew Run of AutoCache\n----------------------------------\n", 3, '/tmp/memcache.log');
    error_log("$func function being used and the cache_name is $cache_name\n", 3, '/tmp/memcache.log');
    if (!$data) {
        error_log("Full Data Array Memcache miss\n", 3, '/tmp/memcache.log');
        $data = call_user_func_array($func, $inputs);
        try {
            $cache->set($cache_name, $data, MEMCACHE_COMPRESSED, $cache_time);
        } catch (Exception $e) {
            error_log("Error\n----------------------------------\n" . $e->getMessage() . "\n----------------------------------\n", 3, '/tmp/memcache.log');
        }
    } else {
        error_log("Full Data Array Memcache hit\n", 3, '/tmp/memcache.log');
    }

    return $data;
}
