<?php
session_start();
require_once 'config/db_config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$building_id = $_GET['building_id'] ?? null;
$building_name = '';
$error_message = '';

if ($building_id) {
    try {
        if (isset($_GET['delete_id']) && $_SESSION['role'] === 'admin') {
            $delete_id = $_GET['delete_id'];
            try {
                $stmt = $pdo->prepare("DELETE FROM Floors WHERE floor_id = ? AND building_id = ?");
                $stmt->execute([$delete_id, $building_id]);
                header('Location: katlar.php?building_id=' . $building_id);
                exit();
            } catch (PDOException $e) {
                $error_message = "Silme işlemi sırasında bir hata oluştu: " . $e->getMessage();
            }
        }

        $stmt = $pdo->prepare("SELECT * FROM Floors WHERE building_id = ? ORDER BY floor_name ASC");
        $stmt->execute([$building_id]);
        $floors = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmt = $pdo->prepare("SELECT building_name FROM Buildings WHERE building_id = ?");
        $stmt->execute([$building_id]);
        $building_info = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($building_info) {
            $building_name = $building_info['building_name'];
        } else {
            header('Location: binalar.php');
            exit();
        }
    } catch (PDOException $e) {
        die("Veritabanı hatası: " . $e->getMessage());
    }
} else {
    header('Location: binalar.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<body>
<div class="wrapper">
    <?php include 'partials/navbar.php'; ?>
    <?php include 'partials/sidebar.php'; ?>

    <div class="content-wrapper">
        <section class="content">
            <div class="container-fluid">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Kat Listesi</h3>
                        <div class="card-tools">
                            <?php if ($_SESSION['role'] === 'admin'): ?>
                                <a href="kat_ekle_duzenle.php?building_id=<?php echo htmlspecialchars($building_id); ?>" class="btn btn-success btn-sm">
                                    <i class="fas fa-plus"></i> Kat Ekle
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-striped projects">
                            <thead>
                            <tr>
                                <th style="width: 10%">#</th>
                                <th style="width: 50%">Kat Adı</th>
                                <th style="width: 40%">İşlemler</th>
                            </tr>
                            </thead>
                            <tbody>
                            <td class="project-actions">
                                <a class="btn btn-primary btn-sm" href="sistem_odalari.php?floor_id=<?php echo htmlspecialchars($floor['floor_id']); ?>">
                                    <i class="fas fa-door-open"></i> Sistem Odaları
                                </a>
                                <?php if ($_SESSION['role'] === 'admin'): ?>
                                    <a class="btn btn-info btn-sm" href="kat_ekle_duzenle.php?id=<?php echo htmlspecialchars($floor['floor_id']); ?>&building_id=<?php echo htmlspecialchars($building_id); ?>">
                                        <i class="fas fa-pencil-alt"></i> Düzenle
                                    </a>
                                    <a class="btn btn-danger btn-sm" href="katlar.php?delete_id=<?php echo htmlspecialchars($floor['floor_id']); ?>&building_id=<?php echo htmlspecialchars($building_id); ?>" onclick="return confirm('Bu katı silmek istediğinizden emin misiniz? Bu işlem geri alınamaz.');">
                                        <i class="fas fa-trash"></i> Sil
                                    </a>
                                <?php endif; ?>
                            </td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </section>
    </div>
    <?php include 'partials/footer.php'; ?>
</div>
</body>
</html>