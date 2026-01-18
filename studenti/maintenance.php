<?php
require 'auth.php';
require 'db.php';

// 1. DEFINIM VARIABILA $isAdmin (Aici era eroarea)
$isAdmin = (isset($_SESSION['role']) && $_SESSION['role'] === 'admin');

// 2. SECURITATE: Dacă nu e admin, redirect la dashboard
if (!$isAdmin) {
    header("Location: dashboard.php");
    exit();
}

$logs = $pdo->query("SELECT m.*, d.Model FROM Maintenance m LEFT JOIN Drones d ON m.DroneID = d.DroneID ORDER BY m.DatePerformed DESC")->fetchAll();
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <title>DroneFleet Manager - Mentenanță</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .data-table { width: 100%; border-collapse: collapse; margin-top: 20px; background: white; }
        .data-table th, .data-table td { padding: 12px; border-bottom: 1px solid #ddd; text-align: left; }
        .data-table th { background-color: #f8f9fa; }
    </style>
</head>
<body class="dashboard-layout">
    
    <div class="top-bar-dashboard">
        <div class="logo"><a href="home.php"><img src="logo1.png" alt="Logo"></a></div>
        <nav>
            <ul class="dashboard-nav-links">
                <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="missions.php"><i class="fas fa-route"></i> Misiuni</a></li>
                
                <?php if($isAdmin): ?>
                    <li><a href="drones.php"><i class="fas fa-plane"></i> Drone</a></li>
                    <li><a href="users.php"><i class="fas fa-users"></i> Utilizatori</a></li>
                    <li class="active"><a href="maintenance.php"><i class="fas fa-tools"></i> Mentenanță</a></li>
                    <li><a href="locations.php"><i class="fas fa-map-marker-alt"></i> Locații</a></li>
                <?php endif; ?>
            </ul>
        </nav>
        <div class="user-controls-top">
             <div class="user-info-top"><i class="fas fa-user-circle"></i> <?= htmlspecialchars($_SESSION['fullname']) ?></div>
             <a href="logout.php" class="logout-top-button">Deconectare</a>
        </div>
    </div>
    
    <div class="main-content">
        <section id="maintenance">
            <header class="dashboard-header"><h1>Jurnal Mentenanță</h1></header>
            
            <div style="overflow-x: auto;">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID Op.</th>
                            <th>Dronă</th>
                            <th>Data</th>
                            <th>Tip</th>
                            <th>Observații</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logs as $log): ?>
                        <tr>
                            <td>#<?= $log['MaintenanceID'] ?></td>
                            <td><?= htmlspecialchars($log['Model']) ?></td>
                            <td><?= htmlspecialchars($log['DatePerformed']) ?></td>
                            <td><?= htmlspecialchars($log['Type']) ?></td>
                            <td><?= htmlspecialchars($log['Notes']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</body>
</html>