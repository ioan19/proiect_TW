<?php
session_start();
?>
<!DOCTYPE html>
<html lang="ro">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact - DroneFleet Manager</title>
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
                <li><a href="contact.php" class="active">Contact</a></li>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <li><a href="dashboard.php" class="cta-button">Dashboard
                            (<?= htmlspecialchars($_SESSION['username']) ?>)</a></li>
                <?php else: ?>
                    <li><a href="index.php" class="cta-button">Intră în Sistem (Login)</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </header>

    <main class="page-content contact-page">
        <h1>Contactați Echipa de Suport Tehnic și Parteneriate</h1>
        <p>Suntem disponibili pentru a discuta despre implementarea sistemului, suport tehnic personalizat sau
            oportunități de colaborare.</p>

        <section class="contact-details">
            <div class="contact-item">
                <i class="fas fa-phone"></i>
                <p><strong>Telefon:</strong> +40 753 907 120</p>
            </div>
            <div class="contact-item">
                <i class="fas fa-envelope"></i>
                <p><strong>Email:</strong> support@dronefleet.com</p>
            </div>
            <div class="contact-item">
                <i class="fas fa-map-marker-alt"></i>
                <p><strong>Adresă:</strong> Splaiul Independentei 313</p>
            </div>
        </section>

        <form class="contact-form">
            <input type="text" placeholder="Numele dumneavoastră" required>
            <input type="email" placeholder="Adresa de Email" required>
            <textarea placeholder="Mesajul dumneavoastră..." rows="5" required></textarea>
            <button type="submit">Trimite Mesajul</button>
        </form>
    </main>
    <footer class="site-footer">
        <p>&copy; 2025 DroneFleet Manager. Toate drepturile rezervate.</p>
        <p>Contact: <a href="mailto:support@dronefleet.com">support@dronefleet.com</a></p>
    </footer>
</body>

</html>