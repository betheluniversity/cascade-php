<?php
/**
 * Created by PhpStorm.
 * User: ejc84332
 * Date: 10/9/14
 * Time: 12:58 PM
 */

    $gifts = json_decode(file_get_contents("https://wsapi.bethel.edu/roar/total-gifts"));
    //print_r($gifts);
    $total = $gifts->{'result'}[0][0];
    $total = number_format($total);  // implode array with comma
    echo "$$total";