<?php
/**
 * Created by PhpStorm.
 * User: cav28255
 * Date: 4/14/15
 * Time: 10:33 AM
 */
include_once $_SERVER["DOCUMENT_ROOT"] . "/code/general-cascade/macros.php";
//
//$cache = new Memcache;
//$cache->addServer('localhost', 11211);
//echo autoCache("testarino", array("caleb", "yo"), "caleb");


function testarino($name, $hello){
    return "$name       $hello";
}


