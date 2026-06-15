<?php
session_start();

$chars = "ABCDEFGHJKLMNPQRSTUVWXYZ23456789";
$code = "";

for ($i = 0; $i < 4; $i++) {
    $code .= $chars[rand(0, strlen($chars) - 1)];
}

$_SESSION["captcha"] = $code;
?>
