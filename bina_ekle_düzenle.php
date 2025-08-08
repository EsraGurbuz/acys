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

$building_id = null;
$building_name = '';
$is_edit_mode = false;
$error_message = '';
$success_message = '';

// Turkish:
// Düzenleme modunda mı kontrol et
// English:
// Check if in edit mode
if (isset($_GET['id'])) {
    $building_id = $_GET['id'];
    $is_edit_mode = true;
    try {
        $stmt = $pdo->prepare("SELECT building_name FROM Buildings WHERE building_id = ?");
        $stmt->execute([$building_id]);
        $building = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($building) {
            $building_name = $building['building_name'];
        } else {
            // Bina bulunamazsa listeye geri dön
            // If building not found, redirect to the list
            header('Location: binalar.php');
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
    $building_name = $_POST['building_name'];
    $building_id = $_POST['building_id'] ?? null;

    if (empty($building_name)) {
        $error_message = "Bina adı boş bırakılamaz.";
    } else {
        if ($building_id) {
            // Düzenleme işlemi
            // Update operation
            try {
                $stmt = $pdo->prepare("UPDATE Buildings SET building_name = ? WHERE building_id = ?");
                $stmt->execute([$building_name, $building_id]);
                $success_message = "Bina başarıyla güncellendi.";
            } catch (PDOException $e) {
                $error_message = "Güncelleme sırasında bir hata oluştu: " . $e->getMessage();
            }
        } else {
            // Ekleme işlemi
            // Create operation
            try {
                $stmt = $pdo->prepare("INSERT INTO Buildings (building_name) VALUES (?)");
                $stmt->execute([$building_name]);
                $success_message = "Bina başarıyla eklendi.";
            } catch (PDOException $e) {
                // Tekrar eden bina adı hatası
                // Duplicate building name error
                if ($e->getCode() === '23000') {
                    $error_message = "Bu bina adı zaten mevcut.";
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
    <title>ACYS - Bina Yönetimi</title>

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
                        <h1><?php echo $is_edit_mode ? 'Bina Düzenle' : 'Bina Ekle'; ?></h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="index.php">ACYS</a></li>
                            <li class="breadcrumb-item"><a href="binalar.php">Bina Yönetimi</a></li>
                            <li class="breadcrumb-item active"><?php echo $is_edit_mode ? 'Bina Düzenle' : 'Bina Ekle'; ?></li>
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
                        <h3 class="card-title"><?php echo $is_edit_mode ? 'Bina Bilgilerini Düzenle' : 'Yeni Bina Ekle'; ?></h3>
                    </div>
                    <form action="bina_ekle_duzenle.php" method="post">
                        <div class="card-body">
                            <div class="form-group">
                                <label for="building_name">Bina Adı</label>
                                <input type="text" name="building_name" class="form-control" id="building_name" placeholder="Bina adı giriniz" value="<?php echo htmlspecialchars($building_name); ?>" required>
                            </div>
                        </div>
                        <div class="card-footer">
                            <?php if ($is_edit_mode): ?>
                                <input type="hidden" name="building_id" value="<?php echo htmlspecialchars($building_id); ?>">
                                <button type="submit" class="btn btn-primary">Güncelle</button>
                            <?php else: ?>
                                <button type="submit" class="btn btn-primary">Ekle</button>
                            <?php endif; ?>
                            <a href="binalar.php" class="btn btn-default">Geri Dön</a>
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