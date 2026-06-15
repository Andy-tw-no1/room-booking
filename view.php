<?php
include "db.php";

// 刪除已過期的預約
$sql = "DELETE FROM bookings
        WHERE CONCAT(date,' ',end_time) < NOW()";
$conn->query($sql);

// 查詢預約資料
$sql = "SELECT * FROM bookings
        ORDER BY date ASC, start_time ASC";

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

<h2>📅 預約名單</h2>

<table border="1" cellpadding="10">
    <tr>
        <th>編號</th>
        <th>使用者</th>
        <th>日期</th>
        <th>開始時間</th>
        <th>結束時間</th>
    </tr>

    <?php
    $i = 1;

    while($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $i++ . "</td>";
        echo "<td>" . htmlspecialchars($row["user"]) . "</td>";
        echo "<td>" . htmlspecialchars($row["date"]) . "</td>";
        echo "<td>" . htmlspecialchars($row["start_time"]) . "</td>";
        echo "<td>" . htmlspecialchars($row["end_time"]) . "</td>";
        echo "</tr>";
    }
    ?>

</table>

<br>

<a href="booking.html">
    <button>返回預約頁面</button>
</a>

</body>
</html>

<?php
$conn->close();
?>
