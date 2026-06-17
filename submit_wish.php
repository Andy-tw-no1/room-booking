<?php
include "db.php";
date_default_timezone_set("Asia/Taipei");

// =====================
// 0. 檢查截止時間
// =====================
$now = new DateTime();
$dayOfWeek = $now->format('N'); // 1=週一 7=週日
$hour = (int)$now->format('H');
$minute = (int)$now->format('i');

if ($dayOfWeek == 7 && ($hour > 21 || ($hour == 21 && $minute >= 0))) {
    die("志願登記已截止（每週日 21:00 截止）");
}

// =====================
// 1. 檢查資料
// =====================
if (!isset($_POST["user"], $_POST["band_name"], $_POST["wish1_date"], $_POST["wish1_start"])) {
    die("資料不完整，至少需填寫第 1 志願");
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
    $date  = isset($_POST["wish{$i}_date"])  ? trim($_POST["wish{$i}_date"])  : "";
    $start = isset($_POST["wish{$i}_start"]) ? trim($_POST["wish{$i}_start"]) : "";

    if (!empty($date) && !empty($start)) {
        $wishes[] = [
            "date"  => $date,
            "start" => $start
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

foreach ($wishes as $w) {
    $dt = new DateTime($w["date"]);
    if ($dt < $nextMonday || $dt > $nextSunday) {
        die("日期必須在下週範圍內（" . $nextMonday->format('Y-m-d') . " ~ " . $nextSunday->format('Y-m-d') . "）");
    }
}

// =====================
// 4. 寫入資料庫
// =====================
$created_at = $now->format("Y-m-d H:i:s");

// 補齊 5 個志願（沒填的補 NULL）
while (count($wishes) < 5) {
    $wishes[] = ["date" => null, "start" => null];
}

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
    echo "志願登記成功！<br><br>";
    echo "登記時間：$created_at<br><br>";
    echo "系統將於本週日 21:10 公布分配結果。<br><br>";
    echo '<a href="result.php"><button>查看結果</button></a> ';
    echo '<a href="index.html"><button>返回首頁</button></a>';
} else {
    echo "登記失敗：" . $conn->error;
}

$conn->close();
?>
