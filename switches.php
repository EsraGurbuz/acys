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

$room_id = $_GET['room_id'] ?? null;
$room_name = '';
$floor_name = '';
$building_name = '';
$building_id = '';
$floor_id = '';

if ($room_id) {
    try {
        // Turkish:
        // Sistem odası ve bağlı olduğu kat, bina adını çek
        // English:
        // Fetch the system room and its associated floor, building name
        $stmt = $pdo->prepare("SELECT sr.room_name, f.floor_name, b.building_name, b.building_id, f.floor_id FROM System_Rooms sr INNER JOIN Floors f ON sr.floor_id = f.floor_id INNER JOIN Buildings b ON f.building_id = b.building_id WHERE sr.room_id = ?");
        $stmt->execute([$room_id]);
        $room_info = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($room_info) {
            $room_name = $room_info['room_name'];
            $floor_name = $room_info['floor_name'];
            $building_name = $room_info['building_name'];
            $building_id = $room_info['building_id'];
            $floor_id = $room_info['floor_id'];
        } else {
            header('Location: binalar.php'); // Sistem odası bulunamazsa binalar sayfasına geri dön
            exit();
        }

        // Turkish:
        // Silme işlemi
        // English:
        // Delete operation
        if (isset($_GET['delete_id']) && $_SESSION['role'] === 'admin') {
            $delete_id = $_GET['delete_id'];
            try {
                $stmt = $pdo->prepare("DELETE FROM Switches WHERE switch_id = ? AND room_id = ?");
                $stmt->execute([$delete_id, $room_id]);
                header('Location: switches.php?room_id=' . $room_id); // Silme sonrası sayfayı yeniden yükle
                exit();
            } catch (PDOException $e) {
                $error_message = "Silme işlemi sırasında bir hata oluştu: " . $e->getMessage();
            }
        }

        // Turkish:
        // Veritabanından anahtarları çek
        // English:
        // Fetch switches from the database
        $stmt = $pdo->prepare("SELECT * FROM Switches WHERE room_id = ? ORDER BY ip_address ASC");
        $stmt->execute([$room_id]);
        $switches = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        die("Veritabanı hatası: " . $e->getMessage());
    }
} else {
    header('Location: binalar.php'); // Sistem odası ID'si yoksa binalar sayfasına geri dön
    exit();
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ACYS - Anahtar Yönetimi</title>

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
                        <h1><?php echo htmlspecialchars($room_name); ?> - Anahtar Yönetimi</h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="index.php">ACYS</a></li>
                            <li class="breadcrumb-item"><a href="binalar.php">Bina Yönetimi</a></li>
                            <li class="breadcrumb-item"><a href="katlar.php?building_id=<?php echo htmlspecialchars($building_id); ?>">Kat Yönetimi</a></li>
                            <li class="breadcrumb-item"><a href="sistem_odalari.php?floor_id=<?php echo htmlspecialchars($floor_id); ?>">Sistem Odaları</a></li>
                            <li class="breadcrumb-item active">Anahtar Yönetimi</li>
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
                        <h3 class="card-title">Anahtar Listesi</h3>
                        <div class="card-tools">
                            <a href="switch_ekle_duzenle.php?room_id=<?php echo htmlspecialchars($room_id); ?>" class="btn btn-success btn-sm">
                                <i class="fas fa-plus"></i> Anahtar Ekle
                            </a>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-striped projects">
                            <thead>
                            <tr>
                                <th style="width: 10%">#</th>
                                <th style="width: 20%">Model</th>
                                <th style="width: 10%">Port Sayısı</th>
                                <th style="width: 20%">IP Adresi</th>
                                <th style="width: 40%">İşlemler</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php if (empty($switches)): ?>
                                <tr>
                                    <td colspan="5">Bu sistem odasına henüz hiç anahtar eklenmedi.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($switches as $switch): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($switch['switch_id']); ?></td>
                                        <td><?php echo htmlspecialchars($switch['switch_model']); ?></td>
                                        <td><?php echo htmlspecialchars($switch['port_count']); ?></td>
                                        <td><?php echo htmlspecialchars($switch['ip_address']); ?></td>
                                        <td class="project-actions">
                                            <a class="btn btn-secondary btn-sm" href="ports.php?switch_id=<?php echo htmlspecialchars($switch['switch_id']); ?>">
                                                <i class="fas fa-plug"></i> Portları Görüntüle
                                            </a>
                                            <a class="btn btn-info btn-sm" href="switch_ekle_duzenle.php?id=<?php echo htmlspecialchars($switch['switch_id']); ?>&room_id=<?php echo htmlspecialchars($room_id); ?>">
                                                <i class="fas fa-pencil-alt"></i> Düzenle
                                            </a>
                                            <a class="btn btn-danger btn-sm" href="switches.php?delete_id=<?php echo htmlspecialchars($switch['switch_id']); ?>&room_id=<?php echo htmlspecialchars($room_id); ?>" onclick="return confirm('Bu anahtarı silmek istediğinizden emin misiniz? Bu işlem geri alınamaz.');">
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