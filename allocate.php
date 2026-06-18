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
// 6. 輸出執行成果
// =====================
echo "<h3>排班與資料清理完成！</h3>";
echo "<strong>機制：</strong> 志願序絕對優先（安全機制：強制過濾非下週範圍志願）<br>";
echo "<strong>本次有效下週區間：</strong> {$allowed_start} ~ {$allowed_end}<br><br>";
echo "本次成功分配：<span style='color: green; font-weight: bold;'>{$success}</span> 團<br>";
echo "本次未能分配：<span style='color: red; font-weight: bold;'>{$fail}</span> 團（包含日期不符或衝突者）<br><br>";
echo "<strong>狀態：</strong> 分配結果已更新，wishes 表已清空。<br>";
echo "<strong>執行時間：</strong> {$created_at}";

$conn->close();
?>
