<?php
/**
 * Created by PhpStorm.
 * User: ejc84332
 * Date: 10/16/14
 * Time: 11:11 AM
 */

    // Todo: calendar stopped using this. Do we still need it??

    $check_auth = "Yes";
    include_once($_SERVER["DOCUMENT_ROOT"] . '/code/general-cascade/cas.php');
    $data = array("remote_user" => $remote_user);
    echo json_encode($data);
