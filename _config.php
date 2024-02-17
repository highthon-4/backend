<?php
error_reporting( E_ALL );
ini_set( "display_errors", 1 );
mysqli_report(MYSQLI_REPORT_STRICT | MYSQLI_REPORT_ALL);

date_default_timezone_set("Asia/Seoul");

if(empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] !== "on") {
    $location = 'https:/' . $_SERVER["HTTP_HOST"] . $_SERVER['REQUEST_URI'];
    header('HTTP/1.1 301 Moved Permanently');
    header('Location: ' . $location);
    exit();
}

// if(!session_id()) {
//     session_start();
//     if( !isset($_SESSION['login']) ) $_SESSION['login'] = false;
// }

define("_db_address", "localhost");
define("_db_name", "highthon9th");
if(true) {
    define("_db_id", "");
    define("_db_pwd", "");
}

define("_main_domain", "highthon9th.gdre.dev");

if(empty($_POST)) $_POST = json_decode(file_get_contents('php://input'), true);

function error_then_exit($error) {
    if($error == 500) {
        require_once $_SERVER["DOCUMENT_ROOT"]."/errorpage/500.php";
    } else if($error == 403) {
        require_once $_SERVER["DOCUMENT_ROOT"]."/errorpage/403.php";
    } else {
        require_once $_SERVER["DOCUMENT_ROOT"]."/errorpage/404.php";
    }
}

function auth() {
	if(!isset($_SERVER['PHP_AUTH_USER'])) return false;

    try {
        $pdo = new PDO(
            "mysql:host="._db_address.";dbname="._db_name.";charset=utf8",
            _db_id,_db_pwd,
            array(
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_EMULATE_PREPARES => false
            )
        );

        $stmt = $pdo->prepare("SELECT * FROM `login_sessions` WHERE `token` = :token");
        $stmt->bindValue(':token', $_SERVER['PHP_AUTH_USER']);
        $stmt->execute();
        $session = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if($session) {
            $stmt = $pdo->prepare("SELECT * FROM `accounts` WHERE `idx` = :idx");
            $stmt->bindValue(':idx', $session["account_idx"]);
            $stmt->execute();
            $accounts = $stmt->fetch(PDO::FETCH_ASSOC);

            if(isset($accounts["pwd"])) unset($accounts["pwd"]);

            return $accounts;
        } else return false;

    } catch (PDOException $e) {
        return false;
    }
}

function response_header_init() {
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Credentials: true");
    header("Access-Control-Max-Age: 86400");
    header("Access-Control-Allow-Methods: POST, GET, PUT, DELETE, OPTIONS");
    header("Access-Control-Allow-Headers: Accept, Content-Type, Content-Length, Accept-Encoding, X-CSRF-Token, Authorization");
    header("Content-type: application/json;charset=utf-8");
}

?>