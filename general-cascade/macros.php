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
        if( is_array($img) ){
            $img_path = $img[0];
            $alt = $img[1];
        } else {
            $img_path = $img;
            $alt = '';
        }

        $content = srcset("$img_path", $print=false,$lazy=true, $classes='', $alt_text=$alt);
        $final_content = $final_content . carousel_item($content, '','',false);
    }
    if( sizeof($images) > 0 ) {
        echo '<div class="site__image-bank">';
        carousel_create('flickity  carousel--image-bank', $final_content);
        echo '</div>';
    }
}


function srcset($end_path, $print=true, $lazy=true, $classes='', $alt_text=''){
    // todo: in the move to imgix, we don't want this anymore
//    if( strpos($end_path,"www.bethel.edu") == false ) {
//        $end_path = "https://www.bethel.edu/$end_path";
//    }

    $twig = makeTwigEnviron('/code/general-cascade/twig');
    $content = $twig->render('srcset.html', array(
        'end_path'  => $end_path,
        'lazy'      => $lazy,
        'classes'   => $classes,
        'alt_text'  => $alt_text)
    );

    if($print){
        echo $content;
    }else{
        return $content;
    }

}


function thumborURL($end_path, $width, $lazy=true, $print=true, $alt_text=''){

    // todo: this is gross, but its a way to clean up the end_path
    $end_path = str_replace('http://', '', $end_path);
    $end_path = str_replace('https://', '', $end_path);
    $end_path = str_replace('www.bethel.edu', '', $end_path);
    $end_path = str_replace('staging.bethel.edu/', '', $end_path);
    $end_path = str_replace('staging.xp.bethel.edu/', '', $end_path);

    $twig = makeTwigEnviron('/code/general-cascade/twig');
    $html = $twig->render('thumborURL.html', array(
        'end_path'  => $end_path,
        'width'     => $width,
        'lazy'      => $lazy,
        'alt_text'  => $alt_text
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

function autoCache($func, $inputs=array(), $cache_time=300, $blerts="No"){
    if( !is_int($cache_time) )
        $cache_time = 300;

    // build a cache string from the stack trace.
    $bt = debug_backtrace();
    $cache_name = "";

    // if URL uses a query string (e.g. Calendar, Program Search) to specify content, add the query string params to the cache name
    if(isset($_SERVER["QUERY_STRING"])){
        $array_of_query_strings = array();
        // $array_of_query_strings becomes a key=>value array, based on the QUERY_STRING
        parse_str($_SERVER["QUERY_STRING"], $array_of_query_strings);
        $keys_we_care_about = [
            'year', # calendar
            'month', # calendar
            'day', # calendar
            'search', # Program Search
            'degree', # Program Search
            'school', # Program Search
            'delivery' # Program Search
        ];

        // sort the associative array, so the keys are always in the same order
        ksort($array_of_query_strings);
        foreach($array_of_query_strings as $key => $value){
            // each key we want to use, we append it with the value to the cache name.
            if( in_array($key, $keys_we_care_about) ) {
                // as long as we are consistent with how they get added, it doesn't matter on the style of adding it
                $cache_name .= "$key=$value&";
            }
        }
    }

    foreach ($bt as $entry_id => $entry) {
        // append info from each layer in the stack trace to the cache name.
        // this will result in a unique cache name for each time autoCache is called
        if( array_key_exists('file', $entry))
            $cache_name .= $entry['file'];
        if( array_key_exists('function', $entry))
            $cache_name .= $entry['function'];
        if( array_key_exists('line', $entry))
            $cache_name .= $entry['line'];
    }

    $cache_name = md5($cache_name);

    //checks if cache_name is being used. if so it retrieves it's data otherwise it creates a new key using cache_name
    $cache = new Memcached;
    $cache->addServer("localhost", 11211);
    // store bethel alert cache clearing
    if( $blerts != 'No' ) {
        $bethel_alert_cache_name = 'clear_cache_bethel_alert_keys';
        $cache_keys = $cache->get($bethel_alert_cache_name);
        if( $cache_keys ) {
            // if the cache name isn't in it.
            if( strpos($cache_keys, $cache_name) === false )
                $cache->set($bethel_alert_cache_name, "$cache_keys:$cache_name", $cache_time*5);
        } else {
            // cache this for 5x the normal cache time. This will help us maintain this list for a longer period of time.
            $cache->set($bethel_alert_cache_name, $cache_name, $cache_time*5);
        }
    }

    $data = $cache->get($cache_name);
    if (!$data) {
        $msg = "\nFull Data Array Memcache miss at " . $_SERVER['REQUEST_URI'] . "\n";
        error_log($msg, 3, '/opt/php_logs/memcache.log');
        $data = call_user_func_array($func, $inputs);
        try {
            $cache->set($cache_name, $data, $cache_time);
        } catch (Exception $e) {
            $msg = "\nError - " . $e->getMessage() . " at " . $_SERVER['REQUEST_URI'] . "\n";
            error_log($msg, 3, '/opt/php_logs/memcache.log');
        }
    }
    return $data;
}


?>
