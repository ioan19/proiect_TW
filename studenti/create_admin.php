<?php
require 'db.php';

// Configurare user nou
$newUsername = 'admin_nou';
$newPassword = '1234'; // Parola pe care o vei folosi
$newEmail = 'admin_nou@test.com';
$role = 'admin';
$fullName = 'Administrator Test';

// 1. Generăm hash-ul parolei folosind SHA-256 (așa cum este în sistemul tău)
$passwordHash = hash('sha256', $newPassword);

try {
    // 2. Verificăm dacă există deja    
    $check = $pdo->prepare("SELECT UserID FROM Users WHERE Username = ?");
    $check->execute([$newUsername]);
    
    if ($check->fetch()) {
        // Dacă există, îi actualizăm parola
        $stmt = $pdo->prepare("UPDATE Users SET PasswordHash = ? WHERE Username = ?");
        $stmt->execute([$passwordHash, $newUsername]);
        echo "<h1>Succes!</h1><p>Parola utilizatorului <strong>$newUsername</strong> a fost resetată la <strong>$newPassword</strong>.</p>";
    } else {
        // Dacă nu există, îl creăm (folosim un ID mare, ex: 999, ca să nu ne suprapunem cu datele existente)
        $stmt = $pdo->prepare("INSERT INTO Users (UserID, Username, PasswordHash, Email, Role, FullName) VALUES (999, ?, ?, ?, ?, ?)");
        $stmt->execute([$newUsername, $passwordHash, $newEmail, $role, $fullName]);
        echo "<h1>Succes!</h1><p>Utilizatorul <strong>$newUsername</strong> a fost creat cu parola <strong>$newPassword</strong>.</p>";
    }

    echo '<br><a href="index.php">Mergi la Login</a>';

} catch (PDOException $e) {
    echo "Eroare SQL: " . $e->getMessage();
}
?>