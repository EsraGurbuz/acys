<?php
// Turkish:
// Oturum başlatma
// Zaten giriş yapmış kullanıcıyı ana sayfaya yönlendir.
// English:
// Start session
// Redirect already logged-in users to the main dashboard.
session_start();
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

// Turkish:
// Veritabanı bağlantı dosyasını dahil et.
// English:
// Include the database configuration file.
require_once 'config/db_config.php';

// Form gönderildi mi kontrol et.
// Check if the form has been submitted.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error_message = "Kullanıcı adı ve şifre boş bırakılamaz.";
    } else {
        // SQL Injection saldırılarını önlemek için prepared statement kullan.
        // Use a prepared statement to prevent SQL Injection attacks.
        $stmt = $pdo->prepare("SELECT user_id, username, password, role FROM Users WHERE username = :username");
        $stmt->bindParam(':username', $username);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // Kullanıcı bulunduysa ve şifre doğruysa
        // If a user is found and the password is correct
        if ($user && password_verify($password, $user['password'])) {
            // Oturum değişkenlerini ayarla
            // Set session variables
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];

            // Ana sayfaya yönlendir
            // Redirect to the main page
            header('Location: index.php');
            exit();
        } else {
            $error_message = "Hatalı kullanıcı adı veya şifre.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ACYS - Giriş Yap</title>

    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <link rel="stylesheet" href="public/adminlte/plugins/fontawesome-free/css/all.min.css">
    <link rel="stylesheet" href="public/adminlte/plugins/icheck-bootstrap/icheck-bootstrap.min.css">
    <link rel="stylesheet" href="public/adminlte/dist/css/adminlte.min.css">
</head>
<body class="hold-transition login-page">
<div class="login-box">
    <div class="login-logo">
        <a href="index.php"><b>ACYS</b> Giriş</a>
    </div>
    <div class="card">
        <div class="card-body login-card-body">
            <p class="login-box-msg">Oturumunuzu başlatmak için giriş yapın</p>

            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger" role="alert">
                    <?php echo $error_message; ?>
                </div>
            <?php endif; ?>

            <form action="login.php" method="post">
                <div class="input-group mb-3">
                    <input type="text" name="username" class="form-control" placeholder="Kullanıcı Adı" required>
                    <div class="input-group-append">
                        <div class="input-group-text">
                            <span class="fas fa-user"></span>
                        </div>
                    </div>
                </div>
                <div class="input-group mb-3">
                    <input type="password" name="password" class="form-control" placeholder="Şifre" required>
                    <div class="input-group-append">
                        <div class="input-group-text">
                            <span class="fas fa-lock"></span>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-8">
                    </div>
                    <div class="col-4">
                        <button type="submit" class="btn btn-primary btn-block">Giriş Yap</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
<script src="public/adminlte/plugins/jquery/jquery.min.js"></script>
<script src="public/adminlte/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="public/adminlte/dist/js/adminlte.min.js"></script>
</body>
</html>