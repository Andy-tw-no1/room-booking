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
// ⭐ 核心安全機制：獲取排隊鎖 (防止多人同時預約衝突)
// =====================
// 鎖定名為 'booking_lock' 的資源，最多等候 5 秒
$lock_query = $conn->query("SELECT GET_LOCK('booking_lock', 5) AS locked");
$lock_row = $lock_query->fetch_assoc();

if (!$lock_row || $lock_row['locked'] != 1) {
    die("伺服器正忙碌中，請稍後再試");
}

try {
    // =====================
    // 4. 防重疊 (在鎖定狀態下檢查，絕對安全)
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
    // 5. ⭐ 送出時間（台北時區）
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
    // =====================
    // ⭐ 無論成功或失敗，最後一定要釋放鎖，讓下一個人可以使用
    // =====================
    $conn->query("SELECT RELEASE_LOCK('booking_lock')");
    $conn->close();
}

?>
