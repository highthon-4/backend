<?php
require_once $_SERVER["DOCUMENT_ROOT"]."/_config.php";

if( !isset($_POST["id"]) || !preg_match("/^[a-z0-9_]{4,}$/", $_POST["id"]) ) {
    $response = array(
        "header" => array(
            "result" => "fail",
            "message" => "아이디가 올바르지 않습니다."
        ),
        "body" => array()
    );
    goto response_handling;
}

if( !isset($_POST["pwd"]) || !preg_match("/^[a-zA-z0-9!@#$%^&*?_]{8,}$/", $_POST["pwd"]) ) {
    $response = array(
        "header" => array(
            "result" => "fail",
            "message" => "비밀번호가 올바르지 않습니다."
        ),
        "body" => array()
    );
    goto response_handling;
}

if( !isset($_POST["repwd"]) || $_POST["pwd"] != $_POST["repwd"] ) {
    $response = array(
        "header" => array(
            "result" => "fail",
            "message" => "비밀번호 재입력이 일치하지 않습니다."
        ),
        "body" => array()
    );
    goto response_handling;
}

if( !isset($_POST["username"]) || empty($_POST["username"]) ) {
    $response = array(
        "header" => array(
            "result" => "fail",
            "message" => "사용자 이름이 올바르지 않습니다."
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

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM `accounts` WHERE `id` = :id");
    $stmt->bindValue(':id', $_POST["id"]);
    $stmt->execute();
    $already_existing_accounts_cnt = $stmt->fetchColumn();

    if($already_existing_accounts_cnt > 0) {
        $response = array(
            "header" => array(
                "result" => "fail",
                "message" => "이미 존재하는 아이디입니다."
            ),
            "body" => array()
        );
        goto response_handling;
    }

    $hashed_pwd = password_hash($_POST["pwd"], PASSWORD_DEFAULT);
    $now = date("Y-m-d H:i:s");
    $stmt = $pdo->prepare("INSERT INTO `accounts`(`id`, `pwd`, `username`, `created_at`) VALUES (:id, :pwd, :username, :created_at)");
    $stmt->bindValue(':id', $_POST["id"]);
    $stmt->bindValue(':pwd', $hashed_pwd);
    $stmt->bindValue(':username', $_POST["username"]);
    $stmt->bindValue(':created_at', $now);
    $result = $stmt->execute();

    if($result) {
        $response = array(
            "header" => array(
                "result" => "success",
                "message" => "회원가입이 완료되었습니다."
            ),
            "body" => array()
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