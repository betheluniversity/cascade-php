<?php

$staging = strstr(getcwd(), "staging/public");
$soda = strstr(getcwd(), "soda");

if( $staging ){

    if ($cms_url){
        $testing = "<a href='$cms_url' style='color: white; text-decoration: underline;'>TESTING</a>";
    }else{
        $testing = "TESTING";
    }

    echo "<div style='text-align:center; background:tomato;color:#fff;font-weight:500;padding:.7em;'>This page is a $testing version.</div>";
}

if ($cms_url) {
    echo '<div id="cms_url" style="display:none">';
    echo $cms_url;
    echo '</div>';
}

if ($require_auth == "Yes" || $check_auth == "Yes"){
    header("Cache-Control: no-cache");
    include_once 'cas.php';
}else{
    header("Cache-Control: public, must-revalidate, max-age=86400");
}

function srcset($end_path){
    $rand = rand(1,4);
    echo "<img srcset='
                //cdn$rand.bethel.edu/resize/unsafe/1400x0/smart/$end_path 1400w,
                //cdn$rand.bethel.edu/resize/unsafe/1200x0/smart/$end_path 1200w,
                //cdn$rand.bethel.edu/resize/unsafe/1000x0/smart/$end_path 1000w,
                //cdn$rand.bethel.edu/resize/unsafe/800x0/smart/$end_path 800w,
                //cdn$rand.bethel.edu/resize/unsafe/600x0/smart/$end_path 600w,
                //cdn$rand.bethel.edu/resize/unsafe/400x0/smart/$end_path 400w,
                //cdn$rand.bethel.edu/resize/unsafe/200x0/smart/$end_path 200w'
                src='//cdn$rand.bethel.edu/resize/unsafe/320x0/smart/$end_path'></img>";

}