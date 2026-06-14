<?php

session_start();
include "db.php";

// ===== 1. 取得資料 =====
$user = $_POST["user"];
$date = $_POST["date"];
$start = $_POST["start"];
$end = $_POST["end"];
$captcha = $_POST["captcha"];


// ===== 2. 驗證碼檢查 =====
if ($captcha != $_SESSION["captcha"]) {
    die("驗證碼錯誤");
}


// ===== 3. 時間轉換 =====
$startTime = strtotime($start);
$endTime = strtotime($end);

if ($endTime <= $startTime) {
    die("結束時間必須大於開始時間");
}


// ===== 4. 限制 2 小時 =====
$diffHours = ($endTime - $startTime) / 3600;

if ($diffHours > 2) {
    die("一次最多只能預約 2 小時");
}


// ===== 5. 檢查時間重疊 =====
$sql = "SELECT * FROM bookings
        WHERE date='$date'
        AND NOT (
            '$end' <= start_time
            OR '$start' >= end_time
        )";

$result = $conn->query($sql);

if (!$result) {
    die("查詢失敗：" . $conn->error);
}

if ($result->num_rows > 0) {
    die("此時段已被預約");
}


// ===== 6. 寫入資料 =====
$sql = "INSERT INTO bookings
        (user, date, start_time, end_time)
        VALUES
        ('$user', '$date', '$start', '$end')";

if ($conn->query($sql)) {
    echo "預約成功";
} else {
    echo "預約失敗：" . $conn->error;
}

$conn->close();

?>
