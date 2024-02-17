<?php
require_once $_SERVER["DOCUMENT_ROOT"]."/_config.php";

$response = array(
    "header" => array(
        "result" => "success",
        "message" => "Hello, world"
    ),
    "body" => array()
);

response_header_init();
echo json_encode($response);

exit();
?>