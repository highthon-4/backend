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
    !isset($_POST["message"]) || empty($_POST["message"]) ||
    !isset($_POST["session"]) || empty($_POST["session"])
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
    $stmt->bindValue(':session', $_POST["session"]);
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

    $chat_session = $_POST["session"];
    
    $stmt = $pdo->prepare("SELECT * FROM `chat_history` WHERE `session` = :session");
    $stmt->bindValue(':session', $_POST["session"]);
    $stmt->execute();
    $chat_history = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if(count($chat_history) >= 1) {
    
        $apidata = array();
        foreach($chat_history as $item) {
            array_push($apidata, array(
                "role" => "user",
                "content" => $item["request"]
            ));
            array_push($apidata, array(
                "role" => "assistant",
                "content" => $item["response"]
            ));
        }
        
        array_push($apidata, array(
            "role" => "user",
            "content" => $_POST["message"]
        ));

        $apidata = json_encode($apidata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $apidata = preg_replace("/\"},{\"role\":\"/", "\"},\n{\"role\":\"", $apidata);
        $apidata = json_encode( array("request" => $apidata), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        // echo $apidata;
        // exit();

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "http://15.164.38.42:8080/continue");
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $apidata);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/json"));
        $response = curl_exec ($ch);
        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
    } else {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "http://15.164.38.42:8080/".$counselor_type);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode( array("request" => $_POST["message"]) ));           
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/json"));
        $response = curl_exec ($ch);
        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
    }
    
    if($statusCode == "200") {
        $response = json_decode($response, true);
        
        $now = date("Y-m-d H:i:s");
        $stmt = $pdo->prepare("INSERT INTO `chat_history`(`account_idx`, `session`, `type`, `request`, `response`, `created_at`) VALUES (:account_idx, :session, :type, :request, :response, :created_at)");
        $stmt->bindValue(':account_idx', $auth["idx"]);
        $stmt->bindValue(':session', $chat_session);
        $stmt->bindValue(':type', $counselor_type);
        $stmt->bindValue(':request', $_POST["message"]);
        $stmt->bindValue(':response', $response["data"]);
        $stmt->bindValue(':created_at', $now);
        $result = $stmt->execute();
    
        if($result) {
            $response = array(
                "header" => array(
                    "result" => "success",
                    "message" => "처리가 완료되었습니다."
                ),
                "body" => array(
                    "message" => $response["data"]
                )
            );
            goto response_handling;
        } else {
            $response = array(
                "header" => array(
                    "result" => "fail",
                    "message" => "데이터베이스 오류가 발생했습니다."
                ),
                "body" => array(
                    "message" => $response["data"]
                )
            );
            goto response_handling;
        }
    } else {
        $response = array(
            "header" => array(
                "result" => "fail",
                "message" => "잠시 후에 다시 시도해주세요."
            ),
            "body" => array(
                "response" => $response,
                "statusCode" => $statusCode
            )
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