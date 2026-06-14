<?php

include "db.php";

$user = $_POST["user"];
$date = $_POST["date"];
$start = $_POST["start"];
$end = $_POST["end"];

// 檢查開始時間是否早於結束時間
if ($start >= $end)
{
    die("開始時間必須早於結束時間");
}

// 檢查是否與既有預約重疊
$sql = "SELECT * FROM bookings
        WHERE date='$date'
        AND NOT (
            '$end' <= start_time
            OR '$start' >= end_time
        )";

$result = $conn->query($sql);

if (!$result)
{
    die("查詢失敗：" . $conn->error);
}

if ($result->num_rows > 0)
{
    die("此時段已被預約");
}

// 新增預約
$sql = "INSERT INTO bookings
        (user, date, start_time, end_time)
        VALUES
        ('$user', '$date', '$start', '$end')";

if ($conn->query($sql))
{
    echo "預約成功";
}
else
{
    echo "預約失敗：" . $conn->error;
}

$conn->close();

?>
