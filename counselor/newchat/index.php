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
    !isset($_GET["type"]) || empty($_GET["type"])
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

$fp = fopen('/dev/urandom', 'rb');
$chat_session = bin2hex(fread($fp, 16));
fclose($fp);

if(isset($_GET["type"]) && $_GET["type"] == "ideal") $counselor_type = "ideal";
elseif(isset($_GET["type"]) && $_GET["type"] == "realistic") $counselor_type = "realistic";
elseif(isset($_GET["type"]) && $_GET["type"] == "sensuous") $counselor_type = "sensuous";
else {
    $response = array(
        "header" => array(
            "result" => "fail",
            "message" => "타입이 올바르지 않습니다."
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

    $stmt = $pdo->prepare("INSERT INTO `chat_rooms`(`account_idx`, `session`, `type`) VALUES (:account_idx, :session, :type)");
    $stmt->bindValue(':account_idx', $auth["idx"]);
    $stmt->bindValue(':session', $chat_session);
    $stmt->bindValue(':type', $counselor_type);
    $result = $stmt->execute();
    
    if($result) {
        $response = array(
            "header" => array(
                "result" => "success",
                "message" => "처리가 완료되었습니다."
            ),
            "body" => array(
                "session" => $chat_session
            )
        );
        goto response_handling;
    } else {
        $response = array(
            "header" => array(
                "result" => "fail",
                "message" => "데이터베이스 오류가 발생했습니다."
            ),
            "body" => array()
        );
        goto response_handling;
    }

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