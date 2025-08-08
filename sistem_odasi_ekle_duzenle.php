<?php
// Turkish:
// Oturum başlatma ve veritabanı bağlantısı
// English:
// Start session and include database connection
session_start();
require_once 'config/db_config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

$room_id = null;
$floor_id = $_GET['floor_id'] ?? null;
$room_name = '';
$floor_name = '';
$building_name = '';
$building_id = '';
$is_edit_mode = false;
$error_message = '';
$success_message = '';

if (!$floor_id) {
    header('Location: binalar.php'); // Kat ID'si yoksa binalar sayfasına geri dön
    exit();
}

// Turkish:
// Kat ve bağlı olduğu bina adını çek
// English:
// Fetch the floor and its associated building name
try {
    $stmt = $pdo->prepare("SELECT f.floor_name, b.building_name, b.building_id FROM Floors f INNER JOIN Buildings b ON f.building_id = b.building_id WHERE f.floor_id = ?");
    $stmt->execute([$floor_id]);
    $floor_info = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($floor_info) {
        $floor_name = $floor_info['floor_name'];
        $building_name = $floor_info['building_name'];
        $building_id = $floor_info['building_id'];
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
    $room_id = $_GET['id'];
    $is_edit_mode = true;
    try {
        $stmt = $pdo->prepare("SELECT room_name FROM System_Rooms WHERE room_id = ? AND floor_id = ?");
        $stmt->execute([$room_id, $floor_id]);
        $room = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($room) {
            $room_name = $room['room_name'];
        } else {
            header('Location: sistem_odalari.php?floor_id=' . $floor_id); // Sistem odası bulunamazsa listeye geri dön
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
    $room_name = $_POST['room_name'];
    $room_id = $_POST['room_id'] ?? null;
    $floor_id = $_POST['floor_id'];

    if (empty($room_name)) {
        $error_message = "Sistem odası adı boş bırakılamaz.";
    } else {
        if ($room_id) {
            // Düzenleme işlemi
            // Update operation
            try {
                $stmt = $pdo->prepare("UPDATE System_Rooms SET room_name = ? WHERE room_id = ? AND floor_id = ?");
                $stmt->execute([$room_name, $room_id, $floor_id]);
                $success_message = "Sistem odası başarıyla güncellendi.";
            } catch (PDOException $e) {
                $error_message = "Güncelleme sırasında bir hata oluştu: " . $e->getMessage();
            }
        } else {
            // Ekleme işlemi
            // Create operation
            try {
                $stmt = $pdo->prepare("INSERT INTO System_Rooms (room_name, floor_id) VALUES (?, ?)");
                $stmt->execute([$room_name, $floor_id]);
                $success_message = "Sistem odası başarıyla eklendi.";
            } catch (PDOException $e) {
                $error_message = "Ekleme sırasında bir hata oluştu: " . $e->getMessage();
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
    <title>ACYS - Sistem Odası Yönetimi</title>

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
                        <h1><?php echo $is_edit_mode ? 'Sistem Odası Düzenle' : 'Sistem Odası Ekle'; ?></h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="index.php">ACYS</a></li>
                            <li class="breadcrumb-item"><a href="binalar.php">Bina Yönetimi</a></li>
                            <li class="breadcrumb-item"><a href="katlar.php?building_id=<?php echo htmlspecialchars($building_id); ?>">Kat Yönetimi</a></li>
                            <li class="breadcrumb-item active"><?php echo $is_edit_mode ? 'Sistem Odası Düzenle' : 'Sistem Odası Ekle'; ?></li>
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
                        <h3 class="card-title"><?php echo htmlspecialchars($floor_name); ?> - <?php echo $is_edit_mode ? 'Sistem Odası Düzenle' : 'Sistem Odası Ekle'; ?></h3>
                    </div>
                    <form action="sistem_odasi_ekle_duzenle.php?floor_id=<?php echo htmlspecialchars($floor_id); ?>" method="post">
                        <div class="card-body">
                            <div class="form-group">
                                <label for="room_name">Sistem Odası Adı</label>
                                <input type="text" name="room_name" class="form-control" id="room_name" placeholder="Sistem odası adı giriniz" value="<?php echo htmlspecialchars($room_name); ?>" required>
                            </div>
                        </div>
                        <div class="card-footer">
                            <?php if ($is_edit_mode): ?>
                                <input type="hidden" name="room_id" value="<?php echo htmlspecialchars($room_id); ?>">
                                <input type="hidden" name="floor_id" value="<?php echo htmlspecialchars($floor_id); ?>">
                                <button type="submit" class="btn btn-primary">Güncelle</button>
                            <?php else: ?>
                                <input type="hidden" name="floor_id" value="<?php echo htmlspecialchars($floor_id); ?>">
                                <button type="submit" class="btn btn-primary">Ekle</button>
                            <?php endif; ?>
                            <a href="sistem_odalari.php?floor_id=<?php echo htmlspecialchars($floor_id); ?>" class="btn btn-default">Geri Dön</a>
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
