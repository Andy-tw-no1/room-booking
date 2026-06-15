<?php

include "db.php";

date_default_timezone_set("Asia/Taipei");

// =====================
// 0. 檢查資料
// =====================
if (!isset($_POST["user"], $_POST["date"], $_POST["start"], $_POST["end"])) {
    die("資料不完整");
}

// =====================
// 1. 取得資料
// =====================
$user = trim($_POST["user"]);
$date = $_POST["date"];
$start = $_POST["start"];
$end = $_POST["end"];

// =====================
// 2. 時間檢查
// =====================
$startDT = DateTime::createFromFormat("H:i", $start);
$endDT   = DateTime::createFromFormat("H:i", $end);

if (!$startDT || !$endDT) {
    die("時間格式錯誤");
}

if ($endDT <= $startDT) {
    die("結束時間必須大於開始時間");
}

// =====================
// 3. 最多 2 小時
// =====================
$diff = ($endDT->getTimestamp() - $startDT->getTimestamp()) / 3600;

if ($diff > 2) {
    die("一次最多只能預約 2 小時");
}

// =====================
// 4. 防重疊
// =====================
$stmt = $conn->prepare("
    SELECT id FROM bookings
    WHERE date = ?
    AND NOT (
        ? <= start_time
        OR ? >= end_time
    )
");

$stmt->bind_param("sss", $date, $end, $start);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    die("此時段已被預約");
}

// =====================
// 5. 寫入資料
// =====================
$stmt = $conn->prepare("
    INSERT INTO bookings (user, date, start_time, end_time)
    VALUES (?, ?, ?, ?)
");

$stmt->bind_param("ssss", $user, $date, $start, $end);

if ($stmt->execute()) {

    echo "預約成功<br><br>";
    echo '<a href="view.php"><button>查看名單</button></a> ';
    echo '<a href="booking.html"><button>繼續預約</button></a>';

} else {
    echo "預約失敗：" . $conn->error;
}

$conn->close();

?>
