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

// Swiper controls are opt-in; other carousels keep the legacy template.
function carousel_create($class = "", $content, $arrows=false, $pagination=false){
    $twig = makeTwigEnviron('/code/general-cascade/twig');
    $classes = preg_split('/\s+/', trim($class));
    $is_swiper = in_array('swiper', $classes, true);
    $template = $is_swiper ? 'carousel-swiper.html' : 'carousel.html';
    echo $twig->render($template, array(
        'class' => $class,
        'content' => $content,
        'arrows' => $arrows,
        'pagination' => $pagination,
        'quote_collection' => in_array('swiper--quotes', $classes, true)
    ));
}

function build_carousel_from_array($array, $class, $arrows=false, $pagination=false){
    $content = "";
    foreach($array as $item){
        $content .= $item;
    }

    carousel_create($class, $content, $arrows, $pagination);
}

// Set $print to false to return a slide without echoing it.
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

// Read local image dimensions once per request; never fetch remote images here.
function native_image_dimensions($end_path){
    static $dimensions = array();
    $path = parse_url((string) $end_path, PHP_URL_PATH);
    $local_path = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/' . ltrim((string) $path, '/');
    if (!array_key_exists($local_path, $dimensions)) {
        $dimensions[$local_path] = is_file($local_path) ? @getimagesize($local_path) : false;
    }
    return $dimensions[$local_path];
}

/*
 * Responsive imgix image; mirrors the responsiveImage macro in Cascade's image.vm.
 * $path is site-relative; an empty path returns ''. $alt is escaped by Twig.
 *
 * Supported $options:
 * "class": CSS classes. Default: no class attribute.
 * "sizes": fallback layout description (default "100vw"). Lazy images with known
 *   dimensions automatically prepend "auto, " so the browser measures the layout.
 * "autoSizes": true (default) or false. False never adds auto; use it for
 *   object-fit: cover crops and for imgs that are themselves flex items.
 * "loading": "lazy" (default) or "eager".
 * "fetchpriority": "high" or "low"; omitted by default.
 * "width" and "height": original image dimensions; supply both. Otherwise they are
 *   read from the local file. They reserve the aspect ratio and cap the requests.
 */
function responsive_image($path, $alt = '', $options = array()){
    // Strip the leading slash so imgix URLs never contain "//", then percent-encode
    // characters that would break the URL or the srcset list. "%" goes first.
    $url_path = ltrim(trim((string) $path), '/');
    $url_path = str_replace(array('%', ' ', '&', '#', '?'), array('%25', '%20', '%26', '%23', '%3F'), $url_path);
    if ($url_path === '') {
        return '';
    }

    // Legacy src fallback only; not a CSS cap or the largest srcset candidate.
    $fallback_width = 1152;
    $class = isset($options['class']) ? trim((string) $options['class']) : '';
    $sizes = !empty($options['sizes']) ? trim((string) $options['sizes']) : '100vw';
    $auto_sizes = !(isset($options['autoSizes']) && $options['autoSizes'] === false);
    $loading = (isset($options['loading']) && $options['loading'] === 'eager') ? 'eager' : 'lazy';
    $priority = (isset($options['fetchpriority']) && in_array($options['fetchpriority'], array('high', 'low'), true))
        ? $options['fetchpriority'] : '';

    // Supplied dimensions avoid the file lookup.
    $width = 0;
    $height = 0;
    if (isset($options['width'], $options['height']) && $options['width'] > 0 && $options['height'] > 0) {
        $width = (int) $options['width'];
        $height = (int) $options['height'];
    } else {
        $dimensions = native_image_dimensions($path);
        if ($dimensions && $dimensions[0] > 0 && $dimensions[1] > 0) {
            $width = (int) $dimensions[0];
            $height = (int) $dimensions[1];
        }
    }

    // Treat caller sizes as a fallback description. Strip any leading auto, then
    // add it only for lazy images with dimensions or an explicit auto override.
    $explicit_auto = false;
    if (strtolower($sizes) === 'auto' || strpos(strtolower($sizes), 'auto,') === 0) {
        $explicit_auto = true;
        $sizes = trim(ltrim(trim(substr($sizes, 4)), ','));
    }
    if ($sizes === '') {
        $sizes = '100vw';
    }
    if ($auto_sizes && $loading === 'lazy' && (($width > 0 && $height > 0) || $explicit_auto)) {
        $sizes = 'auto, ' . $sizes;
    }

    $params = 'auto=format,compress&fit=max';

    // 3840 supports a 1920px hero at 2x; known source dimensions cap the requests.
    $widths = array();
    $srcset = array();
    foreach (array(200, 256, 320, 400, 480, 640, 800, 960, 1152, 1440, 1600, 1920, 2304, 2560, 2880, 3200, 3840) as $requested_width) {
        $candidate_width = ($width > 0) ? min($requested_width, $width) : $requested_width;
        if (!in_array($candidate_width, $widths, true)) {
            $widths[] = $candidate_width;
            $srcset[] = "https://bethel-university.imgix.net/$url_path?w=$candidate_width&$params {$candidate_width}w";
        }
    }
    $src_width = ($width > 0) ? min($fallback_width, $width) : $fallback_width;

    $twig = makeTwigEnviron('/code/general-cascade/twig');
    return $twig->render('srcset.html', array(
        'class'         => $class,
        'alt'           => (string) $alt,
        'loading'       => $loading,
        'fetchpriority' => $priority,
        'width'         => $width,
        'height'        => $height,
        'src'           => "https://bethel-university.imgix.net/$url_path?w=$src_width&$params",
        'srcset'        => implode(', ', $srcset),
        'sizes'         => $sizes
    ));
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

    // Cache entries must vary with the arguments passed to the cached
    // function. Without this, category-specific feeds invoked from the same
    // call path reuse whichever feed was cached first.
    $cache_name .= '|function=' . (string)$func;
    try {
        $serialized_inputs = serialize($inputs);
    } catch (\Throwable $error) {
        // Keep caching available for any legacy caller that passes an object
        // PHP cannot serialize. The normal event/feed inputs are scalar or
        // array values and use the stable serialized representation above.
        $serialized_inputs = print_r($inputs, true);
    }
    $cache_name .= '|inputs=' . md5($serialized_inputs);

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
