<?php
/**
 * Created by PhpStorm.
 * User: ces55739
 * Date: 8/1/14
 * Time: 2:59 PM
 */

/*
 * This file will be used to 'import' Cascade functions into PHP.
 *
 * It currently is an exact copy of various Cascade functions.
 *
 * To do: Down the road, Cascade could import this php helper file. Then
 * pass over the information for PHP to render.
 *
 */

    function render_image( $imgPath, $imgDesc, $imgClass, $width, $siteDestinationName)
    {
        $rand1_4 = rand(1, 4);
        $path = 'https://cdn' . $rand1_4 . '.bethel.edu/resize/unsafe/{width}x0/smart/http://' . $siteDestinationName . '.bethel.edu' . $imgPath;
        return '<div class="'.$imgClass.'" data-src="'.$path.'" data-alt="'.$imgDesc.'" width="'.$width.'"></div>';
    }



?>