<?php
session_start();
require 'db.php';

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Căutăm utilizatorul în baza de date
    $stmt = $pdo->prepare("SELECT UserID, Username, PasswordHash, Role, FullName FROM Users WHERE Username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user) {
        // Verificăm parola (presupunând SHA-256 conform dump-ului SQL)
        $inputHash = hash('sha256', $password);
        
        // Comparăm hash-ul calculat cu cel din baza de date
        if ($inputHash === $user['PasswordHash']) {
            // Login reușit: Setăm sesiunea
            $_SESSION['user_id'] = $user['UserID'];
            $_SESSION['username'] = $user['Username'];
            $_SESSION['role'] = $user['Role'];
            $_SESSION['fullname'] = $user['FullName'];

            // Redirecționare către Dashboard
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DroneFleet Manager - Login</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <header class="top-nav">
        <div class="logo">
            <a href="home.php">
                <img src="logo1.png" alt="DroneFleet Manager Logo">
            </a>
        </div>
        <nav class="main-menu">
            <ul>
                <li><a href="home.php">Acasă</a></li>
                <li><a href="about.php">Despre Noi</a></li>
                <li><a href="contact.php">Contact</a></li>
                <li><a href="index.php" class="cta-button active-login">Login</a></li>
            </ul>
        </nav>
    </header>

    <main class="landing-content">
        <h1>Autentificare în Sistemul DroneFleet Manager</h1>
        <p>Introduceți credențialele pentru a accesa tabloul de bord al flotei.</p>
        
        <div class="login-form-placeholder">
            <?php if ($error): ?>
                <div style="color: red; margin-bottom: 10px; font-weight: bold;">
                    <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="index.php">
                <input type="text" name="username" placeholder="Nume Utilizator" required>
                <input type="password" name="password" placeholder="Parolă" required>
                <button type="submit">Autentificare</button>
            </form>
            <p style="margin-top: 15px; font-size: 0.9em;"><a href="home.html">Înapoi la pagina principală</a></p>
        </div>
    </main>
    <footer class="site-footer">
        <p>&copy; 2025 DroneFleet Manager. Toate drepturile rezervate.</p>
    </footer>
</body>
</html>