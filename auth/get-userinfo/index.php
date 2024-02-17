<?php
require_once $_SERVER["DOCUMENT_ROOT"]."/_config.php";

$auth = auth();

if($auth === false) {
    $response = array(
        "header" => array(
            "result" => "fail",
            "message" => "인증 토큰이 올바르지 않습니다."
        ),
        "body" => array()
    );
    goto response_handling;
} else {
    $response = array(
        "header" => array(
            "result" => "success",
            "message" => "정상 처리되었습니다."
        ),
        "body" => array(
            "data" => $auth
        )
    );
    goto response_handling;
}

response_handling:
response_header_init();
echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);