<?php
include "db.php";
date_default_timezone_set("Asia/Taipei");

$now = new DateTime();
$dayOfWeek = (int)$now->format('N');
$hour = (int)$now->format('H');
$minute = (int)$now->format('i');

$secret = $_GET["secret"] ?? "";
$allowedByTime = ($dayOfWeek == 7 && $hour == 21 && $minute <= 30);
$allowedByKey = ($secret === "hotmusic2025");

if (!$allowedByTime && !$allowedByKey) {
    die("不在執行時間");
}

/* =====================
   週範圍
===================== */
$nextMonday = new DateTime('next monday');
$nextSunday = clone $nextMonday;
$nextSunday->modify("+6 days");

$weekStart = $nextMonday->format("Y-m-d");
$weekEnd = $nextSunday->format("Y-m-d");

/* =====================
   清除舊結果（安全版）
===================== */
$conn->query("
    DELETE FROM allocations
    WHERE date BETWEEN '$weekStart' AND '$weekEnd'
");

/* =====================
   讀取志願
===================== */
$res = $conn->query("
    SELECT * FROM wishes
    ORDER BY created_at ASC
");

$wishes = [];
while ($row = $res->fetch_assoc()) {
    $wishes[] = $row;
}

/* =====================
   排程
===================== */
$allocated = [];
$final = [];

foreach ($wishes as $w) {

    for ($i = 1; $i <= 5; $i++) {

        $date = $w["wish{$i}_date"];
        $start = $w["wish{$i}_start"];

        if (!$date || !$start) continue;

        $dt = DateTime::createFromFormat("H:i:s", $start)
           ?: DateTime::createFromFormat("H:i", $start);

        if (!$dt) continue;

        $key = $date . "_" . $dt->format("H:i");

        if (!isset($allocated[$key])) {

            $end = clone $dt;
            $end->modify("+1 hour");

            $allocated[$key] = true;

            $final[] = [
                "wish_id" => $w["id"],
                "user" => $w["user"],
                "band_name" => $w["band_name"],
                "date" => $date,
                "start" => $dt->format("H:i:s"),
                "end" => $end->format("H:i:s"),
                "status" => "success"
            ];

            break; // 只取一個志願
        }
    }
}

/* =====================
   寫入 DB（只存成功）
===================== */
$created_at = $now->format("Y-m-d H:i:s");

foreach ($final as $f) {
    $stmt = $conn->prepare("
        INSERT INTO allocations
        (wish_id, user, band_name, date, start_time, end_time, status, created_at)
        VALUES (?, ?, ?, ?, ?, ?, 'success', ?)
    ");

    $stmt->bind_param(
        "issssss",
        $f["wish_id"],
        $f["user"],
        $f["band_name"],
        $f["date"],
        $f["start"],
        $f["end"],
        $created_at
    );

    $stmt->execute();
}

echo "排程完成：".count($final)." 筆成功";
?>
