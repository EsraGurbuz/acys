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

$switch_id = $_GET['switch_id'] ?? null;
$switch_model = '';
$room_name = '';
$floor_name = '';
$building_name = '';
$building_id = '';
$floor_id = '';
$room_id = '';

if ($switch_id) {
    try {
        // Turkish:
        // Anahtar ve bağlı olduğu sistem odası, kat, bina adını çek
        // English:
        // Fetch the switch and its associated system room, floor, and building name
        $stmt = $pdo->prepare("SELECT s.switch_model, sr.room_name, f.floor_name, b.building_name, b.building_id, f.floor_id, sr.room_id FROM Switches s INNER JOIN System_Rooms sr ON s.room_id = sr.room_id INNER JOIN Floors f ON sr.floor_id = f.floor_id INNER JOIN Buildings b ON f.building_id = b.building_id WHERE s.switch_id = ?");
        $stmt->execute([$switch_id]);
        $switch_info = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($switch_info) {
            $switch_model = $switch_info['switch_model'];
            $room_name = $switch_info['room_name'];
            $floor_name = $switch_info['floor_name'];
            $building_name = $switch_info['building_name'];
            $building_id = $switch_info['building_id'];
            $floor_id = $switch_info['floor_id'];
            $room_id = $switch_info['room_id'];
        } else {
            header('Location: binalar.php'); // Anahtar bulunamazsa binalar sayfasına geri dön
            exit();
        }

        // Turkish:
        // Veritabanından portları çek
        // English:
        // Fetch ports from the database
        $stmt = $pdo->prepare("SELECT * FROM Ports WHERE switch_id = ? ORDER BY port_number ASC");
        $stmt->execute([$switch_id]);
        $ports = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        die("Veritabanı hatası: " . $e->getMessage());
    }
} else {
    header('Location: binalar.php'); // Anahtar ID'si yoksa binalar sayfasına geri dön
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ACYS - Port Yönetimi</title>

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
                        <h1><?php echo htmlspecialchars($switch_model); ?> - Port Yönetimi</h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="index.php">ACYS</a></li>
                            <li class="breadcrumb-item"><a href="binalar.php">Bina Yönetimi</a></li>
                            <li class="breadcrumb-item"><a href="katlar.php?building_id=<?php echo htmlspecialchars($building_id); ?>">Kat Yönetimi</a></li>
                            <li class="breadcrumb-item"><a href="sistem_odalari.php?floor_id=<?php echo htmlspecialchars($floor_id); ?>">Sistem Odaları</a></li>
                            <li class="breadcrumb-item"><a href="switches.php?room_id=<?php echo htmlspecialchars($room_id); ?>">Anahtarlar</a></li>
                            <li class="breadcrumb-item active">Port Yönetimi</li>
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
                        <h3 class="card-title">Port Listesi</h3>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-striped projects">
                            <thead>
                            <tr>
                                <th style="width: 10%">#</th>
                                <th style="width: 20%">Port Numarası</th>
                                <th style="width: 25%">Cihaz Adı</th>
                                <th style="width: 25%">Cihaz MAC Adresi</th>
                                <th style="width: 20%">İşlemler</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php if (empty($ports)): ?>
                                <tr>
                                    <td colspan="5">Bu anahtara ait port bulunamadı.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($ports as $port): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($port['port_id']); ?></td>
                                        <td><?php echo htmlspecialchars($port['port_number']); ?></td>
                                        <td><?php echo htmlspecialchars($port['device_name']); ?></td>
                                        <td><?php echo htmlspecialchars($port['mac_address']); ?></td>
                                        <td class="project-actions">
                                            <a class="btn btn-info btn-sm" href="port_ekle_duzenle.php?id=<?php echo htmlspecialchars($port['port_id']); ?>&switch_id=<?php echo htmlspecialchars($switch_id); ?>">
                                                <i class="fas fa-pencil-alt"></i> Düzenle
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
