<?php
/**
 * Created by PhpStorm.
 * User: ejc84332
 * Date: 10/9/14
 * Time: 12:46 PM
 */

    $gifts = json_decode(file_get_contents("http://wsapi.bethel.edu/roar/total-num-gifts"));
    //print_r($gifts);
    echo '<ol>';
    foreach($gifts->{'result'} as $result){
        echo "<li>$result[1], $result[2] gifts</li>";
    }
    echo '</ol>';
