<?php
include "db.php";
date_default_timezone_set("Asia/Taipei");

// =====================
// 安全驗證：保留金鑰手動觸發機制
// =====================
$now = new DateTime();
$secret = $_GET["secret"] ?? "";
$allowedByKey = ($secret === "hotmusic2025");

if (!$allowedByKey) {
    die("未授權的執行請求。");
}

// =====================
// 1. 執行前，先清空上一次的分配結果
// =====================
$conn->query("TRUNCATE TABLE allocations");

// 2. 取得目前所有的志願（依送出時間排序）
$result = $conn->query("SELECT * FROM wishes ORDER BY created_at ASC");

$wishes = [];
while ($row = $result->fetch_assoc()) {
    $wishes[] = $row;
}

// =====================
// 3. 分配邏輯：志願序優先於送出時間
// =====================
$allocated = []; // 已分配的時段 ["date_start" => true]
$results   = []; // 最終分配結果 ["wish_id" => [結果資料]]

// 先將所有團初始化為「未分配狀態 (null)」
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

// 外迴圈：縱向跑 1 到 5 志願
for ($v = 1; $v <= 5; $v++) {
    
    // 內迴圈：橫向依填寫順序（先搶先贏）處理每一團
    foreach ($wishes as $wish) {
        $wishId = $wish["id"];

        // 如果這一團在前面的志願序已經分配成功，就直接跳過不處理
        if ($results[$wishId]["date"] !== null) {
            continue;
        }

        $date  = $wish["wish{$v}_date"];
        $start = $wish["wish{$v}_start"];

        // 如果該志願欄位是空的，換下一團
        if (empty($date) || empty($start)) continue;

        // 標準化時間格式
        $startDT = DateTime::createFromFormat("H:i:s", $start) ?: DateTime::createFromFormat("H:i", $start);
        if (!$startDT) continue;
        
        // 計算結束時間（+1小時）
        $endDT = clone $startDT;
        $endDT->modify("+1 hour");

        // 時段唯一 Key
        $key = $date . "_" . $startDT->format("H:i:s");

        // 檢查這個時段有沒有被前面的人佔用
        if (!isset($allocated[$key])) {
            // 時段有空，分配成功！
            $allocated[$key] = true;
            
            // 更新這一團的分配結果
            $results[$wishId]["date"]       = $date;
            $results[$wishId]["start_time"] = $startDT->format("H:i:s");
            $results[$wishId]["end_time"]   = $endDT->format("H:i:s");
        }
    }
}

// =====================
// 4. 寫入本次的分配結果（完美相容 MySQL 嚴格模式）
// =====================
$created_at = $now->format("Y-m-d H:i:s");
$success = 0;
$fail = 0;

$stmt = $conn->prepare("
    INSERT INTO allocations (wish_id, user, band_name, date, start_time, end_time, created_at)
    VALUES (?, ?, ?, ?, ?, ?, ?)
");

foreach ($results as $r) {
    // 保持原本的 null 狀態，直接傳進 bind_param，讓 PHP 自動轉成 SQL 的 NULL 寫入
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
// 5. 確定分配成功並寫入 allocations 後，最後一步才清空 wishes
// =====================
$conn->query("TRUNCATE TABLE wishes");

// =====================
// 6. 輸出執行結果畫面
// =====================
echo "<h3>排班與資料清理完成！</h3>";
echo "<strong>機制：</strong> 志願序絕對優先（嚴格模式相容版）<br><br>";
echo "本次成功分配：<span style='color: green; font-weight: bold;'>{$success}</span> 團<br>";
echo "本次未能分配：<span style='color: red; font-weight: bold;'>{$fail}</span> 團<br><br>";
echo "<strong>狀態：</strong> 歷史分配已成功更新至 allocations 表，wishes 原始填寫表已安全清空。<br>";
echo "<strong>執行時間：</strong> {$created_at}";

$conn->close();
?>
