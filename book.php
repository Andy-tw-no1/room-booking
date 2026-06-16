<?php

include "db.php";

date_default_timezone_set("Asia/Taipei");

// =====================
// 0. 檢查資料
// =====================
if (!isset($_POST["user"], $_POST["band_name"], $_POST["date"], $_POST["start"], $_POST["end"])) {
    die("資料不完整");
}

// =====================
// 1. 取得資料
// =====================
$user = trim($_POST["user"]);
$band_name = trim($_POST["band_name"]);
$date = $_POST["date"]; // 使用者選的日期 (例如: 2026-06-19)
$start = $_POST["start"];
$end = $_POST["end"];

// =====================
// 2. 時間格式與基本檢查
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
// ⭐ 新增規則：必須在當週週一 00:00 後才能預約
// =====================
$now = new DateTime(); // 現在時間
$targetDate = new DateTime($date); // 使用者想預約的日期

// 計算該日期所屬週的週一 00:00
// 'monday this week' 會自動根據該日期找出當週的週一
$targetMonday = clone $targetDate;
if ($targetDate->format('N') != 1) { // 如果選的不是週一，就找這週的週一
    $targetMonday->modify('monday this week');
}
$targetMonday->setTime(0, 0, 0); // 設定為 00:00:00

// 檢查現在時間是否小於該週週一 00:00
if ($now < $targetMonday) {
    die("預約失敗：該週時段必須在 " . $targetMonday->format('Y-m-d 00:00') . " 之後才開放預約！");
}


// =====================
// ⭐ 核心安全機制：獲取排隊鎖 (防止多人同時預約衝突)
// =====================
$lock_query = $conn->query("SELECT GET_LOCK('booking_lock', 5) AS locked");
$lock_row = $lock_query->fetch_assoc();

if (!$lock_row || $lock_row['locked'] != 1) {
    die("伺服器正忙碌中，請稍後再試");
}

try {
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
    // 5. 送出時間
    // =====================
    $created_at = date("Y-m-d H:i:s");

    // =====================
    // 6. 寫入資料
    // =====================
    $stmt = $conn->prepare("
        INSERT INTO bookings (user, band_name, date, start_time, end_time, created_at)
        VALUES (?, ?, ?, ?, ?, ?)
    ");

    $stmt->bind_param("ssssss", $user, $band_name, $date, $start, $end, $created_at);

    if ($stmt->execute()) {
        echo "預約成功<br><br>";
        echo "送出時間：$created_at<br><br>";
        echo '<a href="view.php"><button>查看名單</button></a> ';
        echo '<a href="booking.html"><button>繼續預約</button></a>';
    } else {
        echo "預約失敗：" . $conn->error;
    }

} finally {
    $conn->query("SELECT RELEASE_LOCK('booking_lock')");
    $conn->close();
}

?>
