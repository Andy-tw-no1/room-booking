<?php
include "db.php";
date_default_timezone_set("Asia/Taipei");

// ==========================================
// 1. 檢查基本輸入
// ==========================================
if (!isset($_POST["user"], $_POST["band_name"], $_POST["date"], $_POST["start"], $_POST["end"])) {
    die("資料不完整");
}

$user      = trim($_POST["user"]);
$band_name = trim($_POST["band_name"]);
$date      = trim($_POST["date"]);
$start     = trim($_POST["start"]);
$end       = trim($_POST["end"]);

if (empty($user) || empty($band_name) || empty($date) || empty($start) || empty($end)) {
    die("所有欄位皆為必填，不可空白");
}

// ==========================================
// 2. 嚴格的時間範圍與偷跑漏洞限制
// ==========================================
$now = new DateTime();
$targetDate = new DateTime($date);

// 限制 A：預約日期不能是今天以前
if ($targetDate->format('Y-m-d') < $now->format('Y-m-d')) {
    die("預約日期不可為過去的時間");
}

// 限制 B：修復週一判定漏洞
// 找出「今天」這週的週一 00:00 與週日 23:59
$currentMonday = clone $now;
$dayOfWeekToday = (int)$now->format('N');
$currentMonday->modify("-" . ($dayOfWeekToday - 1) . " days")->setTime(0, 0, 0);

$currentSunday = clone $currentMonday;
$currentSunday->modify("+6 days")->setTime(23, 59, 59);

// 嚴格限制：即時預約系統「只能預約當週（今天到這週日）」的時段
if ($targetDate < $currentMonday || $targetDate > $currentSunday) {
    die("即時預約系統僅開放預約「當週」時段（" . $currentMonday->format('Y-m-d') . " ~ " . $currentSunday->format('Y-m-d') . "）。下週時段請至首頁填寫下週志願。");
}

// ==========================================
// 3. 限制預約時長與整點/半點判定
// ==========================================
$startTime = DateTime::createFromFormat("H:i", $start);
$endTime   = DateTime::createFromFormat("H:i", $end);

if (!$startTime || !$endTime) {
    die("時間格式錯誤");
}

// 計算時差（秒）
$durationSeconds = $endTime->getTimestamp() - $startTime->getTimestamp();

if ($durationSeconds <= 0) {
    die("結束時間必須大於開始時間");
}

// 限制最多預約 2 小時 (7200秒)
if ($durationSeconds > 7200) {
    die("每次預約最多不可超過 2 小時");
}

// ==========================================
// 4. 精確排隊鎖 (Lock) 與防重疊判定
// ==========================================
// 優化：將全域鎖改為「日期鎖」，鎖定特定日期 (例如：lock_2026-06-15)
// 這樣預約星期二的人，就不會卡到預約星期五的人！
$lockName = "booking_lock_" . $date;
$lockStmt = $conn->prepare("SELECT GET_LOCK(?, 5)");
$lockStmt->bind_param("s", $lockName);
$lockStmt->execute();
$lockResult = $lockStmt->get_result()->fetch_row();
$lockStmt->close();

if (!$lockResult || $lockResult[0] != 1) {
    die("系統正忙碌中，請稍後再試");
}

try {
    // 檢查該日期是否有時段重疊的預約
    // 重疊數學公式：A_start < B_end AND A_end > B_start
    $checkStmt = $conn->prepare("
        SELECT id FROM bookings 
        WHERE date = ? 
        AND start_time < ? 
        AND end_time > ?
    ");
    
    $startTimeStr = $startTime->format("H:i:s");
    $endTimeStr   = $endTime->format("H:i:s");
    
    $checkStmt->bind_param("sss", $date, $endTimeStr, $startTimeStr);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    $checkStmt->close();

    if ($checkResult->num_rows > 0) {
        die("預約失敗：該時段已被其他樂團預約！");
    }

    // ==========================================
    // 5. 寫入預約資料
    // ==========================================
    $created_at = $now->format("Y-m-d H:i:s");
    $insStmt = $conn->prepare("
        INSERT INTO bookings (user, band_name, date, start_time, end_time, created_at) 
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $insStmt->bind_param("ssssss", $user, $band_name, $date, $startTimeStr, $endTimeStr, $created_at);

    if ($insStmt->execute()) {
        // 成功畫面
        echo "<!DOCTYPE html><html lang='zh-TW'><head><meta charset='UTF-8'>";
        echo "<meta name='viewport' content='width=device-width, initial-scale=1.0'>";
        echo "<title>預約成功</title>";
        echo "<style>
            body { background: #0d0714; color: #fff; font-family: Arial, sans-serif; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
            .box { text-align: center; background: rgba(255,255,255,0.05); padding: 40px; border-radius: 12px; border: 1px solid rgba(255,255,255,0.1); box-shadow: 0 10px 30px rgba(0,0,0,0.5); }
            h2 { color: #ff007f; text-shadow: 0 0 10px rgba(255,0,127,0.4); }
            p { color: #aaa; margin-bottom: 25px; }
            .btn { display: inline-block; margin: 0 8px; padding: 12px 24px; border-radius: 6px; text-decoration: none; font-weight: bold; transition: all 0.2s; }
            .btn-pink { background: #ff007f; color: #fff; }
            .btn-pink:hover { background: #e60073; box-shadow: 0 0 15px rgba(255,0,127,0.4); }
            .btn-gray { background: rgba(255,255,255,0.1); color: #fff; border: 1px solid rgba(255,255,255,0.2); }
            .btn-gray:hover { background: rgba(255,255,255,0.2); }
        </style></head><body>";
        echo "<div class='box'>";
        echo "<h2>🎉 團室預約成功！</h2>";
        echo "<p>預約時段：{$date} {$start} ~ {$end}</p>";
        echo "<a href='view.php' class='btn btn-pink'>查看預約名單</a>";
        echo "<a href='index.html' class='btn btn-gray'>返回首頁</a>";
        echo "</div></body></html>";
    } else {
        echo "系統錯誤，登記失敗：" . $conn->error;
    }
    $insStmt->close();

} finally {
    // ==========================================
    // 6. 解放日期鎖 (確保不論成功失敗都會解鎖)
    // ==========================================
    $unlockStmt = $conn->prepare("SELECT RELEASE_LOCK(?)");
    $unlockStmt->bind_param("s", $lockName);
    $unlockStmt->execute();
    $unlockStmt->close();
}

$conn->close();
?>
