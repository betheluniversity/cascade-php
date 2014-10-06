<?php
/**
 * Created by PhpStorm.
 * User: ejc84332
 * Date: 10/6/14
 * Time: 9:47 AM
 */


require_once 'cas_config.php';
require_once $phpcas_path . '/CAS.php';



phpCAS::setDebug();
// Initialize phpCAS
phpCAS::client(CAS_VERSION_2_0, $cas_host, $cas_port, $cas_context);

phpCAS::forceAuthentication();