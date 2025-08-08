<?php
// Turkish:
// Oturumu başlat
// English:
// Start session
session_start();

// Turkish:
// Tüm oturum değişkenlerini temizle
// English:
// Unset all session variables
$_SESSION = array();

// Turkish:
// Oturumu sonlandır
// English:
// Destroy the session
session_destroy();

// Turkish:
// Kullanıcıyı giriş sayfasına yönlendir
// English:
// Redirect the user to the login page
header('Location: login.php');
exit();
?>

