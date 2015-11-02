<?php
/**
 * Created by PhpStorm.
 * User: ejc84332
 * Date: 10/9/14
 * Time: 1:00 PM
 */


// todo: rename everything to total_gifts_given.

function get_last_gift(){
$gifts = json_decode(file_get_contents("http://wsapi.bethel.edu/roar/total_gifts_given"));
$last = $gifts->{'result'}[0][0];
return $last;
}


echo autoCache('get_last_gift', array(), $cache_name='get_last_gift', $cache_time=60);
