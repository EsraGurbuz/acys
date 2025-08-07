<?php
session_start();
require_once 'config/db_config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$error_message = '';

if (isset($_GET['delete_id']) && $_SESSION['role'] === 'admin') {
    $delete_id = $_GET['delete_id'];
    try {
        $stmt = $pdo->prepare("DELETE FROM Buildings WHERE building_id = ?");
        $stmt->execute([$delete_id]);
        header('Location: binalar.php');
        exit();
    } catch (PDOException $e) {
        $error_message = "Silme işlemi sırasında bir hata oluştu: " . $e->getMessage();
    }
}

try {
    $stmt = $pdo->query("SELECT * FROM Buildings ORDER BY building_name ASC");
    $buildings = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Veritabanı hatası: " . $e->getMessage());
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
                        <h3 class="card-title">Bina Listesi</h3>
                        <div class="card-tools">
                            <?php if ($_SESSION['role'] === 'admin'): ?>
                                <a href="bina_ekle_duzenle.php" class="btn btn-success btn-sm">
                                    <i class="fas fa-plus"></i> Bina Ekle
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-striped projects">
                            <thead>
                            <tr>
                                <th style="width: 10%">#</th>
                                <th style="width: 60%">Bina Adı</th>
                                <th style="width: 30%">İşlemler</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php if (empty($buildings)): ?>
                                <tr>
                                    <td colspan="3">Henüz hiç bina eklenmedi.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($buildings as $building): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($building['building_id']); ?></td>
                                        <td><?php echo htmlspecialchars($building['building_name']); ?></td>
                                        <td class="project-actions">
                                            <a class="btn btn-primary btn-sm" href="katlar.php?building_id=<?php echo htmlspecialchars($building['building_id']); ?>">
                                                <i class="fas fa-door-open"></i> Katlar
                                            </a>
                                            <?php if ($_SESSION['role'] === 'admin'): ?>
                                                <a class="btn btn-info btn-sm" href="bina_ekle_duzenle.php?id=<?php echo htmlspecialchars($building['building_id']); ?>">
                                                    <i class="fas fa-pencil-alt"></i> Düzenle
                                                </a>
                                                <a class="btn btn-danger btn-sm" href="binalar.php?delete_id=<?php echo htmlspecialchars($building['building_id']); ?>" onclick="return confirm('Bu binayı silmek istediğinizden emin misiniz? Bu işlem geri alınamaz.');">
                                                    <i class="fas fa-trash"></i> Sil
                                                </a>
                                            <?php endif; ?>
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
</body>
</html>