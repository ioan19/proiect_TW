<?php
session_start();

// Verificăm dacă variabila de sesiune user_id există
if (!isset($_SESSION['user_id'])) {
    // Dacă nu e logat, îl trimitem la pagina de login
    header("Location: index.php");
    exit();
}
?>