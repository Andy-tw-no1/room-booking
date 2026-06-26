<?php
// =====================
// 🛡️ 啟動 Session 進行後端防連點檢查 (必須在檔案最上方)
// =====================
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

include "db.php";
date_default_timezone_set("Asia/Taipei");

// =====================
// 🛡️ 後端時間差防護鎖 (3秒內同名同團禁止重複 INSERT)
// =====================
$current_time = microtime(true);
if (isset($_SESSION['last_submit_time'], $_POST['user'], $_POST['band_name'])) {
    $time_difference = $current_time - $_SESSION['last_submit_time'];
    $last_user = $_SESSION['last_submit_user'];
    $last_band = $_SESSION['last_submit_band'];

    // 如果 3 秒內連續發送，且姓名與團名完全相同，直接無情攔截
    if ($time_difference < 3.0 && $_POST['user'] === $last_user && $_POST['band_name'] === $last_band) {
        echo "<script>alert('請勿重複點擊！您的志願已經在處理中。'); window.location.href='index.html';</script>";
        exit();
    }
}

//
