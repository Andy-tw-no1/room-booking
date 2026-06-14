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


// ===== 3. 時間檢查 =====
$startTime = strtotime($start);
$endTime = strtotime($end);

if ($endTime <= $startTime) {
    die("結束時間必須大於開始時間");
}


// ===== 4. 最多 2 小時 =====
if (($endTime - $startTime) / 3600 > 2) {
    die("一次最多只能預約 2 小時");
}


// ===== 5. 檢查重疊 =====
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

    echo "預約成功<br><br>";

    echo '<a href="view.php">
            <button>查看預約名單</button>
          </a>';

    echo ' ';

    echo '<a href="booking.html">
            <button>繼續預約</button>
          </a>';

} else {
    echo "預約失敗：" . $conn->error;
}

$conn->close();

?>
