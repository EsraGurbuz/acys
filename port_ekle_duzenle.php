<?php
// Turkish:
// Oturum başlatma ve veritabanı bağlantısı
// English:
// Start session and include database connection
session_start();
require_once 'config/db_config.php';

// Yetkilendirme kontrolü: Sadece adminler erişebilir
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}


$port_id = $_GET['id'] ?? null;
$switch_id = $_GET['switch_id'] ?? null;
$port_number = '';
$device_name = '';
$mac_address = '';
$switch_model = '';
$room_name = '';
$floor_name = '';
$building_name = '';
$building_id = '';
$floor_id = '';
$room_id = '';
$error_message = '';
$success_message = '';

if (!$port_id || !$switch_id) {
    header('Location: binalar.php'); // Port veya anahtar ID'si yoksa binalar sayfasına geri dön
    exit();
}

// Turkish:
// Port ve bağlı olduğu anahtar, sistem odası, kat, bina adını çek
// English:
// Fetch the port and its associated switch, system room, floor, and building name
try {
    $stmt = $pdo->prepare("SELECT p.port_number, p.device_name, p.mac_address, s.switch_model, sr.room_name, f.floor_name, b.building_name, b.building_id, f.floor_id, sr.room_id FROM Ports p INNER JOIN Switches s ON p.switch_id = s.switch_id INNER JOIN System_Rooms sr ON s.room_id = sr.room_id INNER JOIN Floors f ON sr.floor_id = f.floor_id INNER JOIN Buildings b ON f.building_id = b.building_id WHERE p.port_id = ? AND p.switch_id = ?");
    $stmt->execute([$port_id, $switch_id]);
    $port_info = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($port_info) {
        $port_number = $port_info['port_number'];
        $device_name = $port_info['device_name'];
        $mac_address = $port_info['mac_address'];
        $switch_model = $port_info['switch_model'];
        $room_name = $port_info['room_name'];
        $floor_name = $port_info['floor_name'];
        $building_name = $port_info['building_name'];
        $building_id = $port_info['building_id'];
        $floor_id = $port_info['floor_id'];
        $room_id = $port_info['room_id'];
    } else {
        header('Location: switches.php?room_id=' . $room_id); // Port bulunamazsa anahtarlar sayfasına geri dön
        exit();
    }
} catch (PDOException $e) {
    die("Veritabanı hatası: " . $e->getMessage());
}

// Turkish:
// Form gönderimi işlemleri
// English:
// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $device_name = $_POST['device_name'] ?? '';
    $mac_address = $_POST['mac_address'] ?? '';
    $port_id = $_POST['port_id'];
    $switch_id = $_POST['switch_id'];

    if (empty($device_name) || empty($mac_address)) {
        $error_message = "Tüm alanlar doldurulmalıdır.";
    } elseif (!preg_match('/^([0-9A-Fa-f]{2}[:-]){5}([0-9A-Fa-f]{2})$/', $mac_address)) {
        $error_message = "Geçerli bir MAC adresi giriniz. (örn: 00:1A:2B:3C:4D:5E)";
    } else {
        // Düzenleme işlemi
        // Update operation
        try {
            $stmt = $pdo->prepare("UPDATE Ports SET device_name = ?, mac_address = ? WHERE port_id = ? AND switch_id = ?");
            $stmt->execute([$device_name, $mac_address, $port_id, $switch_id]);
            $success_message = "Port başarıyla güncellendi.";
        } catch (PDOException $e) {
            $error_message = "Güncelleme sırasında bir hata oluştu: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ACYS - Port Düzenle</title>

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
                        <h1>Port Düzenle</h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="index.php">ACYS</a></li>
                            <li class="breadcrumb-item"><a href="binalar.php">Bina Yönetimi</a></li>
                            <li class="breadcrumb-item"><a href="katlar.php?building_id=<?php echo htmlspecialchars($building_id); ?>">Kat Yönetimi</a></li>
                            <li class="breadcrumb-item"><a href="sistem_odalari.php?floor_id=<?php echo htmlspecialchars($floor_id); ?>">Sistem Odaları</a></li>
                            <li class="breadcrumb-item"><a href="switches.php?room_id=<?php echo htmlspecialchars($room_id); ?>">Anahtarlar</a></li>
                            <li class="breadcrumb-item active">Port Düzenle</li>
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
                <?php elseif (isset($success_message) && $success_message): ?>
                    <div class="alert alert-success" role="alert">
                        <?php echo $success_message; ?>
                    </div>
                <?php endif; ?>

                <div class="card card-primary">
                    <div class="card-header">
                        <h3 class="card-title"><?php echo htmlspecialchars($switch_model); ?> - Port #<?php echo htmlspecialchars($port_number); ?> Düzenle</h3>
                    </div>
                    <form action="port_ekle_duzenle.php?id=<?php echo htmlspecialchars($port_id); ?>&switch_id=<?php echo htmlspecialchars($switch_id); ?>" method="post">
                        <div class="card-body">
                            <div class="form-group">
                                <label for="device_name">Bağlı Cihaz Adı</label>
                                <input type="text" name="device_name" class="form-control" id="device_name" placeholder="Bağlı cihazın adını giriniz" value="<?php echo htmlspecialchars($device_name); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="mac_address">Cihaz MAC Adresi</label>
                                <input type="text" name="mac_address" class="form-control" id="mac_address" placeholder="MAC adresi giriniz (örn: 00:1A:2B:3C:4D:5E)" value="<?php echo htmlspecialchars($mac_address); ?>" required>
                            </div>
                        </div>
                        <div class="card-footer">
                            <input type="hidden" name="port_id" value="<?php echo htmlspecialchars($port_id); ?>">
                            <input type="hidden" name="switch_id" value="<?php echo htmlspecialchars($switch_id); ?>">
                            <button type="submit" class="btn btn-primary">Güncelle</button>
                            <a href="ports.php?switch_id=<?php echo htmlspecialchars($switch_id); ?>" class="btn btn-default">Geri Dön</a>
                        </div>
                    </form>
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

