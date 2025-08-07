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


$floor_id = null;
$building_id = $_GET['building_id'] ?? null;
$floor_name = '';
$building_name = '';
$is_edit_mode = false;
$error_message = '';
$success_message = '';

if (!$building_id) {
    // Bina ID'si yoksa binalar sayfasına geri dön
    // If no building ID is provided, redirect to the buildings page
    header('Location: binalar.php');
    exit();
}

// Turkish:
// Bina adını çek
// English:
// Fetch the building name
try {
    $stmt = $pdo->prepare("SELECT building_name FROM Buildings WHERE building_id = ?");
    $stmt->execute([$building_id]);
    $building = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($building) {
        $building_name = $building['building_name'];
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
    $floor_id = $_GET['id'];
    $is_edit_mode = true;
    try {
        $stmt = $pdo->prepare("SELECT floor_name FROM Floors WHERE floor_id = ? AND building_id = ?");
        $stmt->execute([$floor_id, $building_id]);
        $floor = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($floor) {
            $floor_name = $floor['floor_name'];
        } else {
            // Kat bulunamazsa katlar sayfasına geri dön
            // If floor not found, redirect to the floors page
            header('Location: katlar.php?building_id=' . $building_id);
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
    $floor_name = $_POST['floor_name'];
    $floor_id = $_POST['floor_id'] ?? null;
    $building_id = $_POST['building_id'];

    if (empty($floor_name)) {
        $error_message = "Kat adı boş bırakılamaz.";
    } else {
        if ($floor_id) {
            // Düzenleme işlemi
            // Update operation
            try {
                $stmt = $pdo->prepare("UPDATE Floors SET floor_name = ? WHERE floor_id = ? AND building_id = ?");
                $stmt->execute([$floor_name, $floor_id, $building_id]);
                $success_message = "Kat başarıyla güncellendi.";
            } catch (PDOException $e) {
                $error_message = "Güncelleme sırasında bir hata oluştu: " . $e->getMessage();
            }
        } else {
            // Ekleme işlemi
            // Create operation
            try {
                $stmt = $pdo->prepare("INSERT INTO Floors (floor_name, building_id) VALUES (?, ?)");
                $stmt->execute([$floor_name, $building_id]);
                $success_message = "Kat başarıyla eklendi.";
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
    <title>ACYS - Kat Yönetimi</title>

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
                        <h1><?php echo $is_edit_mode ? 'Kat Düzenle' : 'Kat Ekle'; ?></h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="index.php">ACYS</a></li>
                            <li class="breadcrumb-item"><a href="binalar.php">Bina Yönetimi</a></li>
                            <li class="breadcrumb-item"><a href="katlar.php?building_id=<?php echo htmlspecialchars($building_id); ?>">Kat Yönetimi</a></li>
                            <li class="breadcrumb-item active"><?php echo $is_edit_mode ? 'Kat Düzenle' : 'Kat Ekle'; ?></li>
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
                        <h3 class="card-title"><?php echo htmlspecialchars($building_name); ?> - <?php echo $is_edit_mode ? 'Kat Düzenle' : 'Kat Ekle'; ?></h3>
                    </div>
                    <form action="kat_ekle_duzenle.php?building_id=<?php echo htmlspecialchars($building_id); ?>" method="post">
                        <div class="card-body">
                            <div class="form-group">
                                <label for="floor_name">Kat Adı</label>
                                <input type="text" name="floor_name" class="form-control" id="floor_name" placeholder="Kat adı giriniz" value="<?php echo htmlspecialchars($floor_name); ?>" required>
                            </div>
                        </div>
                        <div class="card-footer">
                            <?php if ($is_edit_mode): ?>
                                <input type="hidden" name="floor_id" value="<?php echo htmlspecialchars($floor_id); ?>">
                                <input type="hidden" name="building_id" value="<?php echo htmlspecialchars($building_id); ?>">
                                <button type="submit" class="btn btn-primary">Güncelle</button>
                            <?php else: ?>
                                <input type="hidden" name="building_id" value="<?php echo htmlspecialchars($building_id); ?>">
                                <button type="submit" class="btn btn-primary">Ekle</button>
                            <?php endif; ?>
                            <a href="katlar.php?building_id=<?php echo htmlspecialchars($building_id); ?>" class="btn btn-default">Geri Dön</a>
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
