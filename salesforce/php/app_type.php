<?php

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: https://bethel.fluidreview.com', false);
$string = file_get_contents("apptype.json");
echo $string;
?>



