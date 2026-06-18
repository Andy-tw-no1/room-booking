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
// 1. 刪除上一次（所有歷史）的執行結果
// =====================
$conn->query("TRUNCATE TABLE allocations");

// =====================
// 2. 取得目前所有的志願（依送出時間排序）
// =====================
$result = $conn->query("SELECT * FROM wishes ORDER BY created_at ASC");

$wishes = [];
while ($row = $result->fetch_assoc()) {
    $wishes[] = $row;
}

// =====================
// 分配邏輯（保持你原本的邏輯）
// =====================
$allocated = []; 
$results   = []; 

foreach ($wishes as $wish) {
    $assigned = false;

    for ($i = 1; $i <= 5; $i++) {
        $date  = $wish["wish{$i}_date"];
        $start = $wish["wish{$i}_start"];

        if (empty($date) || empty($start)) continue;

        $startDT = DateTime::createFromFormat("H:i:s", $start) ?: DateTime::createFromFormat("H:i", $start);
        if (!$startDT) continue;

        $endDT = clone $startDT;
        $endDT->modify("+1 hour");

        $key = $date . "_" . $start;

        if (!isset($allocated[$key])) {
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
// 3. 寫入本次的分配結果
// =====================
$created_at = $now->format("Y-m-d H:i:s");
$success = 0;
$fail = 0;

$stmt = $conn->prepare("
    INSERT INTO allocations (wish_id, user, band_name, date, start_time, end_time, created_at)
    VALUES (?, ?, ?, ?, ?, ?, ?)
");

foreach ($results as $r) {
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
$stmt->close();

// =====================
// 4. 分配完成後，立刻清空所有使用者的志願
// =====================
$conn->query("TRUNCATE TABLE wishes");


echo "強制排班與資料清理完成！<br>";
echo "本次成功分配：{$success} 團<br>";
echo "本次未能分配：{$fail} 團<br>";
echo "歷史分配記錄已清除，且使用者的志願皆已清空完畢。<br>";
echo "執行時間：{$created_at}";

$conn->close();
?>
