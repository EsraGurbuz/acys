<?php
session_start();
require_once 'config/db_config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$error_message = '';
$building_switch_counts = [];
$switch_port_usage = [];

try {
    // Rapor 1: Bina Başına Anahtar Sayısı
    $stmt = $pdo->query("
        SELECT
            b.building_name,
            COUNT(s.switch_id) AS switch_count
        FROM Buildings b
        LEFT JOIN Floors f ON b.building_id = f.building_id
        LEFT JOIN System_Rooms sr ON f.floor_id = sr.floor_id
        LEFT JOIN Switches s ON sr.room_id = s.room_id
        GROUP BY b.building_name
        ORDER BY b.building_name ASC
    ");
    $building_switch_counts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Rapor 2: Anahtar Başına Port Kullanımı
    $stmt = $pdo->query("
        SELECT
            s.switch_model,
            s.ip_address,
            s.port_count,
            COUNT(p.port_id) AS used_ports,
            sr.room_name,
            f.floor_name,
            b.building_name
        FROM Switches s
        LEFT JOIN Ports p ON s.switch_id = p.switch_id
        INNER JOIN System_Rooms sr ON s.room_id = sr.room_id
        INNER JOIN Floors f ON sr.floor_id = f.floor_id
        INNER JOIN Buildings b ON f.building_id = b.building_id
        GROUP BY s.switch_id
        ORDER BY s.ip_address ASC
    ");
    $switch_port_usage = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $error_message = "Veritabanı hatası: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ACYS - Raporlama</title>

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
                        <h1>Raporlama</h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="index.php">ACYS</a></li>
                            <li class="breadcrumb-item active">Raporlama</li>
                        </ol>
                    </div>
                </div>
            </div>
        </section>

        <section class="content">
            <div class="container-fluid">
                <?php if ($error_message): ?>
                    <div class="alert alert-danger" role="alert">
                        <?php echo htmlspecialchars($error_message); ?>
                    </div>
                <?php endif; ?>

                <div class="card card-primary">
                    <div class="card-header">
                        <h3 class="card-title">Bina Başına Anahtar Sayısı</h3>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-striped">
                            <thead>
                            <tr>
                                <th>Bina Adı</th>
                                <th>Anahtar Sayısı</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php if (empty($building_switch_counts)): ?>
                                <tr>
                                    <td colspan="2">Hiç veri bulunamadı.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($building_switch_counts as $report): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($report['building_name']); ?></td>
                                        <td><?php echo htmlspecialchars($report['switch_count']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="card card-success">
                    <div class="card-header">
                        <h3 class="card-title">Anahtar Başına Port Kullanımı</h3>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-striped">
                            <thead>
                            <tr>
                                <th>Anahtar (IP)</th>
                                <th>Toplam Port Sayısı</th>
                                <th>Kullanılan Port Sayısı</th>
                                <th>Boş Port Sayısı</th>
                                <th>Kullanım Oranı</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php if (empty($switch_port_usage)): ?>
                                <tr>
                                    <td colspan="5">Hiç veri bulunamadı.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($switch_port_usage as $report): ?>
                                    <?php
                                    $used_ports = (int)$report['used_ports'];
                                    $total_ports = (int)$report['port_count'];
                                    $free_ports = $total_ports - $used_ports;
                                    $usage_rate = ($total_ports > 0) ? round(($used_ports / $total_ports) * 100, 2) : 0;
                                    ?>
                                    <tr>
                                        <td>
                                            <i class="fas fa-network-wired"></i>
                                            <?php echo htmlspecialchars($report['ip_address']); ?> (<?php echo htmlspecialchars($report['switch_model']); ?>)
                                            <br>
                                            <small class="text-muted">
                                                <?php echo htmlspecialchars($report['building_name']); ?> >
                                                <?php echo htmlspecialchars($report['floor_name']); ?> >
                                                <?php echo htmlspecialchars($report['room_name']); ?>
                                            </small>
                                        </td>
                                        <td><?php echo htmlspecialchars($total_ports); ?></td>
                                        <td><?php echo htmlspecialchars($used_ports); ?></td>
                                        <td><?php echo htmlspecialchars($free_ports); ?></td>
                                        <td>
                                            <div class="progress progress-xs">
                                                <div class="progress-bar bg-primary" style="width: <?php echo $usage_rate; ?>%"></div>
                                            </div>
                                            <span class="badge bg-primary"><?php echo $usage_rate; ?>%</span>
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
