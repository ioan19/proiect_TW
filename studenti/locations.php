<?php
require 'auth.php';
require 'db.php';

// --- SECURITATE: Doar Adminii au acces aici ---
// Dacă rolul nu este admin, te trimite înapoi la dashboard
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: dashboard.php");
    exit();
}

$locations = $pdo->query("SELECT * FROM Locations ORDER BY LocationID ASC")->fetchAll();
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <title>Gestiune Locații</title>
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
                <li><a href="drones.php"><i class="fas fa-plane"></i> Drone</a></li>
                <li><a href="users.php"><i class="fas fa-users"></i> Utilizatori</a></li>
                <li><a href="maintenance.php"><i class="fas fa-tools"></i> Mentenanță</a></li>
                <li class="active"><a href="locations.php"><i class="fas fa-map-marker-alt"></i> Locații</a></li>
            </ul>
        </nav>
        <div class="user-controls-top">
             <div class="user-info-top"><i class="fas fa-user-circle"></i> <?= htmlspecialchars($_SESSION['fullname']) ?></div>
             <a href="logout.php" class="logout-top-button">Deconectare</a>
        </div>
    </div>
    
    <div class="main-content">
        <section id="locations">
            <header class="dashboard-header">
                <h1>Gestiunea Locațiilor</h1>
                <button class="cta-button" style="padding: 5px 15px;">+ Adaugă Locație</button>
            </header>
            
            <div style="overflow-x: auto;">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nume Locație</th>
                            <th>Tip</th>
                            <th>Coordonate</th>
                            <th>Acțiuni</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($locations as $loc): ?>
                        <tr>
                            <td><?= $loc['LocationID'] ?></td>
                            <td><strong><?= htmlspecialchars($loc['LocationName']) ?></strong></td>
                            <td><?= htmlspecialchars($loc['Type']) ?></td>
                            <td><?= htmlspecialchars($loc['Coordinates']) ?></td>
                            <td><i class="fas fa-edit" style="cursor:pointer; color:gray;"></i></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</body>
</html>