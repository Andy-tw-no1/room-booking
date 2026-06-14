<?php

$url = getenv("DB_URL");

$db = parse_url($url);

$host = $db["host"];
$user = $db["user"];
$pass = $db["pass"];
$dbname = ltrim($db["path"], "/");

$conn = new mysqli($host, $user, $pass, $dbname, 3306);

if ($conn->connect_error) {
    die("DB connection failed");
}

?>
