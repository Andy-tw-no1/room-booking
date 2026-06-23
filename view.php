<?php
include "db.php";
date_default_timezone_set("Asia/Taipei");

// 使用 UNION ALL 把「手動直接預約 (bookings)」與「自動分配結果 (allocations)」合併
// 並且同時套用方案一：自動過濾掉今天以前、以及今天已經結束的過期預約
$sql = "
    SELECT user, band_name, date, start_time, end_time, created_at, '直接預約' AS source 
    FROM bookings 
    WHERE date > CURDATE() OR (date = CURDATE() AND end_time >= CURTIME())

    UNION ALL

    SELECT user, band_name, date, start_time, end_time, created_at, '志願分配' AS source 
    FROM allocations 
    WHERE date IS NOT NULL 
      AND (date > CURDATE() OR (date = CURDATE() AND end_time >= CURTIME()))

    ORDER BY date ASC, start_time ASC
";

$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>預約名單 - 屏大熱音</title>
    <style>
        body {
            /* 調整為極致純黑背景 */
            background: #0b0505; 
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
            max-width: 950px;
        }
        h2 {
            /* 標題改為紅色，並帶有紅光外框特效 */
            color: #ff0033; 
            text-shadow: 0 0 12px rgba(255, 0, 51, 0.5);
            margin-bottom: 25px;
            text-align: center;
        }
        .table-responsive {
            width: 100%;
            overflow-x: auto;
            /* 背景改為微透明黑灰色 */
            background: rgba(20, 10, 10, 0.6); 
            border-radius: 12px;
            /* 邊框改為帶有暗紅線條 */
            border: 1px solid rgba(255, 0, 51, 0.2);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.7);
        }
        table {
            width: 100%;
            border-collapse: collapse;
            text-align: center;
            min-width: 650px;
        }
        th, td {
            padding: 14px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }
        th {
            background: rgba(255, 0, 51, 0.1); /* 表格標題背景帶有淡紅 */
            color: #ff3355; /* 欄位名字改為亮紅色 */
            font-weight: bold;
            letter-spacing: 1px;
        }
        tr:hover td {
            background: rgba(255, 0, 51, 0.04); /* 滑鼠劃過表格時亮起微弱紅光 */
        }
        .no-data {
            padding: 30px;
            color: #666;
        }
        /* 來源標籤樣式調整 */
        .badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
        }
        /* 直接預約改成暗暗的紅框 */
        .badge-direct {
            background: rgba(255, 255, 255, 0.05);
            color: #ccc;
            border: 1px solid #555;
        }
        /* 志願分配改成亮紅色標籤 */
        .badge-alloc {
            background: rgba(255, 0, 51, 0.15);
            color: #ff0033;
            border: 1px solid #ff0033;
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
        /* 我要預約按鈕徹底改為烈火紅 */
        .btn-primary {
            background: #ff0033; 
            border-color: #ff0033;
        }
        .btn-primary:hover {
            background: #cc0029;
            color: #fff;
            box-shadow: 0 0 15px rgba(255, 0, 51, 0.6);
        }
    </style>
</head>
<body>

<div class="container">
    <h2>團室預約總名單</h2>

    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>預約類型</th>
                    <th>使用者</th>
                    <th>團名</th>
                    <th>日期</th>
                    <th>開始時間</th>
                    <th>結束時間</th>
                    <th>登記/分配時間</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($result && $result->num_rows > 0) {
                    while($row = $result->fetch_assoc()) {
                        // 根據來源顯示不同的彩色標籤
                        $sourceBadge = ($row['source'] === '直接預約') 
                            ? "<span class='badge badge-direct'>直接預約</span>" 
                            : "<span class='badge badge-alloc'>志願分配</span>";

                        echo "<tr>
                            <td>" . $sourceBadge . "</td>
                            <td>" . htmlspecialchars($row['user']) . "</td>
                            <td>" . htmlspecialchars($row['band_name']) . "</td>
                            <td>" . htmlspecialchars($row['date']) . "</td>
                            <td>" . htmlspecialchars(substr($row['start_time'], 0, 5)) . "</td>
                            <td>" . htmlspecialchars(substr($row['end_time'], 0, 5)) . "</td>
                            <td>" . htmlspecialchars($row['created_at']) . "</td>
                        </tr>";
                    }
                } else {
                    echo "<tr><td colspan='7' class='no-data'>目前尚無任何有效的預約紀錄</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>

    <div class="action-group">
        <a href="index.html" class="btn">返回首頁</a>
        <a href="booking.html" class="btn btn-primary">預約本週</a>
    </div>
</div>

</body>
</html>
