<?php

$conn = new mysqli(
    "localhost",
    "root",
    "",
    "room"
);

if ($conn->connect_error) {
    die("連線失敗");
}

echo "資料庫連線成功";

?>