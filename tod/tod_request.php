<?php
/**
 * Created by PhpStorm.
 * User: ejc84332
 * Date: 6/29/15
 * Time: 2:01 PM
 */

$staging = strstr(getcwd(), "staging/");
$soda = strstr(getcwd(), "soda");
$require_auth = "Yes";

include_once $_SERVER["DOCUMENT_ROOT"] . "/code/general-cascade/cas.php";
header("Location: http://wsapi.bethel.edu/tod/");
