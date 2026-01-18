<?php
session_start();
require 'db.php';

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT UserID, Username, PasswordHash, Role, FullName FROM Users WHERE Username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user) {
        $inputHash = hash('sha256', $password);
        if ($inputHash === $user['PasswordHash']) {
            $_SESSION['user_id'] = $user['UserID'];
            $_SESSION['username'] = $user['Username'];
            $_SESSION['role'] = $user['Role'];
            $_SESSION['fullname'] = $user['FullName'];
            header("Location: dashboard.php");
            exit();
        } else {
            $error = "Parolă incorectă!";
        }
    } else {
        $error = "Utilizatorul nu există!";
    }
}
?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <title>Login - DroneFleet Manager</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body class="auth-page">
    <div class="auth-container">
        <img src="logo1.png" alt="DroneFleet Logo" class="auth-logo">
        <h2>Bine ai venit!</h2>
        
        <?php if ($error): ?>
            <div class="error-banner"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <?php if (isset($_GET['registered'])): ?>
            <div class="success-banner"><i class="fas fa-check-circle"></i> Cont creat! Te poți autentifica.</div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label><i class="fas fa-user"></i> Utilizator</label>
                <input type="text" name="username" placeholder="Introduceți username-ul" required>
            </div>
            
            <div class="form-group">
                <label><i class="fas fa-lock"></i> Parolă</label>
                <input type="password" name="password" placeholder="Introduceți parola" required>
            </div>
            
            <button type="submit" class="btn-auth">Autentificare <i class="fas fa-arrow-right"></i></button>
        </form>
        
        <div class="auth-links">
            <p>Nu ai cont? <a href="register.php">Creează unul acum</a></p>
            <p style="margin-top: 10px;"><a href="home.php"><i class="fas fa-home"></i> Înapoi la Site</a></p>
        </div>
    </div>
</body>
</html>