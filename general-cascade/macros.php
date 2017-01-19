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

function carousel_create($class = "", $content){
   $twig = makeTwigEnviron('/code/general-cascade/twig');
    echo $twig->render('carousel.html', array(
        'class' => $class,
        'content' => $content
    ));
}

function build_carousel_from_array($array, $class){
    $content = "";
    foreach($array as $item){
        $content .= $item;
    }

    carousel_create($class, $content);
}

// Currently some velocity files are using this. (should be deleted soon)
function carousel_open($class = ""){
    echo "<div class='flickity js-rotate-order-carousel js-load-on-demand  $class'>";
}

// Currently some velocity files are using this. (should be deleted soon)
function carousel_close(){
    // basic for now but just in case it gets more complicated
    echo "</div>";
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

function image_carousel($images){
    shuffle($images);
    $final_content = '';
    foreach($images as $img){
        $content = srcset("https://www.bethel.edu/$img", $print=false,$lazy=true);
        $final_content = $final_content . carousel_item($content, '','',false);
    }
    if( sizeof($images) > 0 ) {
        echo '<div class="site__image-bank">';
        carousel_create('flickity  carousel--image-bank', $final_content);
        echo '</div>';
    }
}


function srcset($end_path, $print=true, $lazy=true, $classes=''){
    if( strpos($end_path,"www.bethel.edu") == false ) {
        $end_path = "https://www.bethel.edu/$end_path";
    }

    $twig = makeTwigEnviron('/code/general-cascade/twig');
    $content = $twig->render('srcset.html', array(
        'end_path' => $end_path,
        'lazy' => $lazy,
        'classes'   => $classes)
    );

    if($print){
        echo $content;
    }else{
        return $content;
    }

}


function thumborURL($end_path, $width, $lazy=true, $print=true){

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
        'content' => $content
    ));

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
        'content' => $content
    ));
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
        'url' => $url
    ));
}


function semCheckInPath($url, $name){

    if ($name != "Programs") {
        echo checkInPath($url, $name);
    } else {
        // if the name is Programs and its a faculty URL, skip it.
        $path = $_SERVER['REQUEST_URI'];
        $pos = 0 === strpos($path, $url . 'faculty');
        if (!$pos) {
            echo checkInPath($url, $name);
        }else{
            $twig = makeTwigEnviron('/code/general-cascade/twig');
            echo $twig->render('checkInPath.html', array(
                'pos' => false,
                'name' => $name,
                'url' => $url
            ));
        }
    }
}

function navListItem($pageStartsWith, $test_starts_with, $path, $label, $classes=''){

    //twig version
    $twig = makeTwigEnviron('/code/general-cascade/twig');
    echo $twig->render('navListItem.html', array(
       'pageStartsWith' => $pageStartsWith,
        'test_starts_with' => $test_starts_with,
        'path' => $path,
        'label' => $label,
        'classes' => $classes
    ));
}

function makeTwigEnviron($path){

    $loader = new Twig_Loader_Filesystem($_SERVER["DOCUMENT_ROOT"] . $path);
    $twig = new Twig_Environment($loader);
    return $twig;

}

function autoCache($func, $inputs=array(), $cache_name = null, $cache_time = 300)
{

    if (!$cache_name) {
        // If there is no provided cache name, generate one using the page name and line line number that called the
        // function that requires caching. $cache_name will be something like:  /var/www/staging/public/index.php780.
        // use end() to get the original call on the stack trace, which should be the webpage on the server
        $bt = debug_backtrace();
        $origin_function = end($bt);
        $cache_name = $origin_function['file'] . $origin_function['line'];
    }
    $cache_name = md5($cache_name);

    //checks if cache_name is being used. if so it retrieves it's data otherwise it creates a new key using cache_name
    $cache = new Memcache;
    $cache->connect('localhost', 11211);
    $data = $cache->get($cache_name);
    if (!$data) {
        error_log("Full Data Array Memcache miss", 3, '/tmp/memcache.log');
        $data = call_user_func_array($func, $inputs);
        try {
            $cache->set($cache_name, $data, MEMCACHE_COMPRESSED, $cache_time);
        } catch (Exception $e) {
            error_log("\nError - " . $e->getMessage(), 3, '/tmp/memcache.log');
        }
    }

    return $data;
}


?>
