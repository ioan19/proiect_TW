<?php
require 'auth.php';
require 'db.php';

// Securitate: Doar Admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: dashboard.php"); exit();
}

$message = '';
$isAdmin = true;
$editMode = false;
$droneToEdit = [];

// --- TOGGLE MENTENANȚĂ (Funcționalitate Nouă) ---
if (isset($_POST['toggle_maintenance'])) {
    $dId = $_POST['drone_id'];
    $currentStatus = $_POST['current_status'];
    
    // Dacă e activa -> devine mentenanta. Dacă e mentenanta -> devine activa.
    $newStatus = ($currentStatus === 'mentenanta') ? 'activa' : 'mentenanta';
    
    $stmt = $pdo->prepare("UPDATE Drones SET Status = ? WHERE DroneID = ?");
    $stmt->execute([$newStatus, $dId]);
    
    // Refresh pentru a evita resubmit
    header("Location: drones.php"); 
    exit();
}

// Logica CRUD existentă
if (isset($_GET['delete_id'])) {
    $pdo->prepare("DELETE FROM Drones WHERE DroneID = ?")->execute([$_GET['delete_id']]);
    $message = "Drona a fost ștearsă!";
}
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_drone'])) {
    $model = $_POST['model'];
    $type = $_POST['type']; // Camp nou
    $capacity = $_POST['capacity'];
    $autonomy = $_POST['autonomy'];
    $status = $_POST['status'];
    $lastCheck = $_POST['last_check'];
    $droneId = $_POST['drone_id'];

    if (!empty($droneId)) {
        $stmt = $pdo->prepare("UPDATE Drones SET Model=?, Type=?, PayloadCapacity=?, AutonomyMin=?, Status=?, LastCheckDate=? WHERE DroneID=?");
        $stmt->execute([$model, $type, $capacity, $autonomy, $status, $lastCheck, $droneId]);
    } else {
        $nextId = $pdo->query("SELECT MAX(DroneID) FROM Drones")->fetchColumn() + 1;
        $stmt = $pdo->prepare("INSERT INTO Drones (DroneID, Model, Type, PayloadCapacity, AutonomyMin, Status, LastCheckDate) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$nextId, $model, $type, $capacity, $autonomy, $status, $lastCheck]);
    }
    $message = "Datele dronei au fost salvate!";
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
        .badge-type { background: #eee; padding: 2px 6px; border-radius: 4px; font-size: 0.8em; text-transform: uppercase; color: #555; border: 1px solid #ccc;}
        .btn-small { padding: 5px 10px; border-radius: 4px; border: none; cursor: pointer; color: white; font-size: 0.8em; }
        .btn-green { background-color: #27ae60; }
        .btn-orange { background-color: #e67e22; }
    </style>
</head>
<body class="dashboard-layout">
    
    <div class="top-bar-dashboard">
        <div class="logo"><a href="home.php"><img src="logo1.png" alt="Logo"></a></div>
        <nav>
            <ul class="dashboard-nav-links">
                <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="missions.php"><i class="fas fa-route"></i> Misiuni</a></li>
                <li class="active"><a href="drones.php"><i class="fas fa-plane"></i> Drone</a></li>
                <li><a href="users.php"><i class="fas fa-users"></i> Utilizatori</a></li>
                <li><a href="maintenance.php"><i class="fas fa-tools"></i> Mentenanță</a></li>
                <li><a href="locations.php"><i class="fas fa-map-marker-alt"></i> Locații</a></li>
            </ul>
        </nav>
        <div class="user-controls-top">
             <div class="user-info-top"><i class="fas fa-user-circle"></i> <?= htmlspecialchars($_SESSION['fullname']) ?></div>
             <a href="logout.php" class="logout-top-button">Deconectare</a>
        </div>
    </div>
    
    <div class="main-content">
        <section>
            <header class="dashboard-header"><h1>Flota de Drone</h1></header>
            <?php if ($message): ?><div style="background: #d4edda; color: #155724; padding: 10px; margin-bottom: 20px;"><?= htmlspecialchars($message) ?></div><?php endif; ?>

            <div class="form-container">
                <h3><?= $editMode ? 'Editează Drona' : 'Adaugă Dronă Nouă' ?></h3>
                <form method="POST">
                    <input type="hidden" name="drone_id" value="<?= $editMode ? $droneToEdit['DroneID'] : '' ?>">
                    <div class="form-row">
                        <div class="form-group"><label>Model:</label><input type="text" name="model" required value="<?= $editMode ? $droneToEdit['Model'] : '' ?>"></div>
                        <div class="form-group">
                            <label>Tip Dronă:</label>
                            <select name="type">
                                <option value="transport" <?= ($editMode && $droneToEdit['Type']=='transport')?'selected':'' ?>>Transport (Livrare)</option>
                                <option value="survey" <?= ($editMode && $droneToEdit['Type']=='survey')?'selected':'' ?>>Survey (Inspecție/Mapare)</option>
                            </select>
                        </div>
                        <div class="form-group"><label>Capacitate (kg):</label><input type="number" step="0.1" name="capacity" required value="<?= $editMode ? $droneToEdit['PayloadCapacity'] : '' ?>"></div>
                    </div>
                    <div class="form-row">
                        <div class="form-group"><label>Autonomie (min):</label><input type="number" name="autonomy" required value="<?= $editMode ? $droneToEdit['AutonomyMin'] : '' ?>"></div>
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
                    <tr><th>ID</th><th>Model</th><th>Tip</th><th>Capacitate</th><th>Status</th><th>Mentenanță Rapidă</th><th>Acțiuni</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($drones as $d): ?>
                    <tr>
                        <td>#<?= $d['DroneID'] ?></td>
                        <td><strong><?= htmlspecialchars($d['Model']) ?></strong></td>
                        <td><span class="badge-type"><?= htmlspecialchars($d['Type'] ?? 'transport') ?></span></td>
                        <td><?= $d['PayloadCapacity'] ?> kg</td>
                        <td><span class="status-badge status-<?= $d['Status'] ?>"><?= $d['Status'] ?></span></td>
                        <td>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="drone_id" value="<?= $d['DroneID'] ?>">
                                <input type="hidden" name="current_status" value="<?= $d['Status'] ?>">
                                <button type="submit" name="toggle_maintenance" class="btn-small <?= $d['Status']=='mentenanta' ? 'btn-green' : 'btn-orange' ?>">
                                    <?= $d['Status']=='mentenanta' ? 'Activează' : 'Trimite în Service' ?>
                                </button>
                            </form>
                        </td>
                        <td>
                            <a href="drones.php?edit_id=<?= $d['DroneID'] ?>" style="color:#3498db;"><i class="fas fa-edit"></i></a>
                            <a href="drones.php?delete_id=<?= $d['DroneID'] ?>" style="color:#e74c3c; margin-left: 10px;" onclick="return confirm('Ștergi?');"><i class="fas fa-trash"></i></a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </section>
    </div>
</body>
</html>