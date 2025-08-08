<?php
// Turkish:
// Oturum başlatma ve veritabanı bağlantısı
// English:
// Start session and include database connection
session_start();
require_once 'config/db_config.php';

// Turkish:
// Giriş ve yetki kontrolü
// English:
// Check login and authorization
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

$error_message = '';

// Turkish:
// Silme işlemi
// English:
// Delete operation
if (isset($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];
    try {
        $stmt = $pdo->prepare("DELETE FROM Users WHERE user_id = ?");
        $stmt->execute([$delete_id]);
        header('Location: kullanicilar.php'); // Silme sonrası sayfayı yeniden yükle
        exit();
    } catch (PDOException $e) {
        $error_message = "Silme işlemi sırasında bir hata oluştu: " . $e->getMessage();
    }
}

// Turkish:
// Veritabanından kullanıcıları çek
// English:
// Fetch users from the database
try {
    $stmt = $pdo->query("SELECT user_id, username, role FROM Users ORDER BY username ASC");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Veritabanı hatası: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ACYS - Kullanıcı Yönetimi</title>

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
                        <h1>Kullanıcı Yönetimi</h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="index.php">ACYS</a></li>
                            <li class="breadcrumb-item active">Kullanıcı Yönetimi</li>
                        </ol>
                    </div>
                </div>
            </div>
        </section>

        <section class="content">
            <div class="container-fluid">
                <?php if (isset($error_message) && $error_message): ?>
                    <div class="alert alert-danger" role="alert">
                        <?php echo $error_message; ?>
                    </div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Kullanıcı Listesi</h3>
                        <div class="card-tools">
                            <a href="kullanici_ekle_duzenle.php" class="btn btn-success btn-sm">
                                <i class="fas fa-plus"></i> Kullanıcı Ekle
                            </a>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-striped projects">
                            <thead>
                            <tr>
                                <th style="width: 10%">#</th>
                                <th style="width: 40%">Kullanıcı Adı</th>
                                <th style="width: 20%">Yetki</th>
                                <th style="width: 30%">İşlemler</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php if (empty($users)): ?>
                                <tr>
                                    <td colspan="4">Henüz hiç kullanıcı eklenmedi.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($users as $user): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($user['user_id']); ?></td>
                                        <td><?php echo htmlspecialchars($user['username']); ?></td>
                                        <td><?php echo htmlspecialchars($user['role']); ?></td>
                                        <td class="project-actions">
                                            <a class="btn btn-info btn-sm" href="kullanici_ekle_duzenle.php?id=<?php echo htmlspecialchars($user['user_id']); ?>">
                                                <i class="fas fa-pencil-alt"></i> Düzenle
                                            </a>
                                            <?php if ($user['user_id'] != $_SESSION['user_id']): // Kullanıcı kendini silemez ?>
                                                <a class="btn btn-danger btn-sm" href="kullanicilar.php?delete_id=<?php echo htmlspecialchars($user['user_id']); ?>" onclick="return confirm('Bu kullanıcıyı silmek istediğinizden emin misiniz? Bu işlem geri alınamaz.');">
                                                    <i class="fas fa-trash"></i> Sil
                                                </a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </section>
    </div>
    <?php include 'partials/footer.php'; ?>
</div>

<script src="public/adminlte/plugins/jquery/jquery.min.js"></script>
<script src="public/adminlte/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="public/adminlte/dist/js/adminlte.min.js"></script>
</body>
</html>
