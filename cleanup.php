<?php
include "db.php";

// 刪除已結束的預約（安全版）
$sql = "
    DELETE FROM bookings
    WHERE TIMESTAMP(date, end_time) < NOW()
";

if ($conn->query($sql)) {
    echo "清理完成";
} else {
    echo "清理失敗：" . $conn->error;
}

$conn->close();
?>
