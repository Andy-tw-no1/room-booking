<?php

$url = getenv("DB_URL");

$db = parse_url($url);

if (!$db) {
    die("DB_URL parse failed");
}

$host = $db["host"];
$user = $db["user"];
$pass = $db["pass"];
$port = $db["port"] ?? 3306;
$dbname = ltrim($db["path"], "/");

$conn = new mysqli($host, $user, $pass, $dbname, $port);

if ($conn->connect_error) {
    die("DB connect failed: " . $conn->connect_error);
}

// ⭐ 建議補上這行，防止台灣中文字（如團名、姓名）寫入 MySQL 時變成問號或亂碼
$conn->set_charset("utf8mb4");

?>
