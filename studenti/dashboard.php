<?php
require 'auth.php';
require 'db.php';

$isAdmin = (isset($_SESSION['role']) && $_SESSION['role'] === 'admin');
$userId = $_SESSION['user_id'];

// --- STATISTICI ---
if ($isAdmin) {
    $activeDrones = $pdo->query("SELECT COUNT(*) FROM Drones WHERE Status = 'activa'")->fetchColumn();
    $statsTitle1 = "Drone Active (Total)";
    $pendingMissions = $pdo->query("SELECT COUNT(*) FROM Missions WHERE MissionStatus = 'in_asteptare'")->fetchColumn();
    $statsTitle2 = "Cereri Aprobare";
    $completedMissions = $pdo->query("SELECT COUNT(*) FROM Missions WHERE MissionStatus = 'incheiata'")->fetchColumn();
    $statsTitle3 = "Misiuni Încheiate";
    $stmtRecent = $pdo->query("SELECT m.*, d.Model FROM Missions m LEFT JOIN Drones d ON m.DroneID = d.DroneID WHERE m.MissionStatus = 'in_asteptare' ORDER BY m.MissionID DESC LIMIT 5");
} else {
    $activeDrones = $pdo->query("SELECT COUNT(*) FROM Drones WHERE Status = 'activa'")->fetchColumn();
    $statsTitle1 = "Drone Disponibile";
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM Missions WHERE RequesterID = ? AND MissionStatus = 'in_asteptare'");
    $stmt->execute([$userId]);
    $pendingMissions = $stmt->fetchColumn();
    $statsTitle2 = "Cererile Mele";
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM Missions WHERE RequesterID = ? AND MissionStatus = 'incheiata'");
    $stmt->execute([$userId]);
    $completedMissions = $stmt->fetchColumn();
    $statsTitle3 = "Istoric Completate";
    $stmtRecent = $pdo->prepare("SELECT m.*, d.Model FROM Missions m LEFT JOIN Drones d ON m.DroneID = d.DroneID WHERE m.RequesterID = ? ORDER BY m.MissionID DESC LIMIT 5");
    $stmtRecent->execute([$userId]);
}
$recentMissions = $stmtRecent->fetchAll();
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <title>DroneFleet Manager - Dashboard</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body class="dashboard-layout"> 
    
    <div class="top-bar-dashboard">
        <div class="logo"><a href="home.php"><img src="logo1.png" alt="Logo"></a></div>
        <nav>
            <ul class="dashboard-nav-links">
                <li class="active"><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="missions.php"><i class="fas fa-route"></i> <?= $isAdmin ? 'Misiuni' : 'Cererile Mele' ?></a></li>

                <?php if($isAdmin): ?>
                    <li><a href="drones.php"><i class="fas fa-plane"></i> Drone</a></li>
                    <li><a href="users.php"><i class="fas fa-users"></i> Utilizatori</a></li>
                    <li><a href="maintenance.php"><i class="fas fa-tools"></i> Mentenanță</a></li>
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
        <section id="dashboard">
            <header class="dashboard-header">
                <h1><?= $isAdmin ? 'Panou Administrator' : 'Panou Operator' ?></h1>
            </header>

            <section class="overview-cards">
                <div class="card">
                    <h3><?= $statsTitle1 ?></h3>
                    <p class="metric-value"><?= $activeDrones ?></p>
                    <i class="fas fa-plane card-icon active-icon"></i>
                </div>
                <div class="card">
                    <h3><?= $statsTitle2 ?></h3>
                    <p class="metric-value"><?= $pendingMissions ?></p>
                    <i class="fas fa-hourglass-half card-icon planned-icon" style="color: orange;"></i>
                </div>
                <div class="card">
                    <h3><?= $statsTitle3 ?></h3>
                    <p class="metric-value"><?= $completedMissions ?></p>
                    <i class="fas fa-check-circle card-icon time-icon"></i>
                </div>
            </section>
            
            <section class="data-tables">
                <h2><?= $isAdmin ? 'Cereri Recente' : 'Ultimele Mele Activități' ?></h2>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Dronă</th>
                            <th>Tip</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentMissions as $m): ?>
                        <tr>
                            <td><?= $m['MissionID'] ?></td>
                            <td><?= htmlspecialchars($m['Model'] ?? 'N/A') ?></td>
                            <td><?= htmlspecialchars($m['Type']) ?></td>
                            <td>
                                <span class="status-badge" style="background: <?= $m['MissionStatus']=='in_asteptare'?'#fff3e0':'#e8f5e9' ?>; padding: 5px; border-radius: 4px;">
                                    <?= htmlspecialchars($m['MissionStatus']) ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </section>
        </section>
    </div> 
</body>
</html>