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
}

try {
    $pdo = new PDO(
        "mysql:host="._db_address.";dbname="._db_name.";charset=utf8mb4",
        _db_id,_db_pwd,
        array(
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_EMULATE_PREPARES => false
        )
    );

    $stmt = $pdo->prepare("UPDATE `login_sessions` SET `is_available` = 0 WHERE `account_idx` = :account_idx");
    $stmt->bindValue(':account_idx', $auth["idx"]);
    $stmt->execute();

    $response = array(
        "header" => array(
            "result" => "success",
            "message" => "로그아웃 되었습니다."
        ),
        "body" => array()
    );
    goto response_handling;

} catch (PDOException $e) {
    $response = array(
        "header" => array(
            "result" => "fail",
            "message" => "데이터베이스 오류가 발생했습니다."
        ),
        "body" => array()
    );
    goto response_handling;
}

response_handling:
response_header_init();
echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);