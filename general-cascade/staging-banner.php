<?php
/**
 * Created by PhpStorm.
 * User: jot43536
 * Date: 6/19/15
 * Time: 2:19 PM
 */

$twig = makeTwigEnviron('/code/general-cascade/twig');

echo $twig->render('staging-banner.html', array(
    'staging' => $staging,
    'cms_url' => $cms_url,
    'page_path' => $_SERVER['REQUEST_URI']
));
