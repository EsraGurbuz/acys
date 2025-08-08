<?php
// Turkish:
// Oturum başlatma ve veritabanı bağlantısı
// English:
// Start session and include database connection
session_start();
require_once 'config/db_config.php';

// Turkish:
// Giriş kontrolü, giriş yapılmamışsa kullanıcıyı login sayfasına yönlendirir.
// English:
// Check if user is logged in, redirect to login page if not.
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$floor_id = $_GET['floor_id'] ?? null;
$floor_name = '';
$building_id = '';
$building_name = '';

if ($floor_id) {
    try {
        // Turkish:
        // Kat ve bağlı olduğu bina adını çek
        // English:
        // Fetch the floor and its associated building name
        $stmt = $pdo->prepare("SELECT f.floor_name, b.building_name, b.building_id FROM Floors f INNER JOIN Buildings b ON f.building_id = b.building_id WHERE f.floor_id = ?");
        $stmt->execute([$floor_id]);
        $floor_info = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($floor_info) {
            $floor_name = $floor_info['floor_name'];
            $building_name = $floor_info['building_name'];
            $building_id = $floor_info['building_id'];
        } else {
            header('Location: binalar.php'); // Kat bulunamazsa binalar sayfasına geri dön
            exit();
        }

        // Turkish:
        // Silme işlemi
        // English:
        // Delete operation
        if (isset($_GET['delete_id']) && $_SESSION['role'] === 'admin') {
            $delete_id = $_GET['delete_id'];
            try {
                $stmt = $pdo->prepare("DELETE FROM System_Rooms WHERE room_id = ? AND floor_id = ?");
                $stmt->execute([$delete_id, $floor_id]);
                header('Location: sistem_odalari.php?floor_id=' . $floor_id); // Silme sonrası sayfayı yeniden yükle
                exit();
            } catch (PDOException $e) {
                $error_message = "Silme işlemi sırasında bir hata oluştu: " . $e->getMessage();
            }
        }

        // Turkish:
        // Veritabanından sistem odalarını çek
        // English:
        // Fetch system rooms from the database
        $stmt = $pdo->prepare("SELECT * FROM System_Rooms WHERE floor_id = ? ORDER BY room_name ASC");
        $stmt->execute([$floor_id]);
        $system_rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        die("Veritabanı hatası: " . $e->getMessage());
    }
} else {
    header('Location: binalar.php'); // Kat ID'si yoksa binalar sayfasına geri dön
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ACYS - Sistem Odaları Yönetimi</title>

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
                        <h1><?php echo htmlspecialchars($building_name); ?> - <?php echo htmlspecialchars($floor_name); ?> - Sistem Odaları</h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="index.php">ACYS</a></li>
                            <li class="breadcrumb-item"><a href="binalar.php">Bina Yönetimi</a></li>
                            <li class="breadcrumb-item"><a href="katlar.php?building_id=<?php echo htmlspecialchars($building_id); ?>">Kat Yönetimi</a></li>
                            <li class="breadcrumb-item active">Sistem Odaları</li>
                        </ol>
                    </div>
                </div>
            </div>
        </section>

        <section class="content">
            <div class="container-fluid">
                <?php if (isset($error_message)): ?>
                    <div class="alert alert-danger" role="alert">
                        <?php echo $error_message; ?>
                    </div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Sistem Odası Listesi</h3>
                        <div class="card-tools">
                            <a href="sistem_odasi_ekle_duzenle.php?floor_id=<?php echo htmlspecialchars($floor_id); ?>" class="btn btn-success btn-sm">
                                <i class="fas fa-plus"></i> Sistem Odası Ekle
                            </a>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-striped projects">
                            <thead>
                            <tr>
                                <th style="width: 10%">#</th>
                                <th style="width: 50%">Sistem Odası Adı</th>
                                <th style="width: 40%">İşlemler</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php if (empty($system_rooms)): ?>
                                <tr>
                                    <td colspan="3">Bu kata henüz hiç sistem odası eklenmedi.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($system_rooms as $room): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($room['room_id']); ?></td>
                                        <td><?php echo htmlspecialchars($room['room_name']); ?></td>
                                        <td class="project-actions">
                                            <a class="btn btn-primary btn-sm" href="switches.php?room_id=<?php echo htmlspecialchars($room['room_id']); ?>">
                                                <i class="fas fa-network-wired"></i> Anahtarlar
                                            </a>
                                            <a class="btn btn-info btn-sm" href="sistem_odasi_ekle_duzenle.php?id=<?php echo htmlspecialchars($room['room_id']); ?>&floor_id=<?php echo htmlspecialchars($floor_id); ?>">
                                                <i class="fas fa-pencil-alt"></i> Düzenle
                                            </a>
                                            <a class="btn btn-danger btn-sm" href="sistem_odalari.php?delete_id=<?php echo htmlspecialchars($room['room_id']); ?>&floor_id=<?php echo htmlspecialchars($floor_id); ?>" onclick="return confirm('Bu sistem odasını silmek istediğinizden emin misiniz? Bu işlem geri alınamaz.');">
                                                <i class="fas fa-trash"></i> Sil
                                            </a>
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