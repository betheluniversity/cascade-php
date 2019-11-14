<?php
include_once $_SERVER["DOCUMENT_ROOT"] . "/code/config.php";

$user = $_SERVER['PHP_AUTH_USER'];
$pass = $_SERVER['PHP_AUTH_PW'];

$validated = ($user == $config['CACHE_CLEAR_USER']) && ($pass == $config['CACHE_CLEAR_PASS']);

if (!$validated) {
    header('WWW-Authenticate: Basic realm="My Realm"');
    header('HTTP/1.0 401 Unauthorized');
    die ("Not authorized");
}

$cache = new Memcache;
$cache->connect('localhost', 11211);

$bethel_alert_cache_name = 'clear_cache_bethel_alert_keys';

$cache_keys = $cache->get($bethel_alert_cache_name);
echo "Cache Keys: $cache_keys";
$cache->set($bethel_alert_cache_name,MEMCACHE_COMPRESSED, 300);
$cache_keys = $cache->get($bethel_alert_cache_name);
echo "Cache Keys: $cache_keys";


//if( $cache_keys ){
//    foreach($cache_keys.explode(':') as $cache_key){
//        echo "Cache Key: $cache_key";
//        $cache->delete($cache_key);
//    }
//    $cache->delete($bethel_alert_cache_name);
//}
//echo 'Cleared Cache!';


?>
