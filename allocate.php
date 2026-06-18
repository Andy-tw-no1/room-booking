<?php
include "db.php";
date_default_timezone_set("Asia/Taipei");

// =====================
// 安全驗證：只允許在週日 21:00~21:30 之間執行
// 或帶上 secret key 手動觸發
// =====================
$now = new DateTime();
$dayOfWeek = (int)$now->format('N');
$hour = (int)$now->format('H');
$minute = (int)$now->format('i');
$secret = $_GET["secret"] ?? "";

$allowedByTime = ($dayOfWeek == 7 && $hour == 21 && $minute >= 0 && $minute <= 30);
$allowedByKey  = ($secret === "hotmusic2025");

if (!$allowedByTime && !$allowedByKey) {
    die("不在允許執行的時間範圍內");
}

// =====================
// 計算下週範圍
// =====================
$dayOfWeekToday = (int)$now->format('N');
// 如果是週日，下週一是 1 天後；如果是週一到週六，下週一是 (8 - 當天星期幾) 天後
$daysUntilNextMonday = $dayOfWeekToday === 7 ? 1 : 8 - $dayOfWeekToday;

$nextMonday = clone $now;
$nextMonday->modify("+{$daysUntilNextMonday} days");
$nextMonday->setTime(0, 0, 0);

$nextSunday = clone $nextMonday;
$nextSunday->modify("+6 days");
$nextSunday->setTime(23, 59, 59);

$weekStart = $nextMonday->format("Y-m-d");
$weekEnd   = $nextSunday->format("Y-m-d");

// =====================
// 清除舊的分配結果
// =====================
$conn->query("DELETE FROM allocations WHERE date BETWEEN '$weekStart' AND '$weekEnd'");

// =====================
// 取得所有志願（依送出時間排序，越早越優先）
// =====================
$result = $conn->query("
    SELECT * FROM wishes
    WHERE (
        wish1_date BETWEEN '$weekStart' AND '$weekEnd'
        OR wish2_date BETWEEN '$weekStart' AND '$weekEnd'
        OR wish3_date BETWEEN '$weekStart' AND '$weekEnd'
        OR wish4_date BETWEEN '$weekStart' AND '$weekEnd'
        OR wish5_date BETWEEN '$weekStart' AND '$weekEnd'
    )
    ORDER BY created_at ASC
");

$wishes = [];
while ($row = $result->fetch_assoc()) {
    $wishes[] = $row;
}

// =====================
// 公平分配邏輯（輪流比對志願）
// =====================
$allocated = [];      // 已分配的時段 ["date_HH:mm" => true]
$final_assignments = []; // 最終成功的分配紀錄 ["wish_id" => data_array]
$failed_wishes = [];     // 沒分配成功的名單

// 初始化：先假設大家都沒成功
foreach ($wishes as $wish) {
    $failed_wishes[$wish["id"]] = [
        "wish_id"   => $wish["id"],
        "user"      => $wish["user"],
        "band_name" => $wish["band_name"],
        "date"      => null,
        "start_time"=> null,
        "end_time"  => null,
    ];
}

// 核心重構：外迴圈走 1~5 志願，內迴圈走每個人
for ($round = 1; $round <= 5; $round++) {
    foreach ($wishes as $wish) {
        $wishId = $wish["id"];

        // 如果這個人在前面的志願已經分到時段了，直接跳過不參與後續志願分配
        if (isset($final_assignments[$wishId])) {
            continue;
        }

        $date  = $wish["wish{$round}_date"];
        $start = $wish["wish{$round}_start"];

        if (empty($date) || empty($start)) continue;

        // 統一時間格式為 H:i，避免因秒數（:00）導致的 key 比對失敗
        $startDT = DateTime::createFromFormat("H:i:s", $start);
        if (!$startDT) {
            $startDT = DateTime::createFromFormat("H:i", $start);
        }
        
        if (!$startDT) continue; // 格式不對就跳過

        $timeKey = $startDT->format("H:i");
        $key = $date . "_" . $timeKey;

        // 計算結束時間（+1小時）
        $endDT = clone $startDT;
        $endDT->modify("+1 hour");

        if (!isset($allocated[$key])) {
            // 時段沒人搶，成功分配！
            $allocated[$key] = true;
            
            $final_assignments[$wishId] = [
                "wish_id"   => $wishId,
                "user"      => $wish["user"],
                "band_name" => $wish["band_name"],
                "date"      => $date,
                "start_time"=> $startDT->format("H:i:s"),
                "end_time"  => $endDT->format("H:i:s"),
            ];

            // 從失敗名單中移除
            unset($failed_wishes[$wishId]);
        }
    }
}

// 合併成功與失敗的結果準備寫入
$results = array_merge(array_values($final_assignments), array_values($failed_wishes));

// =====================
// 寫入分配結果
// =====================
$created_at = $now->format("Y-m-d H:i:s");
$success = 0;
$fail = 0;

foreach ($results as $r) {
    $stmt = $conn->prepare("
        INSERT INTO allocations (wish_id, user, band_name, date, start_time, end_time, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param(
        "issssss",
        $r["wish_id"],
        $r["user"],
        $r["band_name"],
        $r["date"],
        $r["start_time"],
        $r["end_time"],
        $created_at
    );
    $stmt->execute();

    if ($r["date"]) {
        $success++;
    } else {
        $fail++;
    }
}

echo "分配完成！ (已改為公平輪詢演算法)<br>";
echo "成功分配：{$success} 團<br>";
echo "未能分配：{$fail} 團<br>";
echo "執行時間：{$created_at}";

$conn->close();
?>
