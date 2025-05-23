<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
$staging = strstr(getcwd(), "/staging");
$soda = strstr(getcwd(), "soda");

if ( strpos($require_auth,"Yes") !== false || $check_auth == "Yes"){
    header("Cache-Control: no-cache, must-revalidate");
    header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
    include_once $_SERVER["DOCUMENT_ROOT"] . "/code/wufoo/embed_preload.php";
    $redirect_url = $canonical_url;
    if ( $auth_type == "Microsoft" ) {
        include_once 'auth.php';
    } else {
        include_once 'auth-cas.php';
    }
}else{
    header("Cache-Control: public, must-revalidate, max-age=86400");
}

include_once $_SERVER["DOCUMENT_ROOT"] . "/code/config.php";

include_once $_SERVER["DOCUMENT_ROOT"] . "/code/general-cascade/macros.php";
require $_SERVER["DOCUMENT_ROOT"] . '/code/vendor/autoload.php';

$client = Sentry\init(['dsn' => $config['RAVEN_URL']]);
Sentry\configureScope(function (Sentry\State\Scope $scope): void {
    $scope->setLevel(Sentry\Severity::error());
});

$twig = makeTwigEnviron('/code/general-cascade/twig');

$prefix = "https://www.bethel.edu";
if($staging){
    $prefix = "https://staging.bethel.edu";
}
$url = $prefix . $_SERVER['REQUEST_URI'];
if( $canonical_url) {
    // from velocity, we can't pass "--", as it is rendered weird. Therefore, we do a replace with "XXXXX"
    $canonical_url = str_replace('XXXXX', '--', $canonical_url);

    // add a fix to handle https://www.bethel.edu as the starting string, so we can do absolute and relative urls
    $canonical_url = str_replace("https://www.bethel.edu","",$canonical_url);

    // make sure that its a relative url with a leading slash
    if ($canonical_url[0] != '/')
        $canonical_url = "/$canonical_url";

    // generate the absolute url
    $canonical_url = "https://www.bethel.edu$canonical_url";
} else {
    $canonical_url = $url;
}

// replace /index with /, if it is at the end of the $canonical_url
$canonical_url = preg_replace('/\/index$/', '/', $canonical_url);

echo "<link rel='canonical' href='$canonical_url'/>";

if( in_array('HTTP_REFERER', $_SERVER) ) {
    $referer = $_SERVER['HTTP_REFERER'];
    $parsed = parse_url($referer);
    if( in_array('host', $parsed) ){
        $host = $parsed['host'];
        if (!stristr($host, "bethel.edu") && $referer != null){
            // update the interesting referer in session
            $_SESSION['interesting_referer'] = $referer;
        }
        if( in_array('interesting_referer', $_SESSION))
            echo "<!-- " . $_SESSION['interesting_referer'] . " -->";
    }

    // Set cookie for google/yahoo/bing searches. Check these before proper utm_ get params so the ad
    // data doesn't get overwritten.
    $url = $_SERVER['HTTP_REFERER'];
    $query = parse_url($url, PHP_URL_QUERY);
    $host = parse_url($url, PHP_URL_HOST);
    $expire_year = time() + 31536000;
    $expire_month = time() + 2628000;
    // should we check for UTM here instead of q=?
    if( strstr($host, 'google.')) {
        setcookie('utm_content', '', -1, "/", ".bethel.edu");
        setcookie('utm_campaign', '', -1, "/", ".bethel.edu");
        setcookie('utm_source', 'google', $expire_month, "/", ".bethel.edu");
        setcookie('utm_medium', 'organic', $expire_month, "/", ".bethel.edu");
    }
    elseif( strstr($host, 'yahoo.')) {
        setcookie('utm_content', '', -1, "/", ".bethel.edu");
        setcookie('utm_campaign', '', -1, "/", ".bethel.edu");
        setcookie('utm_source', 'yahoo', $expire_month, "/", ".bethel.edu");
        setcookie('utm_medium', 'organic', $expire_month, "/", ".bethel.edu");
    }
    elseif( strstr($host, 'bing.')) {
        setcookie('utm_content', '', -1, "/", ".bethel.edu");
        setcookie('utm_campaign', '', -1, "/", ".bethel.edu");
        setcookie('utm_source', 'bing', $expire_month, "/", ".bethel.edu");
        setcookie('utm_medium', 'organic', $expire_month, "/", ".bethel.edu");
    }
}

$expire_year = time() + 31536000;
// testing ads:
// https://www.bethel.edu/graduate/academics/mba/?utm_source=adroll&utm_medium=retargeting&utm_content=mba&utm_campaign=f18_bethel_capsgs_haworth
foreach( $_GET as $key => $value){
    // if the GET key matches utm_, then add it to the session.
    if( strpos($key, 'utm_') !== false  ){
        if( is_array($value) ) {
            $value = implode("|", $value);

            // if we still have an array, skip it. This should only occur when bots try to send crap at our site.
            if( is_array($value) ){
                continue;
            }
        }
        # Please remove any spaces, carriage returns, and non-printing characters, as well as any equal sign, comma, or semi-colon.
        $key = str_replace(array("\r", "\n", ' ', '=', ',', ';'), '',$key);
        setcookie($key, $value, $expire_year, "/", ".bethel.edu");
    }
}
