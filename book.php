<?php

include "db.php";

$user = $_POST["user"];
$date = $_POST["date"];
$start = $_POST["start"];
$end = $_POST["end"];


// ===== 1. 時間格式轉換 =====
// 假設格式是 "10:00"

$startTime = strtotime($start);
$endTime = strtotime($end);

// ===== 2. 檢查時間是否合法 =====
if ($endTime <= $startTime) {
    die("結束時間必須大於開始時間");
}

// ===== 3. 限制最多 2 小時 =====
$diffHours = ($endTime - $startTime) / 3600;

if ($diffHours > 2) {
    die("一次最多只能預約 2 小時");
}


// ===== 4. 檢查是否重疊 =====
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


// ===== 5. 寫入資料 =====
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
