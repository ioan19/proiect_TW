<?php
session_start();
?>
<!DOCTYPE html>
<html lang="ro">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Despre Noi - DroneFleet Manager</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

    <style>
        /* Stiluri specifice pentru pagina About Us îmbunătățită */
        .about-hero {
            background: linear-gradient(rgba(44, 62, 80, 0.8), rgba(44, 62, 80, 0.8)), url('https://images.unsplash.com/photo-1508614589041-895b8c9d7ef5?auto=format&fit=crop&w=1920&q=80');
            background-size: cover;
            background-position: center;
            color: white;
            text-align: center;
            padding: 80px 20px;
            margin-bottom: 40px;
        }

        .about-hero h1 {
            font-size: 3em;
            margin-bottom: 15px;
        }

        .about-hero p {
            font-size: 1.2em;
            max-width: 800px;
            margin: 0 auto;
            opacity: 0.9;
        }

        .about-container {
            max-width: 1100px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .about-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            margin-bottom: 60px;
        }

        .about-card {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            text-align: center;
            transition: transform 0.3s ease;
        }

        .about-card:hover {
            transform: translateY(-5px);
        }

        .about-card i {
            font-size: 2.5em;
            color: #3498db;
            margin-bottom: 20px;
        }

        .about-card h3 {
            margin-bottom: 15px;
            color: #2c3e50;
        }

        .about-card p {
            color: #666;
            line-height: 1.6;
        }

        .stats-section {
            background-color: #2c3e50;
            color: white;
            padding: 40px 0;
            margin: 40px 0;
            text-align: center;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            max-width: 1100px;
            margin: 0 auto;
        }

        .stat-item h2 {
            font-size: 2.5em;
            color: #3498db;
            margin: 0;
        }

        .stat-item p {
            font-size: 1.1em;
            opacity: 0.8;
        }

        .team-section {
            text-align: center;
            margin-bottom: 60px;
        }

        .team-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 30px;
            margin-top: 30px;
        }

        .team-member img {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #3498db;
            margin-bottom: 15px;
        }

        .team-member h4 {
            margin: 10px 0 5px;
            color: #2c3e50;
        }

        .team-member span {
            color: #7f8c8d;
            font-size: 0.9em;
            font-weight: bold;
        }
    </style>
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
                <li><a href="about.php" class="active">Despre Noi</a></li>
                <li><a href="contact.php">Contact</a></li>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <li><a href="dashboard.php" class="cta-button">Dashboard
                            (<?= htmlspecialchars($_SESSION['username']) ?>)</a></li>
                <?php else: ?>
                    <li><a href="index.php" class="cta-button">Intră în Sistem (Login)</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </header>

    <div class="about-hero">
        <h1>Viitorul Logisticii Aeriene</h1>
        <p>DroneFleet Manager este platforma lider în gestionarea și automatizarea flotelor de drone comerciale,
            transformând modul în care companiile interacționează cu spațiul aerian.</p>
    </div>

    <main class="page-content about-container">

        <section style="margin-bottom: 50px; text-align: center;">
            <h2 style="color: #2c3e50; margin-bottom: 20px;">Cine Suntem?</h2>
            <p style="font-size: 1.1em; line-height: 1.8; color: #555; max-width: 900px; margin: 0 auto;">
                Lansat inițial ca un proiect de cercetare academică, <strong>DroneFleet Manager</strong> a evoluat
                într-o soluție software completă dedicată optimizării operațiunilor UAV (Unmanned Aerial Vehicles).
                Suntem dedicați optimizării operațiunilor cu drone, oferind o platformă robustă care asigură
                conformitatea, siguranța și eficiența misiunilor de livrare, inspecție și cartografiere.
                Credem într-un viitor în care transportul autonom va reduce congestia traficului urban și va micșora
                amprenta de carbon a logisticii tradiționale.
            </p>
        </section>

        <section class="about-grid">
            <div class="about-card">
                <i class="fas fa-microchip"></i>
                <h3>Inovație Tehnologică</h3>
                <p>Utilizăm algoritmi avansați pentru calcularea rutelor optime și monitorizarea în timp real a stării
                    tehnice a fiecărei drone din flotă.</p>
            </div>
            <div class="about-card">
                <i class="fas fa-shield-alt"></i>
                <h3>Siguranță și Conformitate</h3>
                <p>Platforma noastră respectă cele mai stricte reglementări aeronautice, asigurând că fiecare zbor este
                    autorizat și monitorizat corespunzător.</p>
            </div>
            <div class="about-card">
                <i class="fas fa-leaf"></i>
                <h3>Sustenabilitate</h3>
                <p>Promovăm utilizarea dronelor electrice pentru a reduce emisiile de CO2 și pentru a crea un sistem
                    logistic prietenos cu mediul înconjurător.</p>
            </div>
        </section>

    </main>

    <div class="stats-section">
        <div class="stats-grid">
            <div class="stat-item">
                <h2>50+</h2>
                <p>Drone în Flotă</p>
            </div>
            <div class="stat-item">
                <h2>1.2k</h2>
                <p>Misiuni Completate</p>
            </div>
            <div class="stat-item">
                <h2>100%</h2>
                <p>Siguranță Zbor</p>
            </div>
            <div class="stat-item">
                <h2>24/7</h2>
                <p>Monitorizare Activă</p>
            </div>
        </div>
    </div>

    <div class="about-container">
        <section class="team-section">
            <h2 style="color: #2c3e50;">Echipa Noastră</h2>

            <div class="team-grid">
                <div class="team-member">
                    <img src="Corjuc_Ioan.jpg" alt="CEO">
                    <h4>Corjuc Ioan</h4>
                    <span>Fondator & CEO</span>
                    <p style="font-size: 0.9em; margin-top: 10px; color: #777;"></p>
                </div>

            </div>
        </section>
    </div>

    <footer class="site-footer">
        <p>&copy; 2025 DroneFleet Manager. Toate drepturile rezervate.</p>
        <p>Contact: <a href="mailto:support@dronefleet.com">support@dronefleet.com</a></p>
    </footer>
</body>

</html>