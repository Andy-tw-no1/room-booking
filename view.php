<?php
include "db.php";
date_default_timezone_set("Asia/Taipei");

// 取得今天日期
$today = date("Y-m-d");

// 修正 SQL：只撈取日期大於或等於今天的預約紀錄
$result = $conn->query("
    SELECT * FROM bookings 
    WHERE date >= '$today' 
    ORDER BY date ASC, start_time ASC
");
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>預約名單 - 屏大熱音</title>
    <style>
        body {
            background: #0d0714;
            color: #fff;
            font-family: Arial, sans-serif;
            padding: 30px 20px;
            margin: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .container {
            width: 100%;
            max-width: 900px;
        }
        h2 {
            color: #00ffff;
            text-shadow: 0 0 10px rgba(0,255,255,0.4);
            margin-bottom: 25px;
            text-align: center;
        }
        .table-responsive {
            width: 100%;
            overflow-x: auto;
            background: rgba(255, 255, 255, 0.03);
            border-radius: 12px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 10px 30px rgba(0,0,0,0.5);
        }
        table {
            width: 100%;
            border-collapse: collapse;
            text-align: center;
            min-width: 600px;
        }
        th, td {
            padding: 14px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }
        th {
            background: rgba(255, 255, 255, 0.07);
            color: #ff007f;
            font-weight: bold;
            letter-spacing: 1px;
        }
        tr:hover td {
            background: rgba(255, 255, 255, 0.02);
        }
        .no-data {
            padding: 30px;
            color: #888;
        }
        .action-group {
            margin-top: 30px;
            display: flex;
            justify-content: center;
            gap: 15px;
        }
        .btn {
            background: rgba(255,255,255,0.05);
            color: #fff;
            border: 1px solid rgba(255,255,255,0.2);
            padding: 10px 20px;
            border-radius: 6px;
            cursor: pointer;
            text-decoration: none;
            font-weight: bold;
            transition: all 0.2s;
        }
        .btn:hover {
            background: #fff;
            color: #000;
        }
        .btn-primary {
            background: #ff007f;
            border-color: #ff007f;
        }
        .btn-primary:hover {
            background: #e60073;
            color: #fff;
            box-shadow: 0 0 15px rgba(255,0,127,0.4);
        }
    </style>
</head>
<body>

<div class="container">
    <h2>團室預約名單</h2>

    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>使用者</th>
                    <th>團名</th>
                    <th>日期</th>
                    <th>開始時間</th>
                    <th>結束時間</th>
                    <th>登記時間</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($result && $result->num_rows > 0) {
                    while($row = $result->fetch_assoc()) {
                        echo "<tr>
                            <td>" . htmlspecialchars($row['user']) . "</td>
                            <td>" . htmlspecialchars($row['band_name']) . "</td>
                            <td>" . htmlspecialchars($row['date']) . "</td>
                            <td>" . htmlspecialchars(substr($row['start_time'], 0, 5)) . "</td>
                            <td>" . htmlspecialchars(substr($row['end_time'], 0, 5)) . "</td>
                            <td>" . htmlspecialchars($row['created_at']) . "</td>
                        </tr>";
                    }
                } else {
                    echo "<tr><td colspan='6' class='no-data'>目前尚無任何預約紀錄</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>

    <div class="action-group">
        <a href="index.html" class="btn">返回首頁</a>
        <a href="booking.html" class="btn btn-primary">我要預約</a>
    </div>
</div>

</body>
</html>
