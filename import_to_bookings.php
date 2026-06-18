<?php
include "db.php";
date_default_timezone_set("Asia/Taipei");

// =====================
// 安全驗證：防止一般社員誤觸
// =====================
$secret = $_GET["secret"] ?? "";
if ($secret !== "hotmusic2025") {
    die("未授權的執行請求。");
}

// 1. 找出 allocations 表裡最新一次排班的時間戳記
$latest_query = $conn->query("SELECT MAX(created_at) as last_run FROM allocations");
$latest_run = null;
if ($latest_query) {
    $row = $latest_query->fetch_assoc();
    $latest_run = $row['last_run'] ?? null;
}

if (empty($latest_run)) {
    die("<h3>導入失敗：</h3> 找不到任何排班分配紀錄，請先執行 allocate.php 排班！");
}

// 2. 抓出該批次中「成功分配（date 有值）」的樂團資料
$result = $conn->query("
    SELECT user, band_name, date, start_time, end_time, created_at 
    FROM allocations 
    WHERE created_at = '$latest_run' AND date IS NOT NULL
");

if (!$result || $result->num_rows === 0) {
    die("<h3>導入提示：</h3> 該批次中沒有任何成功分配（正取）的樂團，無資料可導入。");
}

$imported_count = 0;

// 3. 準備將資料寫入 bookings 表 (使用 prepare 防 SQL 注入)
$stmt = $conn->prepare("
    INSERT INTO bookings (user, band_name, date, start_time, end_time, created_at)
    VALUES (?, ?, ?, ?, ?, ?)
");

while ($row = $result->fetch_assoc()) {
    $stmt->bind_param(
        "ssssss",
        $row['user'],
        $row['band_name'],
        $row['date'],
        $row['start_time'],
        $row['end_time'],
        $row['created_at']
    );
    $stmt->execute();
    $imported_count++;
}
$stmt->close();

// 4. 導入成功提示畫面與自動跳轉
echo "<div style='background: #0d0714; color: #fff; font-family: Arial, sans-serif; padding: 40px; text-align: center; height: 100vh; display: flex; flex-direction: column; justify-content: center; align-items: center;'>";
echo "  <h2 style='color: #00ff99; text-shadow: 0 0 10px rgba(0,255,153,0.3);'>🎉 課表導入成功！</h2>";
echo "  <p style='color: #aaa;'>共成功將 <strong>{$imported_count}</strong> 筆抽籤正取名單同步至即時預約系統 (bookings)。</p>";
echo "  <p style='color: #666; font-size: 0.9rem;'>網頁將在 3 秒後自動前往預約名單頁面...</p>";
echo "  <a href='view.php' style='margin-top: 20px; color: #00ffff; text-decoration: none; border: 1px solid #00ffff; padding: 10px 20px; border-radius: 4px;'>手動前往預約名單 (view.php)</a>";
echo "</div>";

// 3 秒後自動導向 view.php 看結果
header("refresh:3;url=view.php");

$conn->close();
?>
