<?php
session_start();
$staging = strstr(getcwd(), "staging/public");
$soda = strstr(getcwd(), "soda");

if ($require_auth == "Yes" || $check_auth == "Yes"){
    header("Cache-Control: no-cache, must-revalidate");
    header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
    include_once 'cas.php';
}else{
    header("Cache-Control: public, must-revalidate, max-age=86400");
}

include_once $_SERVER["DOCUMENT_ROOT"] . "/code/config.php";

include_once $_SERVER["DOCUMENT_ROOT"] . "/code/general-cascade/macros.php";
require $_SERVER["DOCUMENT_ROOT"] . '/code/vendor/autoload.php';

$client = new Raven_Client($config['RAVEN_URL']);
$error_handler = new Raven_ErrorHandler($client);
$error_handler->registerExceptionHandler();
$error_handler->registerErrorHandler();
$error_handler->registerShutdownFunction();


$twig = makeTwigEnviron('/code/general-cascade/twig');

$prefix = "https://www.bethel.edu";
if($staging){
    $prefix = "https://staging.bethel.edu";
}
$url = $prefix . $_SERVER['REQUEST_URI'];
if( $canonical_url) {
    $canonical_url = str_replace('XXXXX', '--', $canonical_url);
    if ($canonical_url[0] != '/')
        $canonical_url = "/$canonical_url";
    $canonical_url = "https://www.bethel.edu$canonical_url";
} else {
    $canonical_url = $url;
}
echo "<link rel='canonical' href='$canonical_url'/>";

$referer = $_SERVER['HTTP_REFERER'];
$parsed = parse_url($referer);
$host = $parsed['host'];
if (!stristr($host, "bethel.edu") && $referer != null){
    // update the interesting referer in session
    $_SESSION['interesting_referer'] = $referer;
}
echo "<!-- " . $_SESSION['interesting_referer'] . " -->";


// create cookies from utm_ parameters. expire in a year
$expire = time() + 31536000;

// Set cookie for google/yahoo/bing searches. Check these before proper utm_ get params so the ad
// data doesn't get overwritten.
$url = $_SERVER['HTTP_REFERER'];
$query = parse_url($url, PHP_URL_QUERY);
$host = parse_url($url, PHP_URL_HOST);
// should we check for UTM here instead of q=?
if( !strstr($query,'q=') ){
    $query_values = get_search_query();
    if( strstr($host, 'google.')) {
        setcookie('utm_content', $query_values, -1, "/", ".bethel.edu");
        setcookie('utm_campaign', '', -1, "/", ".bethel.edu");
        setcookie('utm_source', 'google', $expire, "/", ".bethel.edu");
        setcookie('utm_medium', 'organic', $expire, "/", ".bethel.edu");
    }
    elseif( strstr($host, 'yahoo.')) {
        setcookie('utm_content', '', -1, "/", ".bethel.edu");
        setcookie('utm_campaign', '', -1, "/", ".bethel.edu");
        setcookie('utm_source', 'yahoo', $expire, "/", ".bethel.edu");
        setcookie('utm_medium', 'organic', $expire, "/", ".bethel.edu");
    }
    elseif( strstr($host, 'bing.')) {
        setcookie('utm_content', '', -1, "/", ".bethel.edu");
        setcookie('utm_campaign', '', -1, "/", ".bethel.edu");
        setcookie('utm_source', 'bing', $expire, "/", ".bethel.edu");
        setcookie('utm_medium', 'organic', $expire, "/", ".bethel.edu");
    }
}

function get_search_query()
{
    $ref_keywords = '';

    // Get the referrer to the page
    $referrer = $_SERVER['HTTP_REFERER'];
    if (!empty($referrer))
    {
        //Parse the referrer URL
        $parts_url = parse_url($referrer);

        // Check if a query string exists
        $query = isset($parts_url['query']) ? $parts_url['query'] : '';
        if($query)
        {
            // Convert the query string into array
            parse_str($query, $parts_query);
            // Check if the parameters 'q' or 'query' exists, and if exists that is our search query terms.
            $ref_keywords = isset($parts_query['q']) ? $parts_query['q'] : (isset($parts_query['query']) ? $parts_query['query'] : '' );
        }
    }
    return $ref_keywords;
}

// testing ads:
// https://www.bethel.edu/graduate/academics/mba/?utm_source=adroll&utm_medium=retargeting&utm_content=mba&utm_campaign=f18_bethel_capsgs_haworth
foreach( $_GET as $key => $value){
    // if the GET key matches utm_, then add it to the session.
    if( strpos($key, 'utm_') == 0  ){
        setcookie($key, $value, $expire, "/", ".bethel.edu");
    }
}
