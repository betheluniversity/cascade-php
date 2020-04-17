<?php

$global_alert_xml = $_SERVER["DOCUMENT_ROOT"] . "/_shared-content/xml/global-alerts.xml";
$xml = simplexml_load_file($global_alert_xml);

foreach ($xml->xpath("//system-block") as $block) {
    $active = $block->active;
    $text = $block->text;
    $add_link = $block->add_link;
    $link_text = $block->link_text;
    $link_path = "https://www.bethel.edu" . $block->link_path;

    if( $active == 'Yes' ){
        // twig load here!
        $twig = makeTwigEnviron('/code/general-cascade/twig');
        $html = $twig->render('global-alerts.html', array(
            'text' => $text,
            'add_link' => $add_link,
            'link_text' => $link_text,
            'link_path' => $link_path,
        ));

        echo $html;
        break;
    }
}

