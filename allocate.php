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
// 清除本週舊的分配結果
// =====================
$conn->query("DELETE FROM allocations WHERE date BETWEEN '$weekStart' AND '$weekEnd'");

// =====================
// 取得本週所有志願（依送出時間排序，越早越優先）
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
// 分配邏輯
// =====================
$allocated = []; // 已分配的時段 ["date_start" => true]
$results   = []; // 分配結果

foreach ($wishes as $wish) {
    $assigned = false;

    for ($i = 1; $i <= 5; $i++) {
        $date  = $wish["wish{$i}_date"];
        $start = $wish["wish{$i}_start"];

        if (empty($date) || empty($start)) continue;

        // 計算結束時間（+1小時）
        $startDT = DateTime::createFromFormat("H:i:s", $start);
        if (!$startDT) {
            $startDT = DateTime::createFromFormat("H:i", $start);
        }
        $endDT = clone $startDT;
        $endDT->modify("+1 hour");
        $end = $endDT->format("H:i");

        $key = $date . "_" . $start;

        if (!isset($allocated[$key])) {
            // 這個時段還沒被佔用，分配給這個團
            $allocated[$key] = true;
            $results[] = [
                "wish_id"   => $wish["id"],
                "user"      => $wish["user"],
                "band_name" => $wish["band_name"],
                "date"      => $date,
                "start_time"=> $startDT->format("H:i:s"),
                "end_time"  => $endDT->format("H:i:s"),
            ];
            $assigned = true;
            break;
        }
    }

    // 如果五個志願都衝突，記錄為未分配
    if (!$assigned) {
        $results[] = [
            "wish_id"   => $wish["id"],
            "user"      => $wish["user"],
            "band_name" => $wish["band_name"],
            "date"      => null,
            "start_time"=> null,
            "end_time"  => null,
        ];
    }
}

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

echo "分配完成！<br>";
echo "成功分配：{$success} 團<br>";
echo "未能分配：{$fail} 團<br>";
echo "執行時間：{$created_at}";

$conn->close();
?>
