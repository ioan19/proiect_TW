<?php
require 'auth.php';
require 'db.php';

// --- SECURITATE: Doar Adminii au acces aici ---
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: dashboard.php");
    exit();
}

$message = '';
$messageType = '';

// Procesare Formular (Adăugare)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_user'])) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $email = trim($_POST['email']);
    $fullname = trim($_POST['fullname']);
    $role = $_POST['role'];

    // Verificăm duplicat
    $stmt = $pdo->prepare("SELECT UserID FROM Users WHERE Username = ?");
    $stmt->execute([$username]);
    if ($stmt->fetch()) {
        $message = "Userul există deja!";
        $messageType = "error";
    } else {
        $hash = hash('sha256', $password);
        // Generare ID
        $nextId = $pdo->query("SELECT MAX(UserID) FROM Users")->fetchColumn() + 1;
        
        $stmt = $pdo->prepare("INSERT INTO Users (UserID, Username, PasswordHash, Email, Role, FullName) VALUES (?, ?, ?, ?, ?, ?)");
        if ($stmt->execute([$nextId, $username, $hash, $email, $role, $fullname])) {
            $message = "Utilizator creat!";
            $messageType = "success";
        }
    }
}

// Ștergere
if (isset($_GET['delete_id'])) {
    if ($_GET['delete_id'] != $_SESSION['user_id']) {
        $pdo->prepare("DELETE FROM Users WHERE UserID = ?")->execute([$_GET['delete_id']]);
        $message = "Utilizator șters!";
        $messageType = "success";
    } else {
        $message = "Nu te poți șterge singur!";
        $messageType = "error";
    }
}

$users = $pdo->query("SELECT * FROM Users ORDER BY UserID ASC")->fetchAll();
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <title>Gestiune Utilizatori</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .data-table { width: 100%; border-collapse: collapse; margin-top: 20px; background: white; }
        .data-table th, .data-table td { padding: 12px; border-bottom: 1px solid #ddd; text-align: left; }
        .data-table th { background-color: #f8f9fa; }
        .form-container { background: #fff; padding: 20px; border-radius: 8px; margin-bottom: 20px; border-left: 5px solid #3498db; }
        input, select { padding: 8px; margin: 5px 0; width: 100%; box-sizing: border-box; }
        .row { display: flex; gap: 10px; } .col { flex: 1; }
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
                <li class="active"><a href="users.php"><i class="fas fa-users"></i> Utilizatori</a></li>
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
            <header class="dashboard-header"><h1>Utilizatori</h1></header>
            <?php if ($message): ?><p style="padding: 10px; background: #eee;"><?= $message ?></p><?php endif; ?>

            <div class="form-container">
                <h3>Adaugă Utilizator</h3>
                <form method="POST">
                    <div class="row">
                        <div class="col"><input type="text" name="fullname" placeholder="Nume Complet" required></div>
                        <div class="col"><input type="text" name="username" placeholder="Username" required></div>
                    </div>
                    <div class="row">
                        <div class="col"><input type="email" name="email" placeholder="Email" required></div>
                        <div class="col"><input type="password" name="password" placeholder="Parola" required></div>
                        <div class="col">
                            <select name="role">
                                <option value="operator">Operator</option>
                                <option value="admin">Administrator</option>
                            </select>
                        </div>
                    </div>
                    <button type="submit" name="add_user" style="margin-top:10px; padding:10px 20px; background:#2ecc71; color:white; border:none; cursor:pointer;">Salvează</button>
                </form>
            </div>

            <table class="data-table">
                <thead><tr><th>ID</th><th>User</th><th>Email</th><th>Rol</th><th>Acțiuni</th></tr></thead>
                <tbody>
                    <?php foreach ($users as $u): ?>
                    <tr>
                        <td><?= $u['UserID'] ?></td>
                        <td><?= htmlspecialchars($u['Username']) ?></td>
                        <td><?= htmlspecialchars($u['Email']) ?></td>
                        <td><?= htmlspecialchars($u['Role']) ?></td>
                        <td>
                            <?php if($u['UserID'] != $_SESSION['user_id']): ?>
                                <a href="users.php?delete_id=<?= $u['UserID'] ?>" style="color:red;" onclick="return confirm('Ștergi?');"><i class="fas fa-trash"></i></a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </section>
    </div>
</body>
</html>