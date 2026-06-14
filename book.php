<?php

include "db.php";

$user = $_POST["user"];
$date = $_POST["date"];
$start = $_POST["start"];
$end = $_POST["end"];
$sql = $sql = "SELECT * FROM bookings
        WHERE date='$date'
        AND NOT (
            '$end' <= start_time
            OR '$start' >= end_time
        )";

$result = $conn->query($sql);

if($result->num_rows > 0)
{
    die("此時段已被預約");
}
$sql = "INSERT INTO bookings
(user, date, start_time, end_time)
VALUES
('$user','$date','$start','$end')";

if($conn->query($sql))
{
    echo "預約成功";
}
else
{
    echo "預約失敗";
}

?>
