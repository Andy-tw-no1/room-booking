<?php
include "db.php";

// 依日期 + 時間排序
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
    <title>預約查看</title>
</head>
<body>

<h2>📅 所有預約紀錄</h2>

<table border="1" cellpadding="10">
    <tr>
        <th>編號</th>
        <th>姓名</th>
        <th>日期</th>
        <th>開始時間</th>
        <th>結束時間</th>
    </tr>

    <?php
    $i = 1;
    while($row = $result->fetch_assoc()) {
    ?>
    <tr>
        <td><?php echo $i++; ?></td>
        <td><?php echo $row["user"]; ?></td>
        <td><?php echo $row["date"]; ?></td>
        <td><?php echo $row["start_time"]; ?></td>
        <td><?php echo $row["end_time"]; ?></td>
    </tr>
    <?php } ?>

</table>

</body>
</html>
