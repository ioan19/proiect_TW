<?php
require 'auth.php';
require 'db.php';

// Verificare Admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: dashboard.php");
    exit();
}

$isAdmin = true;
$message = '';
$editMode = false;
$droneToEdit = [];

// CRUD Logic
if (isset($_GET['delete_id'])) {
    $stmt = $pdo->prepare("DELETE FROM Drones WHERE DroneID = ?");
    $stmt->execute([$_GET['delete_id']]);
    $message = "Drona ștearsă!";
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_drone'])) {
    // Preluare date
    $model = $_POST['model']; $capacity = $_POST['capacity']; $autonomy = $_POST['autonomy'];
    $status = $_POST['status']; $lastCheck = $_POST['last_check']; $droneId = $_POST['drone_id'];

    if (!empty($droneId)) {
        $stmt = $pdo->prepare("UPDATE Drones SET Model=?, PayloadCapacity=?, AutonomyMin=?, Status=?, LastCheckDate=? WHERE DroneID=?");
        $stmt->execute([$model, $capacity, $autonomy, $status, $lastCheck, $droneId]);
    } else {
        $nextId = $pdo->query("SELECT MAX(DroneID) FROM Drones")->fetchColumn() + 1;
        $stmt = $pdo->prepare("INSERT INTO Drones (DroneID, Model, PayloadCapacity, AutonomyMin, Status, LastCheckDate) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$nextId, $model, $capacity, $autonomy, $status, $lastCheck]);
    }
    $message = "Salvat cu succes!";
}

if (isset($_GET['edit_id'])) {
    $editMode = true;
    $stmt = $pdo->prepare("SELECT * FROM Drones WHERE DroneID = ?");
    $stmt->execute([$_GET['edit_id']]);
    $droneToEdit = $stmt->fetch();
}

$drones = $pdo->query("SELECT * FROM Drones ORDER BY DroneID ASC")->fetchAll();
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <title>Gestiune Drone</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .data-table { width: 100%; border-collapse: collapse; margin-top: 20px; background: white; }
        .data-table th, .data-table td { padding: 12px; border-bottom: 1px solid #ddd; text-align: left; }
        .data-table th { background-color: #f8f9fa; }
        .form-container { background: #fff; padding: 20px; border-left: 5px solid #3498db; margin-bottom: 20px; }
        .form-row { display: flex; gap: 15px; margin-bottom: 10px; }
        .form-group { flex: 1; }
        input, select { width: 100%; padding: 8px; margin-top: 5px; }
        .status-badge { padding: 4px 8px; border-radius: 4px; font-weight: bold; text-transform: uppercase; font-size: 0.8em;}
        .status-activa { background-color: #e8f5e9; color: #2e7d32; }
        .status-mentenanta { background-color: #fff3e0; color: #ef6c00; }
        .status-inactiva { background-color: #ffebee; color: #c62828; }
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
                    <li class="active"><a href="drones.php"><i class="fas fa-plane"></i> Drone</a></li>
                    <li><a href="users.php"><i class="fas fa-users"></i> Utilizatori</a></li>
                    <li><a href="maintenance.php"><i class="fas fa-tools"></i> Mentenanță</a></li>
                    <li><a href="locations.php"><i class="fas fa-map-marker-alt"></i> Locații</a></li>
                <?php endif; ?>
            </ul>
        </nav>
        <div class="user-controls-top">
             <div class="user-info-top"><i class="fas fa-user-circle"></i> Admin</div>
             <a href="logout.php" class="logout-top-button">Deconectare</a>
        </div>
    </div>
    
    <div class="main-content">
        <section>
            <header class="dashboard-header"><h1>Flota de Drone</h1></header>
            
            <?php if ($message): ?>
                <div style="background: #d4edda; color: #155724; padding: 10px; margin-bottom: 20px;"><?= htmlspecialchars($message) ?></div>
            <?php endif; ?>

            <div class="form-container">
                <h3><?= $editMode ? 'Editează Drona' : 'Adaugă Dronă Nouă' ?></h3>
                <form method="POST">
                    <input type="hidden" name="drone_id" value="<?= $editMode ? $droneToEdit['DroneID'] : '' ?>">
                    <div class="form-row">
                        <div class="form-group"><label>Model:</label><input type="text" name="model" required value="<?= $editMode ? $droneToEdit['Model'] : '' ?>"></div>
                        <div class="form-group"><label>Capacitate (kg):</label><input type="number" step="0.1" name="capacity" required value="<?= $editMode ? $droneToEdit['PayloadCapacity'] : '' ?>"></div>
                        <div class="form-group"><label>Autonomie (min):</label><input type="number" name="autonomy" required value="<?= $editMode ? $droneToEdit['AutonomyMin'] : '' ?>"></div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Status:</label>
                            <select name="status">
                                <option value="activa">Activă</option>
                                <option value="mentenanta">Mentenanță</option>
                                <option value="inactiva">Inactivă</option>
                            </select>
                        </div>
                        <div class="form-group"><label>Verificare:</label><input type="date" name="last_check" required value="<?= date('Y-m-d') ?>"></div>
                    </div>
                    <button type="submit" name="save_drone" style="padding: 10px 20px; background: #2ecc71; color: white; border: none; cursor: pointer;">Salvează</button>
                </form>
            </div>

            <table class="data-table">
                <thead>
                    <tr><th>ID</th><th>Model</th><th>Capacitate</th><th>Autonomie</th><th>Status</th><th>Acțiuni</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($drones as $drone): ?>
                    <tr>
                        <td>#<?= $drone['DroneID'] ?></td>
                        <td><strong><?= htmlspecialchars($drone['Model']) ?></strong></td>
                        <td><?= $drone['PayloadCapacity'] ?> kg</td>
                        <td><?= $drone['AutonomyMin'] ?> min</td>
                        <td><span class="status-badge status-<?= $drone['Status'] ?>"><?= $drone['Status'] ?></span></td>
                        <td>
                            <a href="drones.php?edit_id=<?= $drone['DroneID'] ?>" style="color:#3498db;"><i class="fas fa-edit"></i></a>
                            <a href="drones.php?delete_id=<?= $drone['DroneID'] ?>" style="color:#e74c3c; margin-left: 10px;" onclick="return confirm('Ștergi?');"><i class="fas fa-trash"></i></a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </section>
    </div>
</body>
</html>