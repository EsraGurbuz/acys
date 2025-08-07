<?php
session_start();
require_once 'config/db_config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$search_query = $_GET['query'] ?? '';
$results = [];

if ($search_query) {
    $search_term = '%' . $search_query . '%';

    try {
        // Binalar tablosunda arama
        $stmt = $pdo->prepare("SELECT 'Bina' AS type, building_id AS id, building_name AS name FROM Buildings WHERE building_name LIKE ?");
        $stmt->execute([$search_term]);
        $results['buildings'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Katlar tablosunda arama
        $stmt = $pdo->prepare("SELECT 'Kat' AS type, f.floor_id AS id, f.floor_name AS name, b.building_name FROM Floors f INNER JOIN Buildings b ON f.building_id = b.building_id WHERE f.floor_name LIKE ?");
        $stmt->execute([$search_term]);
        $results['floors'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Sistem Odaları tablosunda arama
        $stmt = $pdo->prepare("SELECT 'Sistem Odası' AS type, sr.room_id AS id, sr.room_name AS name, f.floor_name, b.building_name FROM System_Rooms sr INNER JOIN Floors f ON sr.floor_id = f.floor_id INNER JOIN Buildings b ON f.building_id = b.building_id WHERE sr.room_name LIKE ?");
        $stmt->execute([$search_term]);
        $results['rooms'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Anahtarlar tablosunda arama
        $stmt = $pdo->prepare("SELECT 'Anahtar' AS type, s.switch_id AS id, s.switch_model AS name, s.ip_address, sr.room_name, f.floor_name, b.building_name FROM Switches s INNER JOIN System_Rooms sr ON s.room_id = sr.room_id INNER JOIN Floors f ON sr.floor_id = f.floor_id INNER JOIN Buildings b ON f.building_id = b.building_id WHERE s.switch_model LIKE ? OR s.ip_address LIKE ?");
        $stmt->execute([$search_term, $search_term]);
        $results['switches'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Portlar tablosunda arama
        $stmt = $pdo->prepare("SELECT 'Port' AS type, p.port_id AS id, p.port_number AS name, p.device_name, p.mac_address, s.switch_model, sr.room_name, f.floor_name, b.building_name FROM Ports p INNER JOIN Switches s ON p.switch_id = s.switch_id INNER JOIN System_Rooms sr ON s.room_id = sr.room_id INNER JOIN Floors f ON sr.floor_id = f.floor_id INNER JOIN Buildings b ON f.building_id = b.building_id WHERE p.device_name LIKE ? OR p.mac_address LIKE ?");
        $stmt->execute([$search_term, $search_term]);
        $results['ports'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
        die("Veritabanı hatası: " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ACYS - Genel Arama</title>

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
                        <h1>Genel Arama</h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="index.php">ACYS</a></li>
                            <li class="breadcrumb-item active">Genel Arama</li>
                        </ol>
                    </div>
                </div>
            </div>
        </section>

        <section class="content">
            <div class="container-fluid">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Arama Sonuçları</h3>
                    </div>
                    <div class="card-body">
                        <form action="arama.php" method="get">
                            <div class="input-group mb-3">
                                <input type="text" name="query" class="form-control" placeholder="Anahtar kelime girin..." value="<?php echo htmlspecialchars($search_query); ?>">
                                <div class="input-group-append">
                                    <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Ara</button>
                                </div>
                            </div>
                        </form>

                        <?php if ($search_query): ?>
                            <div class="mt-4">
                                <h4>"<?php echo htmlspecialchars($search_query); ?>" için sonuçlar:</h4>
                                <hr>

                                <?php
                                $total_results = 0;
                                foreach ($results as $key => $result_array) {
                                    $total_results += count($result_array);
                                }

                                if ($total_results > 0):
                                    ?>
                                    <?php foreach ($results as $type => $result_array): ?>
                                    <?php if (!empty($result_array)): ?>
                                        <h5><?php echo htmlspecialchars($type); ?> Sonuçları</h5>
                                        <ul class="list-group mb-3">
                                            <?php foreach ($result_array as $result): ?>
                                                <li class="list-group-item">
                                                    <?php
                                                    switch ($result['type']) {
                                                        case 'Bina':
                                                            echo '<i class="fas fa-building text-primary"></i> <a href="binalar.php">' . htmlspecialchars($result['name']) . '</a>';
                                                            break;
                                                        case 'Kat':
                                                            echo '<i class="fas fa-door-open text-info"></i> <a href="katlar.php?building_id=' . htmlspecialchars($result['building_id']) . '">' . htmlspecialchars($result['building_name']) . '</a> - <a href="katlar.php?building_id=' . htmlspecialchars($result['building_id']) . '">' . htmlspecialchars($result['name']) . '</a>';
                                                            break;
                                                        case 'Sistem Odası':
                                                            echo '<i class="fas fa-server text-success"></i> <a href="sistem_odalari.php?floor_id=' . htmlspecialchars($result['floor_id']) . '">' . htmlspecialchars($result['building_name']) . '</a> - <a href="sistem_odalari.php?floor_id=' . htmlspecialchars($result['floor_id']) . '">' . htmlspecialchars($result['floor_name']) . '</a> - <a href="sistem_odalari.php?floor_id=' . htmlspecialchars($result['floor_id']) . '">' . htmlspecialchars($result['name']) . '</a>';
                                                            break;
                                                        case 'Anahtar':
                                                            echo '<i class="fas fa-network-wired text-warning"></i> <a href="switches.php?room_id=' . htmlspecialchars($result['room_id']) . '">' . htmlspecialchars($result['ip_address']) . ' (' . htmlspecialchars($result['name']) . ')</a> - ' . htmlspecialchars($result['building_name']) . ' > ' . htmlspecialchars($result['floor_name']) . ' > ' . htmlspecialchars($result['room_name']);
                                                            break;
                                                        case 'Port':
                                                            echo '<i class="fas fa-plug text-danger"></i> <a href="ports.php?switch_id=' . htmlspecialchars($result['switch_id']) . '">Port #' . htmlspecialchars($result['name']) . '</a> - Cihaz: ' . htmlspecialchars($result['device_name']) . ' - MAC: ' . htmlspecialchars($result['mac_address']) . ' - Switch: ' . htmlspecialchars($result['switch_model']) . ' - ' . htmlspecialchars($result['building_name']) . ' > ' . htmlspecialchars($result['floor_name']) . ' > ' . htmlspecialchars($result['room_name']);
                                                            break;
                                                    }
                                                    ?>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="alert alert-warning">
                                        Aradığınız anahtar kelimeye uygun bir sonuç bulunamadı.
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
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