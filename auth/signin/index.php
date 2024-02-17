<?php
require_once $_SERVER["DOCUMENT_ROOT"]."/_config.php";

if(
    !isset($_POST["id"]) || empty($_POST["id"]) ||
    !isset($_POST["pwd"]) || empty($_POST["pwd"])
) {
    $response = array(
        "header" => array(
            "result" => "fail",
            "message" => "아이디 또는 비밀번호가 올바르지 않습니다."
        ),
        "body" => array()
    );
    goto response_handling;
}

try {
    $pdo = new PDO(
        "mysql:host="._db_address.";dbname="._db_name.";charset=utf8",
        _db_id,_db_pwd,
        array(
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_EMULATE_PREPARES => false
        )
    );

    $user_id = strtolower($_POST["id"]);
    $stmt = $pdo->prepare("SELECT * FROM `accounts` WHERE `id` = :id");
    $stmt->bindValue(':id', $user_id);
    $stmt->execute();
    $account = $stmt->fetch(PDO::FETCH_ASSOC);

    if($account !== false && password_verify($_POST["pwd"], $account['pwd'])) {
        $stmt = $pdo->prepare("UPDATE `login_sessions` SET `is_available` = 0 WHERE `account_idx` = :account_idx");
        $stmt->bindValue(':account_idx', $account["idx"]);
        $stmt->execute();

        // Create login session token (128)
        $fp = fopen('/dev/urandom', 'rb');
        $token = bin2hex(fread($fp, 64));
        fclose($fp);
        
        $now = date("Y-m-d H:i:s");
        $expired_at = date("Y-m-d H:i:s", strtotime("+ 24 hours"));
        $stmt = $pdo->prepare("INSERT INTO `login_sessions`(`account_idx`, `token`, `created_at`, `expired_at`, `is_available`) VALUES (:account_idx, :token, :created_at, :expired_at, 1)");
        $stmt->bindValue(':account_idx', $account["idx"]);
        $stmt->bindValue(':token', $token);
        $stmt->bindValue(':created_at', $now);
        $stmt->bindValue(':expired_at', $expired_at);
        $session = $stmt->execute();

        if($session) {
            $response = array(
                "header" => array(
                    "result" => "success",
                    "message" => "로그인이 완료되었습니다."
                ),
                "body" => array(
                    "token" => $token
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
    } else {
        $response = array(
            "header" => array(
                "result" => "fail",
                "message" => "아이디 또는 비밀번호가 올바르지 않습니다."
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