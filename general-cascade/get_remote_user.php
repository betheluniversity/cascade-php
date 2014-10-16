<?php
/**
 * Created by PhpStorm.
 * User: ejc84332
 * Date: 10/16/14
 * Time: 11:11 AM
 */

    $check_auth = "Yes";
    include_once('cas.php');
    $data = array("remote_user" => $remote_user);
    echo json_encode($data);