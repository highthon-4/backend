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

if(
    !isset($_GET["session"]) || empty($_GET["session"])
) {
    $response = array(
        "header" => array(
            "result" => "fail",
            "message" => "필수 파라미터가 누락되었습니다."
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

    $stmt = $pdo->prepare("SELECT type FROM `chat_rooms` WHERE `session` = :session && `account_idx` = :account_idx");
    $stmt->bindValue(':session', $_GET["session"]);
    $stmt->bindValue(':account_idx', $auth["idx"]);
    $stmt->execute();
    $counselor_type = $stmt->fetchColumn();

    if($counselor_type === false) {
        $response = array(
            "header" => array(
                "result" => "fail",
                "message" => "알 수 없는 세션입니다."
            ),
            "body" => array()
        );
        goto response_handling;
    }

    $stmt = $pdo->prepare("SELECT `request`, `response`, `created_at` FROM `chat_history` WHERE `session` = :session ORDER BY created_at ASC");
    $stmt->bindValue(':session', $_GET["session"]);
    $stmt->execute();
    $chat_history = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $response = array(
        "header" => array(
            "result" => "success",
            "message" => "정상적으로 처리되었습니다."
        ),
        "body" => array(
            "type" => $counselor_type,
            "history" => $chat_history
        )
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