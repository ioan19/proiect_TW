<?php
session_start();
?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DroneFleet Manager - Acasă</title>
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
                <li><a href="home.php" class="active">Acasă</a></li>
                
                <li><a href="about.php">Despre Noi</a></li>
                <li><a href="contact.php">Contact</a></li>

                <?php if (isset($_SESSION['user_id'])): ?>
                    <li><a href="dashboard.php" class="cta-button">Dashboard (<?= htmlspecialchars($_SESSION['username']) ?>)</a></li>
                <?php else: ?>
                    <li><a href="index.php" class="cta-button">Intră în Sistem (Login)</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </header>

    <main class="landing-content">
        <h1>Management Aerian Inteligent</h1>
        <p>DroneFleet Manager este platforma dedicată optimizării și monitorizării în timp real a flotei de Sisteme Aeriene fără Pilot (UAS).</p>
        
        <section class="drone-gallery">
            <img src="poza1.jpg" alt="Dronă profesională pe suprafață de lemn">
            <img src="poza2.jpg" alt="Dronă în zbor la altitudine joasă">
            <img src="poza3.jpg" alt="Dronă pliată pe fundal alb">
        </section>
        
        <section class="system-presentation">
            <h2>Soluții UAV</h2>

            <div class="service-card">
                <div>
                    <i class="fas fa-box-open service-icon delivery-icon"></i>
                    <h3>Fast and Efficient Delivery</h3>
                    <p>Implementăm soluții de livrare autonomă optimizată pentru mediul urban și industrial. Capacitatea de transport a flotei ajunge până la 20 kg per unitate.</p>
                </div>
                <a href="delivery_simulator.html" class="simulator-button">Estimează Costul Livrării</a>
            </div>
            
            <div class="service-card">
                <i class="fas fa-search-location service-icon inspection-icon"></i>
                <h3>Precision Infrastructure Inspections</h3>
                <p>Misiuni specializate pentru verificarea detaliată a rețelelor electrice, parcurilor eoliene și construcțiilor, asigurând conformitatea și reducând riscurile operaționale.</p>
            </div>

            <div class="service-card">
                <i class="fas fa-map service-icon mapping-icon"></i>
                <h3>Advanced Geospatial Services</h3>
                <p>Generăm modele 3D de înaltă rezoluție și ortofotoplanuri precise, esențiale pentru topografie, cadastru și planificare strategică a proiectelor de dezvoltare.</p>
            </div>
        </section>
        
        <?php if (isset($_SESSION['user_id'])): ?>
             <a href="dashboard.php" class="cta-large">Gestionează Flota Ta Acum</a>
        <?php else: ?>
             <a href="index.php" class="cta-large">Gestionează Flota Ta Acum</a>
        <?php endif; ?>
    </main>
    
    <footer class="site-footer">
        <p>&copy; 2025 DroneFleet Manager. Toate drepturile rezervate. Contact: <a href="mailto:support@dronefleet.com">support@dronefleet.com</a></p>
    </footer>

    <script src="app.js"></script> 
</body>
</html>