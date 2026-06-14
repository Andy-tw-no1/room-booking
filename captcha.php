<?php
session_start();

$code = rand(1000, 9999);
$_SESSION["captcha"] = $code;

// 用簡單 HTML 顯示（不靠 GD）
echo "<div style='font-size:28px;font-weight:bold;letter-spacing:5px;'>$code</div>";
?>
