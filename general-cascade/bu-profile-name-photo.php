<?php
function get_name_and_photo($userName) {
    // Example data, replace with actual logic to fetch data
    //$data = array(
    //    "imageURL" => "https://example.com/image.jpg",
    //    "fullName" => $userName
    //);
    //echo json_encode($data);
    return $userName;
}

// Check if userName parameter is set in the query string
if (isset($_GET['userName'])) {
    $userName = $_GET['userName'];
    get_name_and_photo($userName);
} else {
    echo json_encode(array("error" => "userName parameter is missing"));
}
?>