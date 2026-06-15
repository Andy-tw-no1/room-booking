<?php
include "db.php";

$sql = "
    SELECT * FROM bookings
    WHERE TIMESTAMP(date, end_time) >= NOW()
    ORDER BY date ASC, start_time ASC
";

$result = $conn->query($sql);

if (!$result) {
    die("查詢失敗：" . $conn->error);
}
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>預約名單</title>
</head>

<body>

<h2>📅 預約名單（未過期）</h2>

<table border="1" cellpadding="10">
    <tr>
        <th>編號</th>
        <th>使用者</th>
        <th>日期</th>
        <th>開始</th>
        <th>結束</th>
    </tr>

<?php
$i = 1;
while ($row = $result->fetch_assoc()) {
    echo "<tr>";
    echo "<td>".$i++."</td>";
    echo "<td>".htmlspecialchars($row["user"])."</td>";
    echo "<td>".$row["date"]."</td>";
    echo "<td>".$row["start_time"]."</td>";
    echo "<td>".$row["end_time"]."</td>";
    echo "</tr>";
}
?>

</table>

</body>
</html>

<?php $conn->close(); ?>
