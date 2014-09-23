<?php
/**
 * Created by PhpStorm.
 * User: ejc84332
 * Date: 8/28/14
 * Time: 2:39 PM
 */

    require_once 'news_helper.php';
    $xml = get_news_xml();
    $news = "";
    //$pages = array_slice($xml, 0, 4);

?>

<html>
<body>
<div class="silva-channel">
    <div class="channel-section">

        <div class="uportal-cms-block" id="">

<?php
    $count = 0;
    $resp = "";
    foreach($xml as $page){
        if( $count == 4){
            break;
        }

        $image = "http://www.bethel.edu" . $page['image'][0];
        $title = $page['title'][0];
        $teaser = $page['teaser'][0];
        $link = "http://www.bethel.edu" . $page['path'][0];

        $resp .= '<div class="media-box pb1">';
        $resp .= "<a href='$link'>";
        $resp .= "<img class='media-box-img'
                src='$image' alt='$title' title=$title/>";
        $resp .= '</a>';
        $resp .= '<div class="media-box-body">';
        $resp .= '<h2 class="h5">';
        $resp .= "<a href='$link'>$title</a></h2>";
        $resp .= "<p>$teaser</p>";
        $resp .= '</div>';
        $resp .= '</div>';
        $count++;
    }

    echo $resp;

//function get_news_xml(){
//    $xml = simplexml_load_file($_SERVER["DOCUMENT_ROOT"] . '_shared-content/xml/articles.xml');
//    // for each page, add an item to an array with relevant info. For now, title, image url, publish date, and teaser.
//
//    $x = 1;
//
//}