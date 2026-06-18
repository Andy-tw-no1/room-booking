<?php
include "db.php";

/* =====================
   排程結果
===================== */
$alloc = $conn->query("
    SELECT * FROM allocations
    WHERE status='success'
    ORDER BY date, start_time
");

/* =====================
   直接預約
===================== */
$book = $conn->query("
    SELECT * FROM bookings
    ORDER BY date, start_time
");

/* =====================
   志願資料
===================== */
$wish = $conn->query("
    SELECT * FROM wishes
    ORDER BY created_at DESC
");
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
<meta charset="UTF-8">
<title>預約總覽</title>
<style>
body{
    background:#0d0714;
    color:#fff;
    font-family:Arial;
    padding:30px;
}
h2{
    color:#00ffff;
    margin-top:40px;
}
table{
    width:100%;
    border-collapse:collapse;
    margin-bottom:40px;
}
th,td{
    padding:10px;
    border-bottom:1px solid #333;
}
</style>
</head>
<body>

<h2>🎯 自動排程結果（allocations）</h2>
<table>
<tr>
<th>團名</th>
<th>日期</th>
<th>時間</th>
</tr>
<?php while($a=$alloc->fetch_assoc()): ?>
<tr>
<td><?= htmlspecialchars($a["band_name"]) ?></td>
<td><?= $a["date"] ?></td>
<td><?= substr($a["start_time"],0,5) ?> ~ <?= substr($a["end_time"],0,5) ?></td>
</tr>
<?php endwhile; ?>
</table>

<h2>📌 直接預約（bookings）</h2>
<table>
<tr>
<th>團名</th>
<th>日期</th>
<th>時間</th>
</tr>
<?php while($b=$book->fetch_assoc()): ?>
<tr>
<td><?= htmlspecialchars($b["band_name"]) ?></td>
<td><?= $b["date"] ?></td>
<td><?= substr($b["start_time"],0,5) ?> ~ <?= substr($b["end_time"],0,5) ?></td>
</tr>
<?php endwhile; ?>
</table>

<h2>📝 志願填寫（wishes）</h2>
<table>
<tr>
<th>使用者</th>
<th>團名</th>
<th>送出時間</th>
</tr>
<?php while($w=$wish->fetch_assoc()): ?>
<tr>
<td><?= htmlspecialchars($w["user"]) ?></td>
<td><?= htmlspecialchars($w["band_name"]) ?></td>
<td><?= $w["created_at"] ?></td>
</tr>
<?php endwhile; ?>
</table>

</body>
</html>
