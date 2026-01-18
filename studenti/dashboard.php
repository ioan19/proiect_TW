<?php
require 'auth.php';
require 'db.php';

// Preluăm rolul și datele utilizatorului
$role = $_SESSION['role'] ?? 'operator'; // Poate fi: admin, technician, operator
$userId = $_SESSION['user_id'];
$fullName = $_SESSION['fullname'];

// --- LOGICA DE STATISTICI (DIFERITĂ ÎN FUNCȚIE DE ROL) ---
$stats = [];

if ($role === 'admin') {
    // ADMIN: Vede tot
    $stats['total_drones'] = $pdo->query("SELECT COUNT(*) FROM Drones")->fetchColumn();
    $stats['active_drones'] = $pdo->query("SELECT COUNT(*) FROM Drones WHERE Status = 'activa'")->fetchColumn();
    $stats['maintenance'] = $pdo->query("SELECT COUNT(*) FROM Drones WHERE Status = 'mentenanta'")->fetchColumn();
    $stats['pending_missions'] = $pdo->query("SELECT COUNT(*) FROM Missions WHERE MissionStatus = 'in_asteptare'")->fetchColumn();
    
    // Tabel: Cele mai recente misiuni
    $stmtRecent = $pdo->query("SELECT m.*, d.Model FROM Missions m LEFT JOIN Drones d ON m.DroneID = d.DroneID ORDER BY m.MissionID DESC LIMIT 5");

} elseif ($role === 'technician') {
    // TEHNICIAN: Vede doar service
    $stats['maintenance_drones'] = $pdo->query("SELECT COUNT(*) FROM Drones WHERE Status = 'mentenanta'")->fetchColumn();
    $stats['open_tickets'] = $pdo->query("SELECT COUNT(*) FROM Maintenance WHERE StatusTichet = 'deschis'")->fetchColumn();
    $stats['my_repairs'] = $pdo->prepare("SELECT COUNT(*) FROM Maintenance WHERE TechnicianID = ?");
    $stats['my_repairs']->execute([$userId]);
    $stats['my_repairs'] = $stats['my_repairs']->fetchColumn();
    
    // Tabel: Tichete deschise (prioritatea lui)
    $stmtRecent = $pdo->query("SELECT m.*, d.Model, d.Status FROM Maintenance m JOIN Drones d ON m.DroneID = d.DroneID WHERE m.StatusTichet = 'deschis' ORDER BY m.DatePerformed ASC LIMIT 5");

} else {
    // OPERATOR: Vede doar misiunile lui
    $stats['my_missions'] = $pdo->prepare("SELECT COUNT(*) FROM Missions WHERE RequesterID = ?");
    $stats['my_missions']->execute([$userId]);
    $stats['my_missions'] = $stats['my_missions']->fetchColumn();
    
    $stats['completed'] = $pdo->prepare("SELECT COUNT(*) FROM Missions WHERE RequesterID = ? AND MissionStatus = 'incheiata'");
    $stats['completed']->execute([$userId]);
    $stats['completed'] = $stats['completed']->fetchColumn();

    $stats['pending'] = $pdo->prepare("SELECT COUNT(*) FROM Missions WHERE RequesterID = ? AND MissionStatus = 'in_asteptare'");
    $stats['pending']->execute([$userId]);
    $stats['pending'] = $stats['pending']->fetchColumn();
    
    // Tabel: Misiunile mele recente
    $stmtRecent = $pdo->prepare("SELECT m.*, d.Model FROM Missions m LEFT JOIN Drones d ON m.DroneID = d.DroneID WHERE m.RequesterID = ? ORDER BY m.MissionID DESC LIMIT 5");
    $stmtRecent->execute([$userId]);
}

$recentData = $stmtRecent->fetchAll();
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - DroneFleet</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body class="dashboard-layout"> 
    
    <div class="top-bar-dashboard">
        <div class="logo"><a href="home.php"><img src="logo1.png" alt="Logo"></a></div>
        <nav>
            <ul class="dashboard-nav-links">
                <li class="active"><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                
                <?php if($role === 'admin' || $role === 'operator'): ?>
                    <li><a href="missions.php"><i class="fas fa-route"></i> <?= $role==='admin' ? 'Misiuni' : 'Cererile Mele' ?></a></li>
                <?php endif; ?>

                <?php if($role === 'admin'): ?>
                    <li><a href="drones.php"><i class="fas fa-plane"></i> Drone</a></li>
                    <li><a href="users.php"><i class="fas fa-users"></i> Utilizatori</a></li>
                <?php endif; ?>

                <?php if($role === 'admin' || $role === 'technician'): ?>
                    <li><a href="maintenance.php"><i class="fas fa-tools"></i> Mentenanță</a></li>
                <?php endif; ?>

                <?php if($role === 'admin'): ?>
                    <li><a href="locations.php"><i class="fas fa-map-marker-alt"></i> Locații</a></li>
                <?php endif; ?>
            </ul>
        </nav>
        <div class="user-controls-top">
             <div class="user-info-top">
                <i class="fas fa-user-circle"></i>
                <span class="user-role"><?= htmlspecialchars($fullName); ?> (<?= ucfirst($role) ?>)</span>
            </div>
             <a href="logout.php" class="logout-top-button">Deconectare</a>
        </div>
    </div>

    <div class="main-content">
        <section id="dashboard">
            <header class="dashboard-header">
                <h1>Bine ai venit, <?= htmlspecialchars($fullName) ?>!</h1>
                <p>Panou de control: <strong><?= ucfirst($role) ?></strong></p>
            </header>

            <section class="overview-cards">
                <?php if ($role === 'admin'): ?>
                    <div class="card">
                        <h3>Drone Total</h3>
                        <p class="metric-value"><?= $stats['total_drones'] ?></p>
                        <i class="fas fa-plane card-icon active-icon"></i>
                    </div>
                    <div class="card">
                        <h3>În Service</h3>
                        <p class="metric-value"><?= $stats['maintenance'] ?></p>
                        <i class="fas fa-tools card-icon" style="color:orange;"></i>
                    </div>
                    <div class="card">
                        <h3>Misiuni Noi</h3>
                        <p class="metric-value"><?= $stats['pending_missions'] ?></p>
                        <i class="fas fa-bell card-icon planned-icon"></i>
                    </div>

                <?php elseif ($role === 'technician'): ?>
                    <div class="card">
                        <h3>Drone în Service</h3>
                        <p class="metric-value"><?= $stats['maintenance_drones'] ?></p>
                        <i class="fas fa-plane-slash card-icon" style="color:red;"></i>
                    </div>
                    <div class="card">
                        <h3>Tichete Deschise</h3>
                        <p class="metric-value"><?= $stats['open_tickets'] ?></p>
                        <i class="fas fa-clipboard-list card-icon" style="color:orange;"></i>
                    </div>
                    <div class="card">
                        <h3>Reparațiile Mele</h3>
                        <p class="metric-value"><?= $stats['my_repairs'] ?></p>
                        <i class="fas fa-check-circle card-icon time-icon"></i>
                    </div>

                <?php else: ?>
                    <div class="card">
                        <h3>Total Cereri</h3>
                        <p class="metric-value"><?= $stats['my_missions'] ?></p>
                        <i class="fas fa-paper-plane card-icon active-icon"></i>
                    </div>
                    <div class="card">
                        <h3>În Așteptare</h3>
                        <p class="metric-value"><?= $stats['pending'] ?></p>
                        <i class="fas fa-hourglass-half card-icon planned-icon"></i>
                    </div>
                    <div class="card">
                        <h3>Completate</h3>
                        <p class="metric-value"><?= $stats['completed'] ?></p>
                        <i class="fas fa-check card-icon time-icon"></i>
                    </div>
                <?php endif; ?>
            </section>
            
            <section class="data-tables">
                <h2>
                    <?php 
                        if($role === 'admin') echo "Cereri Misiuni Recente";
                        elseif($role === 'technician') echo "Drone care necesită reparații (Urgent)";
                        else echo "Istoricul Meu";
                    ?>
                </h2>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <?php if($role === 'technician'): ?>
                                <th>Dronă</th>
                                <th>Dată Intrare</th>
                                <th>Motiv</th>
                            <?php else: ?>
                                <th>Dronă</th>
                                <th>Tip</th>
                                <th>Status</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentData as $row): ?>
                        <tr>
                            <?php if($role === 'technician'): ?>
                                <td>#<?= $row['MaintenanceID'] ?></td>
                                <td><?= htmlspecialchars($row['Notes'] ?? 'N/A') ?></td>
                                <td><?= explode(' ', $row['DatePerformed'])[0] ?></td>
                                <td><?= htmlspecialchars($row['Description'] ?? 'N/A') ?></td>
                            <?php else: ?>
                                <td><?= $row['MissionID'] ?></td>
                                <td><?= htmlspecialchars($row['Model'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($row['Type']) ?></td>
                                <td>
                                    <span class="status-badge" style="background: #e8f5e9; padding: 5px; border-radius: 4px;">
                                        <?= htmlspecialchars($row['MissionStatus']) ?>
                                    </span>
                                </td>
                            <?php endif; ?>
                        </tr>
                        <?php endforeach; ?>
                        <?php if(empty($recentData)): ?>
                            <tr><td colspan="4" style="text-align:center;">Nu există date recente.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </section>
        </section>
    </div> 
    
    <footer class="site-footer">
        <p>&copy; 2025 DroneFleet Manager. Toate drepturile rezervate.</p>
        <p>Contact: <a href="mailto:support@dronefleet.com">support@dronefleet.com</a></p>
    </footer>
</body>
</html>