<?php
// ==========================================
// 1. 初始化 Session（必須放在檔案最頂端，不能有任何 HTML 或空白）
// ==========================================
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// 引入你的資料庫連線檔案
include "db.php"; 

// 設定時區
date_default_timezone_set("Asia/Taipei");

// ==========================================
// 2. 🛡️ 核心安全防線：後端「微秒級」時間差防連點
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_time = microtime(true); // 取得當前精準到微秒的時間戳記
    $form_user = isset($_POST['user']) ? trim($_POST['user']) : '';
    $form_band = isset($_POST['band_name']) ? trim($_POST['band_name']) : '';

    // 檢查 Session 裡有沒有上一次的送出紀錄
    if (isset($_SESSION['last_submit_time']) && isset($_SESSION['last_submit_user']) && isset($_SESSION['last_submit_band'])) {
        $time_difference = $current_time - $_SESSION['last_submit_time'];
        
        // 🚨 防禦機制：如果距離上次送出小於 3.0 秒，且名字與團名完全相同，直接判定為連點！
        if ($time_difference < 3.0 && $form_user === $_SESSION['last_submit_user'] && $form_band === $_SESSION['last_submit_band']) {
            // 直接中斷，不寫入資料庫，並噴出警告視窗導回首頁
            echo "<script>
                alert('系統收到重複請求！請勿連續點擊「送出」按鈕。您的志願已經在處理中。'); 
                window.location.href='index.html';
            </script>";
            exit(); // 徹底終止程式
        }
    }

    // 通過檢查，把這次的數據存進 Session，當作下一次比對的依據
    $_SESSION['last_submit_time'] = $current_time;
    $_SESSION['last_submit_user'] = $form_user;
    $_SESSION['last_submit_band'] = $form_band;
} else {
    // 如果不是 POST 請求，直接踢回首頁
    header("Location: index.html");
    exit();
}

// ==========================================
// 3. 接收表單資料
// ==========================================
$user = mysqli_real_escape_escape_string($conn, $_POST['user']);
$band_name = mysqli_real_escape_string($conn, $_POST['band_name']);

// 這裡你可以根據你資料庫的架構去寫。
// 假設你是把 5 個志願存在同一個欄位，或是拆開，以下是標準的接收與防 SQL 注入處理：
$wish1_date = !empty($_POST['wish1_date']) ? mysqli_real_escape_string($conn, $_POST['wish1_date']) : null;
$wish1_start = !empty($_POST['wish1_start']) ? mysqli_real_escape_string($conn, $_POST['wish1_start']) : null;
$wish1_duration = !empty($_POST['wish1_duration']) ? intval($_POST['wish1_duration']) : null;

$wish2_date = !empty($_POST['wish2_date']) ? mysqli_real_escape_string($conn, $_POST['wish2_date']) : null;
$wish2_start = !empty($_POST['wish2_start']) ? mysqli_real_escape_string($conn, $_POST['wish2_start']) : null;
$wish2_duration = !empty($_POST['wish2_duration']) ? intval($_POST['wish2_duration']) : null;

$wish3_date = !empty($_POST['wish3_date']) ? mysqli_real_escape_string($conn, $_POST['wish3_date']) : null;
$wish3_start = !empty($_POST['wish3_start']) ? mysqli_real_escape_string($conn, $_POST['wish3_start']) : null;
$wish3_duration = !empty($_POST['wish3_duration']) ? intval($_POST['wish3_duration']) : null;

$wish4_date = !empty($_POST['wish4_date']) ? mysqli_real_escape_string($conn, $_POST['wish4_date']) : null;
$wish4_start = !empty($_POST['wish4_start']) ? mysqli_real_escape_string($conn, $_POST['wish4_start']) : null;
$wish4_duration = !empty($_POST['wish4_duration']) ? intval($_POST['wish4_duration']) : null;

$wish5_date = !empty($_POST['wish5_date']) ? mysqli_real_escape_string($conn, $_POST['wish5_date']) : null;
$wish5_start = !empty($_POST['wish5_start']) ? mysqli_real_escape_string($conn, $_POST['wish5_start']) : null;
$wish5_duration = !empty($_POST['wish5_duration']) ? intval($_POST['wish5_duration']) : null;


// ==========================================
// 4. 寫入資料庫（請根據你實際的資料表名稱與欄位調整）
// ==========================================
// 💡 這裡以常見的 wishes 表單儲存為範例（你可以替換成你原本寫好的 SQL INSERT 指令）：
$sql = "INSERT INTO wishes (
            user, band_name, 
            wish1_date, wish1_start, wish1_duration,
            wish2_date, wish2_start, wish2_duration,
            wish3_date, wish3_start, wish3_duration,
            wish4_date, wish4_start, wish4_duration,
            wish5_date, wish5_start, wish5_duration,
            created_at
        ) VALUES (
            '$user', '$band_name', 
            '$wish1_date', '$wish1_start', " . ($wish1_duration ?? "NULL") . ",
            " . ($wish2_date ? "'$wish2_date'" : "NULL") . ", " . ($wish2_start ? "'$wish2_start'" : "NULL") . ", " . ($wish2_duration ?? "NULL") . ",
            " . ($wish3_date ? "'$wish3_date'" : "NULL") . ", " . ($wish3_start ? "'$wish3_start'" : "NULL") . ", " . ($wish3_duration ?? "NULL") . ",
            " . ($wish4_date ? "'$wish4_date'" : "NULL") . ", " . ($wish4_start ? "'$wish4_start'" : "NULL") . ", " . ($wish4_duration ?? "NULL") . ",
            " . ($wish5_date ? "'$wish5_date'" : "NULL") . ", " . ($wish5_start ? "'$wish5_start'" : "NULL") . ", " . ($wish5_duration ?? "NULL") . ",
            NOW()
        )";

if ($conn->query($sql) === TRUE) {
    // ==========================================
    // 5. 🚀 防 F5 機制：資料庫寫入成功後，立刻重導向！
    // ==========================================
    // 絕對不要在原地 echo "成功"，否則使用者重新整理網頁又會再送一次！
    // 這裡我們直接用 Javascript 彈窗提示成功，並重導向到分配結果或首頁
    echo "<script>
        alert('下週志願登記成功！系統將在週日 21:10 自動完成分配。');
        window.location.href = 'result.php'; 
    </script>";
    exit();
} else {
    echo "錯誤: " . $sql . "<br>" . $conn->error;
}

$conn->close();
?>
