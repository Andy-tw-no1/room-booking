<?php
include "db.php";
date_default_timezone_set("Asia/Taipei");

// =====================
// 安全驗證：鎖定管理員密碼
// =====================
$now = new DateTime();
$secret = $_GET["secret"] ?? "";
$allowedByKey = ($secret === "hotmusic2025");

if (!$allowedByKey) {
    die("未授權的執行請求。");
}

// ==========================================
// 【強制限定】精準計算下週的有效日期範圍
// ==========================================
$dayOfWeekToday = (int)$now->format('N');
$daysUntilNextMonday = $dayOfWeekToday === 7 ? 1 : 8 - $dayOfWeekToday;

$nextMonday = clone $now;
$nextMonday->modify("+{$daysUntilNextMonday} days");
$nextMonday->setTime(0, 0, 0);

$nextSunday = clone $nextMonday;
$nextSunday->modify("+6 days");
$nextSunday->setTime(23, 59, 59);

$allowed_start = $nextMonday->format("Y-m-d"); // 下週一的日期
$allowed_end   = $nextSunday->format("Y-m-d"); // 下週日的日期

// =====================
// 1. 執行前，先清空舊的分配結果
// =====================
$conn->query("TRUNCATE TABLE allocations");

// 2. 取得目前所有的志願（依送出時間排序：先搶先贏）
$result = $conn->query("SELECT * FROM wishes ORDER BY created_at ASC");

$wishes = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $wishes[] = $row;
    }
}

// =====================
// 3. 分配核心邏輯：志願序縱向匹配
// =====================
$allocated = []; // 已佔用的時段 ["date_time" => true]
$results   = []; // 最終分配結果

// 初始化每一團為未分配狀態
foreach ($wishes as $wish) {
    $results[$wish["id"]] = [
        "wish_id"   => $wish["id"],
        "user"      => $wish["user"],
        "band_name" => $wish["band_name"],
        "date"      => null,
        "start_time"=> null,
        "end_time"  => null,
    ];
}

// 外迴圈跑 1 ~ 5 志願
for ($v = 1; $v <= 5; $v++) {
    // 內迴圈依填寫時間先後處理
    foreach ($wishes as $wish) {
        $wishId = $wish["id"];

        // 若此樂團在先前輪次已分配成功，直接跳過
        if ($results[$wishId]["date"] !== null) {
            continue;
        }

        $date  = $wish["wish{$v}_date"] ?? '';
        $start = $wish["wish{$v}_start"] ?? '';

        if (empty($date) || empty($start)) continue;

        // 【安全機制】如果日期不落在計算出的下週範圍內，直接不予分配
        if ($date < $allowed_start || $date > $allowed_end) {
            continue; 
        }

        // 標準化時間格式
        $startDT = DateTime::createFromFormat("H:i:s", $start) ?: DateTime::createFromFormat("H:i", $start);
        if (!$startDT) continue;
        
        $endDT = clone $startDT;
        $endDT->modify("+1 hour");

        // 時段唯一 Key
        $key = $date . "_" . $startDT->format("H:i:s");

        // 檢查是否被佔用
        if (!isset($allocated[$key])) {
            $allocated[$key] = true; // 鎖定時段
            
            $results[$wishId]["date"]       = $date;
            $results[$wishId]["start_time"] = $startDT->format("H:i:s");
            $results[$wishId]["end_time"]   = $endDT->format("H:i:s");
        }
    }
}

// =====================
// 4. 寫入 allocations（確保 NULL 值安全寫入）
// =====================
$created_at = $now->format("Y-m-d H:i:s");
$success = 0;
$fail = 0;

$stmt = $conn->prepare("
    INSERT INTO allocations (wish_id, user, band_name, date, start_time, end_time, created_at)
    VALUES (?, ?, ?, ?, ?, ?, ?)
");

foreach ($results as $r) {
    $db_date  = $r["date"];
    $db_start = $r["start_time"];
    $db_end   = $r["end_time"];

    $stmt->bind_param(
        "issssss",
        $r["wish_id"],
        $r["user"],
        $r["band_name"],
        $db_date,
        $db_start,
        $db_end,
        $created_at
    );
    $stmt->execute();

    if ($r["date"] !== null) {
        $success++;
    } else {
        $fail++;
    }
}
$stmt->close();

// =====================
// 5. 分配完成，清空 wishes 原始表
// =====================
$conn->query("TRUNCATE TABLE wishes");

// =====================
// 6. 輸出執行成果與一鍵導入按鈕
// =====================
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>排班系統後台 - 屏大熱音</title>
    <style>
        body { background: #0d0714; color: #fff; font-family: Arial, sans-serif; padding: 40px; text-align: center; }
        .box { background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; padding: 30px; max-width: 600px; margin: 0 auto; box-shadow: 0 10px 30px rgba(0,0,0,0.5); text-align: left; line-height: 1.8; }
        h3 { color: #00ff99; text-align: center; margin-top: 0; }
        .btn-import { display: block; text-align: center; background: #00ffff; color: #000; padding: 14px 20px; text-decoration: none; font-weight: bold; border-radius: 6px; margin-top: 25px; box-shadow: 0 0 15px rgba(0,255,255,0.4); transition: all 0.2s; }
        .btn-import:hover { background: #00cccc; box-shadow: 0 0 25px rgba(0,255,255,0.6); }
        .btn-view { display: block; text-align: center; color: #888; text-decoration: none; margin-top: 15px; font-size: 0.9rem; }
        .btn-view:hover { color: #fff; }
    </style>
</head>
<body>

<div class="box">
    <h3>排班與資料清理完成！</h3>
    <strong>排班機制：</strong> 志願序絕對優先（安全機制：強制過濾非下週範圍志願）<br>
    <strong>下週有效區間：</strong> <span style="color: #00ff99; font-weight: bold;"><?= $allowed_start ?> ~ <?= $allowed_end ?></span><br><br>
    本次成功分配：<span style="color: #00ff99; font-weight: bold;"><?= $success ?></span> 團<br>
    本次未能分配：<span style="color: #ff4444; font-weight: bold;"><?= $fail ?></span> 團（包含日期不符或衝突者）<br><br>
    <strong>資料庫狀態：</strong> 抽籤結果已暫存至 allocations 表，wishes 填寫表已安全清空。<br>
    <strong>後台執行時間：</strong> <?= $created_at ?><br>

    <a href="import_to_bookings.php?secret=hotmusic2025" class="btn-import">🚀 一鍵正式發布並導入預約課表 (bookings)</a>
    <a href="result.php" target="_blank" class="btn-view">先在新視窗預覽分配結果 (result.php)</a>
</div>

</body>
</html>
<?php
$conn->close();
?>
