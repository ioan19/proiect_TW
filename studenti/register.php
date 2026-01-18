<?php
require 'db.php';
$msg = '';
$msgType = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user = trim($_POST['username']);
    $pass = $_POST['password'];
    $email = trim($_POST['email']);
    $full = trim($_POST['fullname']);
    
    // Verificăm existența
    $check = $pdo->prepare("SELECT UserID FROM Users WHERE Username = ? OR Email = ?");
    $check->execute([$user, $email]);
    
    if ($check->rowCount() > 0) {
        $msg = "Acest Username sau Email este deja folosit!";
        $msgType = "error";
    } else {
        $hash = hash('sha256', $pass);
        $nextId = $pdo->query("SELECT MAX(UserID) FROM Users")->fetchColumn() + 1;
        
        $stmt = $pdo->prepare("INSERT INTO Users (UserID, Username, PasswordHash, Email, Role, FullName) VALUES (?, ?, ?, ?, 'operator', ?)");
        if ($stmt->execute([$nextId, $user, $hash, $email, $full])) {
            header("Location: index.php?registered=1");
            exit();
        } else {
            $msg = "Eroare la baza de date.";
            $msgType = "error";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <title>Înregistrare - DroneFleet Manager</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body class="auth-page">
    <div class="auth-container">
        <h2>Creează Cont Operator</h2>
        <p style="color:#7f8c8d; margin-bottom:20px;">Alătură-te echipei DroneFleet</p>

        <?php if ($msg): ?>
            <div class="<?= $msgType == 'error' ? 'error-banner' : 'success-banner' ?>">
                <i class="fas fa-info-circle"></i> <?= htmlspecialchars($msg) ?>
            </div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label>Nume Complet</label>
                <input type="text" name="fullname" placeholder="Ex: Ion Popescu" required>
            </div>

            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" placeholder="Ex: ipopescu" required>
            </div>

            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" placeholder="email@domeniu.com" required>
            </div>

            <div class="form-group">
                <label>Parolă</label>
                <input type="password" name="password" placeholder="Minim 6 caractere" required>
            </div>
            
            <button type="submit" class="btn-auth" style="background: linear-gradient(to right, #2ecc71, #27ae60);">
                Înregistrează-te
            </button>
        </form>
        
        <div class="auth-links">
            <p>Ai deja cont? <a href="index.php">Autentifică-te aici</a></p>
        </div>
    </div>
</body>
</html>