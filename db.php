<?php

$url = getenv("DB_URL");
$db = parse_url($url);

$host = $db["host"];
$user = $db["user"];
$pass = $db["pass"];
$port = $db["port"] ?? 3306;
$dbname = ltrim($db["path"], "/");

$conn = new mysqli($host, $user, $pass, $dbname, $port);

if ($conn->connect_error) {
    die("連線失敗: " . $conn->connect_error);
}
?>
