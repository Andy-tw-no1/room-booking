<?php
include "db.php";
date_default_timezone_set("Asia/Taipei");

// =====================
// 0. 檢查截止時間
// =====================
$now = new DateTime();
$dayOfWeek = (int)$now->format('N');
$hour = (int)$now->format('H');
$minute = (int)$now->format('i');

if ($dayOfWeek == 7 && $hour >= 21) {
    die("志願登記已截止（每週日 21:00 截止）");
}

// =====================
// 1. 檢查基本資料
// =====================
if (!isset($_POST["user"], $_POST["band_name"])) {
    die("資料不完整");
}

$user      = trim($_POST["user"]);
$band_name = trim($_POST["band_name"]);

if (empty($user) || empty($band_name)) {
    die("姓名與團名不可空白");
}

// =====================
// 2. 整理志願資料
// =====================
$wishes = [];
for ($i = 1; $i <= 5; $i++) {
    $date     = isset($_POST["wish{$i}_date"])     ? trim($_POST["wish{$i}_date"])     : "";
    $start    = isset($_POST["wish{$i}_start"])    ? trim($_POST["wish{$i}_start"])    : "";
    $duration = isset($_POST["wish{$i}_duration"]) ? trim($_POST["wish{$i}_duration"]) : "";

    if (!empty($date) && !empty($start) && !empty($duration)) {
        // 計算結束時間
        $startDT = DateTime::createFromFormat("H:i", $start);
        $endDT   = clone $startDT;
        $endDT->modify("+{$duration} hour");

        // 若結束時間超過 24:00 則拒絕
        $endHour = (int)$endDT->format('H');
        $startHour = (int)$startDT->format('H');
        if ($endHour < $startHour || ($endHour == 0 && $startHour != 22)) {
            die("第 {$i} 志願結束時間不可超過 24:00");
        }

        $wishes[] = [
            "date"  => $date,
            "start" => $startDT->format("H:i:s"),
            "end"   => $endDT->format("H:i:s"),
        ];
    }
}

if (count($wishes) === 0) {
    die("至少需填寫一個志願");
}

// =====================
// 3. 驗證日期必須是下週
// =====================
$today = new DateTime();
$dayOfWeekToday = (int)$today->format('N');
$daysUntilNextMonday = $dayOfWeekToday === 7 ? 1 : 8 - $dayOfWeekToday;
$nextMonday = clone $today;
$nextMonday->modify("+{$daysUntilNextMonday} days");
$nextMonday->setTime(0, 0, 0);
$nextSunday = clone $nextMonday;
$nextSunday->modify("+6 days");
$nextSunday->setTime(23, 59, 59);

foreach ($wishes as $index => $w) {
    $dt = new DateTime($w["date"]);
    if ($dt < $nextMonday || $dt > $nextSunday) {
        $num = $index + 1;
        die("第 {$num} 志願的日期必須在下週範圍內（" . $nextMonday->format('Y-m-d') . " ~ " . $nextSunday->format('Y-m-d') . "）");
    }
}

// =====================
// 4. 補齊 5 個志願（沒填的補 NULL）
// =====================
while (count($wishes) < 5) {
    $wishes[] = ["date" => null, "start" => null, "end" => null];
}

// =====================
// 5. 寫入資料庫
// =====================
$created_at = $now->format("Y-m-d H:i:s");

$stmt = $conn->prepare("
    INSERT INTO wishes 
    (user, band_name, wish1_date, wish1_start, wish2_date, wish2_start, wish3_date, wish3_start, wish4_date, wish4_start, wish5_date, wish5_start, created_at)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");

$stmt->bind_param(
    "sssssssssssss",
    $user, $band_name,
    $wishes[0]["date"], $wishes[0]["start"],
    $wishes[1]["date"], $wishes[1]["start"],
    $wishes[2]["date"], $wishes[2]["start"],
    $wishes[3]["date"], $wishes[3]["start"],
    $wishes[4]["date"], $wishes[4]["start"],
    $created_at
);

if ($stmt->execute()) {
    echo "<!DOCTYPE html><html lang='zh-TW'><head><meta charset='UTF-8'>";
    echo "<meta name='viewport' content='width=device-width, initial-scale=1.0'>";
    echo "<title>登記成功</title>";
    echo "<style>
        body { background: #0d0714; color: #fff; font-family: Arial, sans-serif;
               display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .box { text-align: center; background: rgba(255,255,255,0.05); padding: 40px;
               border-radius: 12px; border: 1px solid rgba(255,255,255,0.1); }
        h2 { color: #00ff99; }
        p { color: #aaa; }
        .btn { display: inline-block; margin: 10px 5px; padding: 10px 20px;
               border-radius: 6px; text-decoration: none; font-weight: bold; cursor: pointer; }
        .btn-green { background: #00ff99; color: #000; }
        .btn-gray { background: rgba(255,255,255,0.1); color: #fff; border: 1px solid rgba(255,255,255,0.2); }
    </style></head><body>";
    echo "<div class='box'>";
    echo "<h2>✅ 志願登記成功！</h2>";
    echo "<p>登記時間：{$created_at}</p>";
    echo "<p>系統將於本週日 21:10 公布分配結果。</p>";
    echo "<a href='result.php' class='btn btn-green'>查看結果</a>";
    echo "<a href='index.html' class='btn btn-gray'>返回首頁</a>";
    echo "</div></body></html>";
} else {
    die("登記失敗：" . $conn->error);
}

$conn->close();
?>
