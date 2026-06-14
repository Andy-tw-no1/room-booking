<?php
include "db.php";

$sql = "SELECT * FROM bookings ORDER BY date, start_time";
$result = $conn->query($sql);

if (!$result) {
    die("查詢失敗：" . $conn->error);
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>預約查看</title>
</head>
<body>

<h2>📅 所有預約紀錄</h2>

<table border="1" cellpadding="10">
    <tr>
        <th>ID</th>
        <th>使用者</th>
        <th>日期</th>
        <th>開始</th>
        <th>結束</th>
    </tr>

    <?php while($row = $result->fetch_assoc()) { ?>
    <tr>
        <td><?php echo $row["id"]; ?></td>
        <td><?php echo $row["user"]; ?></td>
        <td><?php echo $row["date"]; ?></td>
        <td><?php echo $row["start_time"]; ?></td>
        <td><?php echo $row["end_time"]; ?></td>
    </tr>
    <?php } ?>

</table>

</body>
</html>
