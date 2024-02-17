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

    $stmt = $pdo->prepare("SELECT * FROM `chat_rooms` WHERE `account_idx` = :account_idx");
    $stmt->bindValue(':account_idx', $auth["idx"]);
    $stmt->execute();
    $chat_rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $response = array(
        "header" => array(
            "result" => "success",
            "message" => "정상적으로 처리되었습니다."
        ),
        "body" => array(
            "sessions" => array()
        )
    );

    foreach($chat_rooms as $chat_room) {
        $stmt = $pdo->prepare("SELECT * FROM `chat_history` WHERE `session` = :session1 AND created_at = (SELECT MAX(created_at) as created_at FROM `chat_history` WHERE `session` = :session2)");
        $stmt->bindValue(':session1', $chat_room["session"]);
        $stmt->bindValue(':session2', $chat_room["session"]);
        $stmt->execute();
        $session = $stmt->fetch(PDO::FETCH_ASSOC);

        if($session === false) {
            $session["request"] = null;
            $session["response"] = null;
            $session["created_at"] = null;
        }
        
        array_push($response["body"]["sessions"], array(
            "session" => $chat_room["session"],
            "type" => $chat_room["type"],
            "request" => $session["request"],
            "response" => $session["response"],
            "last_chatted" => $session["created_at"]
        ));
    }
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