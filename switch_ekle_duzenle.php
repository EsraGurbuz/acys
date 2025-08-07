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


$switch_id = null;
$room_id = $_GET['room_id'] ?? null;
$switch_model = '';
$port_count = '';
$ip_address = '';
$room_name = '';
$floor_name = '';
$building_name = '';
$building_id = '';
$floor_id = '';
$is_edit_mode = false;
$error_message = '';
$success_message = '';

if (!$room_id) {
    header('Location: binalar.php'); // Sistem odası ID'si yoksa binalar sayfasına geri dön
    exit();
}

// Turkish:
// Sistem odası ve bağlı olduğu kat, bina adını çek
// English:
// Fetch the system room and its associated floor, building name
try {
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
        header('Location: binalar.php');
        exit();
    }
} catch (PDOException $e) {
    die("Veritabanı hatası: " . $e->getMessage());
}

// Turkish:
// Düzenleme modunda mı kontrol et
// English:
// Check if in edit mode
if (isset($_GET['id'])) {
    $switch_id = $_GET['id'];
    $is_edit_mode = true;
    try {
        $stmt = $pdo->prepare("SELECT * FROM Switches WHERE switch_id = ? AND room_id = ?");
        $stmt->execute([$switch_id, $room_id]);
        $switch = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($switch) {
            $switch_model = $switch['switch_model'];
            $port_count = $switch['port_count'];
            $ip_address = $switch['ip_address'];
        } else {
            header('Location: switches.php?room_id=' . $room_id); // Anahtar bulunamazsa listeye geri dön
            exit();
        }
    } catch (PDOException $e) {
        die("Veritabanı hatası: " . $e->getMessage());
    }
}

// Turkish:
// Form gönderimi işlemleri
// English:
// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $switch_model = $_POST['switch_model'];
    $port_count = $_POST['port_count'];
    $ip_address = $_POST['ip_address'];
    $switch_id = $_POST['switch_id'] ?? null;
    $room_id = $_POST['room_id'];

    if (empty($switch_model) || empty($port_count) || empty($ip_address)) {
        $error_message = "Tüm alanlar doldurulmalıdır.";
    } elseif (!filter_var($ip_address, FILTER_VALIDATE_IP)) {
        $error_message = "Geçerli bir IP adresi giriniz.";
    } elseif (!is_numeric($port_count) || $port_count < 1) {
        $error_message = "Port sayısı geçerli bir sayı olmalıdır.";
    } else {
        if ($switch_id) {
            // Düzenleme işlemi
            // Update operation
            try {
                $stmt = $pdo->prepare("UPDATE Switches SET switch_model = ?, port_count = ?, ip_address = ? WHERE switch_id = ? AND room_id = ?");
                $stmt->execute([$switch_model, $port_count, $ip_address, $switch_id, $room_id]);
                $success_message = "Anahtar başarıyla güncellendi.";
            } catch (PDOException $e) {
                if ($e->getCode() === '23000') {
                    $error_message = "Bu IP adresi zaten mevcut.";
                } else {
                    $error_message = "Güncelleme sırasında bir hata oluştu: " . $e->getMessage();
                }
            }
        } else {
            // Ekleme işlemi
            // Create operation
            try {
                $stmt = $pdo->prepare("INSERT INTO Switches (switch_model, port_count, ip_address, room_id) VALUES (?, ?, ?, ?)");
                $stmt->execute([$switch_model, $port_count, $ip_address, $room_id]);
                $success_message = "Anahtar başarıyla eklendi.";
            } catch (PDOException $e) {
                if ($e->getCode() === '23000') {
                    $error_message = "Bu IP adresi zaten mevcut.";
                } else {
                    $error_message = "Ekleme sırasında bir hata oluştu: " . $e->getMessage();
                }
            }
        }
    }
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
                        <h1><?php echo $is_edit_mode ? 'Anahtar Düzenle' : 'Anahtar Ekle'; ?></h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="index.php">ACYS</a></li>
                            <li class="breadcrumb-item"><a href="binalar.php">Bina Yönetimi</a></li>
                            <li class="breadcrumb-item"><a href="katlar.php?building_id=<?php echo htmlspecialchars($building_id); ?>">Kat Yönetimi</a></li>
                            <li class="breadcrumb-item"><a href="sistem_odalari.php?floor_id=<?php echo htmlspecialchars($floor_id); ?>">Sistem Odaları</a></li>
                            <li class="breadcrumb-item active"><?php echo $is_edit_mode ? 'Anahtar Düzenle' : 'Anahtar Ekle'; ?></li>
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
                        <h3 class="card-title"><?php echo htmlspecialchars($room_name); ?> - <?php echo $is_edit_mode ? 'Anahtar Düzenle' : 'Anahtar Ekle'; ?></h3>
                    </div>
                    <form action="switch_ekle_duzenle.php?room_id=<?php echo htmlspecialchars($room_id); ?>" method="post">
                        <div class="card-body">
                            <div class="form-group">
                                <label for="switch_model">Model</label>
                                <input type="text" name="switch_model" class="form-control" id="switch_model" placeholder="Anahtar modeli giriniz" value="<?php echo htmlspecialchars($switch_model); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="port_count">Port Sayısı</label>
                                <input type="number" name="port_count" class="form-control" id="port_count" placeholder="Port sayısı giriniz" value="<?php echo htmlspecialchars($port_count); ?>" required min="1">
                            </div>
                            <div class="form-group">
                                <label for="ip_address">IP Adresi</label>
                                <input type="text" name="ip_address" class="form-control" id="ip_address" placeholder="IP adresi giriniz (örn: 192.168.1.1)" value="<?php echo htmlspecialchars($ip_address); ?>" required>
                            </div>
                        </div>
                        <div class="card-footer">
                            <?php if ($is_edit_mode): ?>
                                <input type="hidden" name="switch_id" value="<?php echo htmlspecialchars($switch_id); ?>">
                                <input type="hidden" name="room_id" value="<?php echo htmlspecialchars($room_id); ?>">
                                <button type="submit" class="btn btn-primary">Güncelle</button>
                            <?php else: ?>
                                <input type="hidden" name="room_id" value="<?php echo htmlspecialchars($room_id); ?>">
                                <button type="submit" class="btn btn-primary">Ekle</button>
                            <?php endif; ?>
                            <a href="switches.php?room_id=<?php echo htmlspecialchars($room_id); ?>" class="btn btn-default">Geri Dön</a>
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
