<?php
/**
 * Created by PhpStorm.
 * User: ejc84332
 * Date: 10/9/14
 * Time: 12:58 PM
 */

function get_total_gifts(){
    $gifts = json_decode(file_get_contents("http://wsapi.bethel.edu/roar/total-gifts"));
    //print_r($gifts);
    $total = $gifts->{'result'}[0][0];
    $total = number_format($total);  // implode array with comma
    return "$$total";
}


echo autoCache('get_total_gifts', array(), $cache_name='get_total_gifts', $cache_time=60);
