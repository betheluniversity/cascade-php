<?php
$valid_passwords = array ("mario" => "carbonell");
$valid_users = array_keys($valid_passwords);

$user = $_SERVER['PHP_AUTH_USER'];
$pass = $_SERVER['PHP_AUTH_PW'];

$validated = (in_array($user, $valid_users)) && ($pass == $valid_passwords[$user]);

if (!$validated) {
    header('WWW-Authenticate: Basic realm="My Realm"');
    header('HTTP/1.0 401 Unauthorized');
    die ("Not authorized");
}

// If arrives here, is a valid user.
echo "<p>Welcome $user.</p>";
echo "<p>Congratulation, you are into the system.</p>";


//$cache = new Memcache;
//$cache->connect('localhost', 11211);
//
//$bethel_alert_cache_name = 'clear_cache_bethel_alert_keys';
//$cache_keys = cache.get($bethel_alert_cache_name);
//if( $cache_keys ){
//    foreach($cache_keys.explode(':') as $cache_key){
//        $cache->delete($cache_key);
//    }
//}


?>

