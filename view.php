<?php
include "db.php";

// 查詢（依時間排序）
$result = $conn->query("SELECT * FROM bookings ORDER BY date ASC, start_time ASC");
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>預約名單</title>

    <style>
        body {
            font-family: Arial;
            margin: 20px;
        }

        table {
            border-collapse: collapse;
            width: 100%;
        }

        th, td {
            border: 1px solid #ccc;
            padding: 8px;
            text-align: center;
        }

        th {
            background: #f2f2f2;
        }

        button {
            padding: 6px 12px;
            margin-top: 10px;
            cursor: pointer;
        }
    </style>
</head>

<body>

<h2>團室預約名單</h2>

<table>
<tr>
    <th>使用者</th>
    <th>團名</th>
    <th>日期</th>
    <th>開始</th>
    <th>結束</th>
    <th>送出時間</th>
</tr>

<?php
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        echo "<tr>
            <td>" . htmlspecialchars($row['user']) . "</td>
            <td>" . htmlspecialchars($row['band_name']) . "</td>
            <td>" . htmlspecialchars($row['date']) . "</td>
            <td>" . htmlspecialchars($row['start_time']) . "</td>
            <td>" . htmlspecialchars($row['end_time']) . "</td>
            <td>" . htmlspecialchars($row['created_at']) . "</td>
        </tr>";
    }
} else {
    echo "<tr><td colspan='6'>目前沒有任何預約紀錄</td></tr>";
}
?>

</table>

<br>
<a href="booking.html"><button>返回預約</button></a>

</body>
</html>
