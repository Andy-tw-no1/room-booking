<?php
include "db.php";
date_default_timezone_set("Asia/Taipei");

// 計算下週範圍（供網頁標題與排班時間範圍對照顯示使用）
$now = new DateTime();
$dayOfWeekToday = (int)$now->format('N');
$daysUntilNextMonday = $dayOfWeekToday === 7 ? 1 : 8 - $dayOfWeekToday;
$nextMonday = clone $now;
$nextMonday->modify("+{$daysUntilNextMonday} days");
$nextMonday->setTime(0, 0, 0);
$nextSunday = clone $nextMonday;
$nextSunday->modify("+6 days");

// ==========================================
// 【核心撈取】改用「最新排班時間戳記」來完整撈取
// ==========================================
// 1. 先找出 allocations 表裡最新一次排班的寫入時間 (created_at)
$latest_query = $conn->query("SELECT MAX(created_at) as last_run FROM allocations");
$latest_run = null;
if ($latest_query) {
    $row = $latest_query->fetch_assoc();
    $latest_run = $row['last_run'] ?? null;
}

// 2. 只要是同一批寫入的資料，不論成功失敗，一概完整撈出
$result = null;
if (!empty($latest_run)) {
    $result = $conn->query("
        SELECT * FROM allocations 
        WHERE created_at = '$latest_run'
        ORDER BY (date IS NULL) ASC, date ASC, start_time ASC
    ");
}
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>分配結果 - 屏大熱音</title>
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
            color: #00ff99;
            text-shadow: 0 0 10px rgba(0,255,153,0.4);
            margin-bottom: 10px;
            text-align: center;
        }
        .week-range {
            text-align: center;
            color: #888;
            margin-bottom: 25px;
            font-size: 0.9rem;
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
            color: #00ff99;
            font-weight: bold;
            letter-spacing: 1px;
        }
        tr:hover td {
            background: rgba(255, 255, 255, 0.02);
        }
        .status-ok {
            color: #00ff99;
            font-weight: bold;
        }
        .status-fail {
            color: #ff4444;
            font-weight: bold;
        }
        .no-data {
            padding: 30px;
            color: #888;
        }
        .notice {
            background: rgba(0,255,153,0.05);
            border-left: 4px solid #00ff99;
            padding: 10px 14px;
            border-radius: 4px;
            color: #ccc;
            font-size: 0.85rem;
            margin-bottom: 25px;
            line-height: 1.6;
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
            background: #00ff99;
            border-color: #00ff99;
            color: #000;
        }
        .btn-primary:hover {
            background: #00cc77;
            color: #000;
        }
    </style>
</head>
<body>
<div class="container">
    <h2>下週團室分配結果</h2>
    <div class="week-range">
        <?= $nextMonday->format('Y-m-d') ?> ~ <?= $nextSunday->format('Y-m-d') ?>
    </div>

    <div class="notice">
        ✅ 每週日 21:10 公布分配結果<br>
        ❌ 若志願全部衝突，顯示「未能分配」，可使用本週即時預約系統登記
    </div>

    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>使用者</th>
                    <th>團名</th>
                    <th>日期</th>
                    <th>時段</th>
                    <th>狀態</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($result && $result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        if (!empty($row['date'])) {
                            $start = substr($row['start_time'], 0, 5);
                            $end   = substr($row['end_time'], 0, 5);
                            echo "<tr>
                                <td>" . htmlspecialchars($row['user']) . "</td>
                                <td>" . htmlspecialchars($row['band_name']) . "</td>
                                <td>" . htmlspecialchars($row['date']) . "</td>
                                <td>{$start} ~ {$end}</td>
                                <td class='status-ok'>✅ 已分配</td>
                            </tr>";
                        } else {
                            echo "<tr>
                                <td>" . htmlspecialchars($row['user']) . "</td>
                                <td>" . htmlspecialchars($row['band_name']) . "</td>
                                <td colspan='2' style='color:#888'>志願全部衝突或不符合下週時段</td>
                                <td class='status-fail'>❌ 未能分配</td>
                            </tr>";
                        }
                    }
                } else {
                    echo "<tr><td colspan='5' class='no-data'>結果尚未公布，請於週日 21:10 後查看</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>

    <div class="action-group">
        <a href="index.html" class="btn">返回首頁</a>
        <a href="wish.html" class="btn btn-primary">填寫志願</a>
    </div>
</div>
</body>
</html>
