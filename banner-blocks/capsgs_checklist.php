<?php
/**
 * Created by PhpStorm.
 * User: ejc84332
 * Date: 11/5/14
 * Time: 3:29 PM
 */


if($select_option == 4){
    return;
}else{
    $url = "http://wsapi.bethel.edu/capsgs-checklist/$program_code/$select_option";
    $results = json_decode(file_get_contents($url));
    $results = explode("\n", $results->data);
    if($results[0]){
        echo "<ul>";
        foreach($results as $result){
            echo "<li>$result</li>";
        }
        echo "</ul>";
    }
}


