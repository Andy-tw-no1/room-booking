<?php
session_start();

$code = rand(1000, 9999);
$_SESSION["captcha"] = $code;

header("Content-type: image/png");

$image = imagecreate(100, 40);

$bg = imagecolorallocate($image, 255, 255, 255);
$text = imagecolorallocate($image, 0, 0, 0);

imagestring($image, 5, 30, 10, $code, $text);

imagepng($image);
imagedestroy($image);
?>
