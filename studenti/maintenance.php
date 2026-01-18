<?php
require 'auth.php';
require 'db.php';

$role = $_SESSION['role'] ?? 'operator';
$userId = $_SESSION['user_id'];
$userName = $_SESSION['fullname'];

// Acces permis doar Admin și Tehnician
if ($role !== 'admin' && $role !== 'technician') {
    header("Location: dashboard.php"); exit();
}

$message = '';

// --- LOGICA TEHNICIAN: FINALIZARE REPARAȚIE ---
if (isset($_POST['complete_repair'])) {
    $mId = $_POST['maintenance_id'];
    $droneId = $_POST['drone_id'];
    $repairType = $_POST['repair_type'];
    $notes = $_POST['notes'];
    
    try {
        // 1. Actualizăm Tichetul
        $stmt = $pdo->prepare("UPDATE Maintenance SET 
                                StatusTichet = 'inchis', 
                                RepairType = ?, 
                                Notes = ?, 
                                TechnicianID = ? 
                                WHERE MaintenanceID = ?");
        $stmt->execute([$repairType, $notes, $userId, $mId]);

        // 2. Reactivăm Drona (Trimisă la HQ, ID 1)
        $stmtD = $pdo->prepare("UPDATE Drones SET Status = 'activa', CurrentLocationID = 1 WHERE DroneID = ?");
        $stmtD->execute([$droneId]);

        $message = "Reparație înregistrată! Drona a fost reactivată.";
    } catch (PDOException $e) {
        $message = "Eroare: " . $e->getMessage();
    }
}

// --- PRELUARE DATE ---
$sqlOpen = "SELECT m.*, d.Model, d.Status 
            FROM Maintenance m 
            JOIN Drones d ON m.DroneID = d.DroneID 
            WHERE m.StatusTichet = 'deschis' 
            ORDER BY m.DatePerformed ASC";
$openTickets = $pdo->query($sqlOpen)->fetchAll();

$sqlHistory = "SELECT m.*, d.Model, u.FullName as TechName 
               FROM Maintenance m 
               JOIN Drones d ON m.DroneID = d.DroneID 
               LEFT JOIN Users u ON m.TechnicianID = u.UserID
               WHERE m.StatusTichet = 'inchis'
               ORDER BY m.DatePerformed DESC LIMIT 50";
$historyLogs = $pdo->query($sqlHistory)->fetchAll();
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <title>Jurnal Mentenanță</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .data-table { width: 100%; border-collapse: collapse; margin-top: 20px; background: white; }
        .data-table th, .data-table td { padding: 12px; border-bottom: 1px solid #ddd; text-align: left; }
        .data-table th { background-color: #f8f9fa; }
        .ticket-card { background: #fff; border-left: 5px solid #e67e22; padding: 20px; margin-bottom: 20px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); border-radius: 5px; }
        .form-row { display: flex; gap: 15px; margin-top: 15px; }
        input, select, textarea { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
        .btn-fix { background: #27ae60; color: white; border: none; padding: 10px 20px; cursor: pointer; border-radius: 4px; font-weight: bold; }
        .badge-tech { background: #e3f2fd; color: #1565c0; padding: 2px 6px; border-radius: 4px; font-size: 0.85em; }
    </style>
</head>
<body class="dashboard-layout">
    
    <div class="top-bar-dashboard">
        <div class="logo"><a href="home.php"><img src="logo1.png" alt="Logo"></a></div>
        <nav>
            <ul class="dashboard-nav-links">
                <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                
                <?php if($role === 'admin' || $role === 'operator'): ?>
                    <li><a href="missions.php"><i class="fas fa-route"></i> <?= $role==='admin' ? 'Misiuni' : 'Cererile Mele' ?></a></li>
                <?php endif; ?>

                <?php if($role === 'admin'): ?>
                    <li><a href="drones.php"><i class="fas fa-plane"></i> Drone</a></li>
                    <li><a href="users.php"><i class="fas fa-users"></i> Utilizatori</a></li>
                <?php endif; ?>

                <?php if($role === 'admin' || $role === 'technician'): ?>
                    <li class="active"><a href="maintenance.php"><i class="fas fa-tools"></i> Mentenanță</a></li>
                <?php endif; ?>

                <?php if($role === 'admin'): ?>
                    <li><a href="locations.php"><i class="fas fa-map-marker-alt"></i> Locații</a></li>
                <?php endif; ?>
            </ul>
        </nav>
        <div class="user-controls-top">
             <div class="user-info-top"><i class="fas fa-user-circle"></i> <?= htmlspecialchars($userName) ?> (<?= ucfirst($role) ?>)</div>
             <a href="logout.php" class="logout-top-button">Deconectare</a>
        </div>
    </div>
    
    <div class="main-content">
        <section>
            <header class="dashboard-header">
                <h1><?= $role === 'technician' ? 'Panou Control Tehnician' : 'Registru Mentenanță' ?></h1>
            </header>
            
            <?php if ($message): ?>
                <div style="background: #d4edda; color: #155724; padding: 15px; margin-bottom: 20px; border-radius: 5px;">
                    <i class="fas fa-check-circle"></i> <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>

            <?php if(count($openTickets) > 0): ?>
                <h2 style="color: #e67e22;"><i class="fas fa-exclamation-triangle"></i> Drone în Service (De Reparat)</h2>
                <?php foreach($openTickets as $ticket): ?>
                <div class="ticket-card">
                    <div style="display:flex; justify-content:space-between;">
                        <h3>Drona: <strong><?= htmlspecialchars($ticket['Model']) ?></strong></h3>
                        <span style="color: #7f8c8d;"><?= $ticket['DatePerformed'] ?></span>
                    </div>
                    <p><strong>Motiv Admin:</strong> <?= htmlspecialchars($ticket['Notes']) ?></p>
                    
                    <?php if($role === 'technician' || $role === 'admin'): ?>
                    <hr style="border:0; border-top:1px solid #eee; margin: 15px 0;">
                    <form method="POST">
                        <input type="hidden" name="maintenance_id" value="<?= $ticket['MaintenanceID'] ?>">
                        <input type="hidden" name="drone_id" value="<?= $ticket['DroneID'] ?>">
                        <div class="form-row">
                            <div style="flex:1;">
                                <label>Tip Reparație:</label>
                                <select name="repair_type" required>
                                    <option value="">-- Selectează --</option>
                                    <option value="Inspectie Generala">Inspecție Generală</option>
                                    <option value="Inlocuire Elice">Înlocuire Elice</option>
                                    <option value="Calibrare Senzori">Calibrare Senzori</option>
                                    <option value="Reparatie Motor">Reparație Motor</option>
                                    <option value="Update Software">Update Software</option>
                                </select>
                            </div>
                            <div style="flex:2;">
                                <label>Raport Tehnic:</label>
                                <input type="text" name="notes" placeholder="Detalii reparație..." required>
                            </div>
                            <div style="flex:0.5; display:flex; align-items:flex-end;">
                                <button type="submit" name="complete_repair" class="btn-fix">Activează</button>
                            </div>
                        </div>
                    </form>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            <?php elseif($role === 'technician'): ?>
                <div style="padding: 20px; background: #e8f5e9; color: #2e7d32; border-radius: 5px;">
                    <i class="fas fa-smile"></i> Nicio dronă în service.
                </div>
            <?php endif; ?>

            <h2 style="margin-top: 40px;"><i class="fas fa-history"></i> Istoric Intervenții</h2>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Dronă</th>
                        <th>Tip Intervenție</th>
                        <th>Raport</th>
                        <th>Tehnician</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($historyLogs as $log): ?>
                    <tr>
                        <td><?= explode(' ', $log['DatePerformed'])[0] ?></td>
                        <td><strong><?= htmlspecialchars($log['Model']) ?></strong></td>
                        <td><?= htmlspecialchars($log['RepairType'] ?? 'General') ?></td>
                        <td><?= htmlspecialchars($log['Notes']) ?></td>
                        <td>
                            <?php if($log['TechName']): ?>
                                <span class="badge-tech"><i class="fas fa-user-wrench"></i> <?= htmlspecialchars($log['TechName']) ?></span>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </section>
    </div>
    
    <footer class="site-footer">
        <p>&copy; 2025 DroneFleet Manager. Toate drepturile rezervate.</p>
        <p>Contact: <a href="mailto:support@dronefleet.com">support@dronefleet.com</a></p>
    </footer>
</body>
</html>