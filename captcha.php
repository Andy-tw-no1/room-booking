<?php
session_start();

// 防止任何輸出破壞圖片
ob_clean();
header("Content-Type: image/png");

$code = rand(1000, 9999);
$_SESSION["captcha"] = $code;

// 如果 GD 沒有 → 直接 fallback
if (!function_exists("imagecreatetruecolor")) {
    echo "CAPTCHA: $code";
    exit;
}

$image = imagecreatetruecolor(120, 40);

$bg = imagecolorallocate($image, 255, 255, 255);
$text = imagecolorallocate($image, 0, 0, 0);

imagefilledrectangle($image, 0, 0, 120, 40, $bg);

imagestring($image, 5, 35, 10, $code, $text);

imagepng($image);
imagedestroy($image);
?>
