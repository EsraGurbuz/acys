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

}

$user_id = null;
$username = '';
$role = 'user'; // Varsayılan yetki
$is_edit_mode = false;
$error_message = '';
$success_message = '';

// Turkish:
// Düzenleme modunda mı kontrol et
// English:
// Check if in edit mode
if (isset($_GET['id'])) {
    $user_id = $_GET['id'];
    $is_edit_mode = true;
    try {
        $stmt = $pdo->prepare("SELECT user_id, username, role FROM Users WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user) {
            $username = $user['username'];
            $role = $user['role'];
        } else {
            header('Location: kullanicilar.php'); // Kullanıcı bulunamazsa listeye geri dön
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
    $username = $_POST['username'];
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'];
    $user_id = $_POST['user_id'] ?? null;

    if (empty($username) || (!$is_edit_mode && empty($password))) {
        $error_message = "Kullanıcı adı ve şifre boş bırakılamaz.";
    } else {
        if ($user_id) {
            // Düzenleme işlemi
            $sql = "UPDATE Users SET username = ?, role = ? WHERE user_id = ?";
            $params = [$username, $role, $user_id];
            if (!empty($password)) {
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                $sql = "UPDATE Users SET username = ?, password = ?, role = ? WHERE user_id = ?";
                $params = [$username, $password_hash, $role, $user_id];
            }
            try {
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                $success_message = "Kullanıcı başarıyla güncellendi.";
            } catch (PDOException $e) {
                if ($e->getCode() === '23000') {
                    $error_message = "Bu kullanıcı adı zaten mevcut.";
                } else {
                    $error_message = "Güncelleme sırasında bir hata oluştu: " . $e->getMessage();
                }
            }
        } else {
            // Ekleme işlemi
            try {
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO Users (username, password, role) VALUES (?, ?, ?)");
                $stmt->execute([$username, $password_hash, $role]);
                $success_message = "Kullanıcı başarıyla eklendi.";
            } catch (PDOException $e) {
                if ($e->getCode() === '23000') {
                    $error_message = "Bu kullanıcı adı zaten mevcut.";
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
                        <h1><?php echo $is_edit_mode ? 'Kullanıcı Düzenle' : 'Kullanıcı Ekle'; ?></h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="index.php">ACYS</a></li>
                            <li class="breadcrumb-item"><a href="kullanicilar.php">Kullanıcı Yönetimi</a></li>
                            <li class="breadcrumb-item active"><?php echo $is_edit_mode ? 'Düzenle' : 'Ekle'; ?></li>
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
                        <h3 class="card-title"><?php echo $is_edit_mode ? 'Kullanıcıyı Düzenle' : 'Yeni Kullanıcı Ekle'; ?></h3>
                    </div>
                    <form action="kullanici_ekle_duzenle.php" method="post">
                        <div class="card-body">
                            <div class="form-group">
                                <label for="username">Kullanıcı Adı</label>
                                <input type="text" name="username" class="form-control" id="username" placeholder="Kullanıcı adı giriniz" value="<?php echo htmlspecialchars($username); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="password">Şifre <?php echo $is_edit_mode ? '(Değiştirmek istemiyorsanız boş bırakın)' : ''; ?></label>
                                <input type="password" name="password" class="form-control" id="password" placeholder="Şifre giriniz" <?php echo $is_edit_mode ? '' : 'required'; ?>>
                            </div>
                            <div class="form-group">
                                <label for="role">Yetki</label>
                                <select name="role" id="role" class="form-control">
                                    <option value="admin" <?php echo ($role === 'admin') ? 'selected' : ''; ?>>Admin</option>
                                    <option value="user" <?php echo ($role === 'user') ? 'selected' : ''; ?>>User</option>
                                </select>
                            </div>
                        </div>
                        <div class="card-footer">
                            <?php if ($is_edit_mode): ?>
                                <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($user_id); ?>">
                                <button type="submit" class="btn btn-primary">Güncelle</button>
                            <?php else: ?>
                                <button type="submit" class="btn btn-primary">Ekle</button>
                            <?php endif; ?>
                            <a href="kullanicilar.php" class="btn btn-default">Geri Dön</a>
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
