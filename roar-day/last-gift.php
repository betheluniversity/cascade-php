<?php
/**
 * Created by PhpStorm.
 * User: ejc84332
 * Date: 10/9/14
 * Time: 1:00 PM
 */


 $gifts = json_decode(file_get_contents("https://wsapi.bethel.edu/roar/total_gifts_given"));
 $last = $gifts->{'result'}[0][0];
 echo $last;
