<?php

session_start();
include "db.php";

date_default_timezone_set("Asia/Taipei");

// =====================
// 0. 檢查資料
// =====================
if (!isset($_POST["user"], $_POST["date"], $_POST["start"], $_POST["end"], $_POST["captcha"])) {
    die("資料不完整");
}

// =====================
// 1. 取得資料
// =====================
$user = $_POST["user"];
$date = $_POST["date"];
$start = $_POST["start"];
$end = $_POST["end"];
$captcha = $_POST["captcha"];


// =====================
// 2. 驗證碼（一次性）
// =====================
if (!isset($_SESSION["captcha"]) || $captcha != $_SESSION["captcha"]) {
    unset($_SESSION["captcha"]);
    die("驗證碼錯誤");
}
unset($_SESSION["captcha"]);


// =====================
// 3. ⭐ 週一 00:00 開放（正確關鍵修正）
// =====================

// 取得「本週週一 00:00」
$weekStart = new DateTime();
$weekStart->modify('monday this week');
$weekStart->setTime(0, 0, 0);

$now = new DateTime();

// ❗如果現在還沒到週一 00:00 → 全部禁止
if ($now < $weekStart) {
    die("尚未開放本週預約（每週一 00:00 開放）");
}


// =====================
// 4. 時間檢查
// =====================
$startTime = strtotime($start);
$endTime = strtotime($end);

if ($startTime === false || $endTime === false) {
    die("時間格式錯誤");
}

if ($endTime <= $startTime) {
    die("結束時間必須大於開始時間");
}


// =====================
// 5. 最多 2 小時
// =====================
if (($endTime - $startTime) / 3600 > 2) {
    die("一次最多只能預約 2 小時");
}


// =====================
// 6. 防重疊
// =====================
$sql = "SELECT * FROM bookings
        WHERE date='$date'
        AND NOT (
            '$end' <= start_time
            OR '$start' >= end_time
        )";

$result = $conn->query($sql);

if (!$result) {
    die("SQL錯誤：" . $conn->error);
}

if ($result->num_rows > 0) {
    die("此時段已被預約");
}


// =====================
// 7. 寫入資料
// =====================
$sql = "INSERT INTO bookings
(user, date, start_time, end_time)
VALUES
('$user','$date','$start','$end')";

if ($conn->query($sql)) {

    echo "預約成功<br><br>";

    echo '<a href="view.php"><button>查看名單</button></a> ';
    echo '<a href="booking.html"><button>繼續預約</button></a>';

} else {
    echo "預約失敗：" . $conn->error;
}

$conn->close();

?>
