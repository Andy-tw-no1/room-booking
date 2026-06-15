<?php
session_start();

// ⚠️ 只產生一次，避免被 iframe 重刷
if (!isset($_SESSION["captcha"])) {
    $_SESSION["captcha"] = rand(1000, 9999);
}

// 顯示驗證碼
echo "<div style='font-size:26px;font-weight:bold;letter-spacing:5px;text-align:center;'>"
    . $_SESSION["captcha"] .
"</div>";
?>
