<?php
// Oturum başlatma ve oturum kontrolü
session_start();
// Veritabanı bağlantı dosyasını dahil et
require_once 'config/db_config.php';
// Sadece giriş yapmış kullanıcıların sayfaya erişimini sağlar.
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// English:
// Start session and check for active session
// This ensures that only logged-in users can access the page.
// If no session exists, the user is redirected to the login page.
// require_once 'config/db_config.php'; // Veritabanı bağlantısı henüz kullanılmıyor, sonra eklenecek
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ACYS - Network Device Management System</title>

    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <link rel="stylesheet" href="public/adminlte/plugins/fontawesome-free/css/all.min.css">
    <link rel="stylesheet" href="public/adminlte/dist/css/adminlte.min.css">
</head>
<body class="hold-transition sidebar-mini">
<div class="wrapper">
    <?php include 'partials/navbar.php'; ?>
    <?php include 'partials/sidebar.php'; ?>

    <div class="content-wrapper">
        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1>Ana Sayfa</h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="#">ACYS</a></li>
                            <li class="breadcrumb-item active">Ana Sayfa</li>
                        </ol>
                    </div>
                </div>
            </div></section>

        <section class="content">

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Ana İçerik Başlığı</h3>

                    <div class="card-tools">
                        <button type="button" class="btn btn-tool" data-card-widget="collapse" title="Collapse">
                            <i class="fas fa-minus"></i>
                        </button>
                        <button type="button" class="btn btn-tool" data-card-widget="remove" title="Remove">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    Buraya projenizin ana içeriği gelecek.
                </div>
                <div class="card-footer">
                    Footer
                </div>
            </div>
        </section>
    </div>
    <aside class="control-sidebar control-sidebar-dark">
    </aside>
</div>
<script src="public/adminlte/plugins/jquery/jquery.min.js"></script>
<script src="public/adminlte/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="public/adminlte/dist/js/adminlte.min.js"></script>
</body>
</html>
