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
    $call_program_code = str_replace("%", "%25", $program_code);
    $url = "http://wsapi.bethel.edu/capsgs-checklist/$call_program_code/$select_option";
    echo "<!-- $url -->";
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


