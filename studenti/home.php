<?php session_start(); ?>
<!DOCTYPE html>
<html lang="ro">

<head>
    <meta charset="UTF-8">
    <title>DroneFleet Manager</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>

<body>
    <header class="top-nav">
        <div class="logo">
            <a href="home.php"><img src="logo1.png" alt="Logo"></a>
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

    <main>
        <section class="hero"
            style="background: linear-gradient(rgba(0,0,0,0.6), rgba(0,0,0,0.6)), url('https://images.unsplash.com/photo-1506947411487-a56738267384?auto=format&fit=crop&w=1920&q=80'); background-size: cover; height: 80vh; color: white; display: flex; align-items: center; justify-content: center; text-align: center;">
            <div class="hero-content">
                <h1 style="font-size: 3.5em; margin-bottom: 20px;">Soluții Geospațiale & Logistice Avansate</h1>
                <p style="font-size: 1.2em; max-width: 800px; margin: 0 auto 30px;">Gestionăm flota viitorului: Livrări
                    precise, Inspecții tehnice și Cartografiere 3D.</p>
                <a href="<?= isset($_SESSION['user_id']) ? 'dashboard.php' : 'register.php' ?>" class="btn-hero"
                    style="background: #3498db; color: white; padding: 15px 30px; border-radius: 30px; text-decoration: none; font-weight: bold;">
                    <?= isset($_SESSION['user_id']) ? 'Accesează Dashboard' : 'Devino Operator' ?>
                </a>
            </div>
        </section>

        <section class="services-section" style="padding: 60px 20px;">
            <h2 style="text-align:center; margin-bottom:40px; color: #2c3e50;">Serviciile Noastre</h2>
            <div class="services-container"
                style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 30px; max-width: 1200px; margin: 0 auto;">

                <div class="service-card"
                    style="background: white; padding: 30px; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.1);">
                    <i class="fas fa-box-open service-icon delivery-icon"
                        style="font-size: 3em; color: #2ecc71; margin-bottom: 20px;"></i>
                    <h3>Livrări Comerciale</h3>
                    <p>Transport rapid de colete ușoare și medii folosind flota noastră de drone DJI FlyCart și Amazon
                        MK30.</p>
                </div>

                <div class="service-card"
                    style="background: white; padding: 30px; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.1);">
                    <i class="fas fa-search-location service-icon inspection-icon"
                        style="font-size: 3em; color: #f1c40f; margin-bottom: 20px;"></i>
                    <h3>Inspecții Industriale</h3>
                    <p>Verificarea infrastructurii critice (poduri, antene, eoliene) cu drone de înaltă precizie pe bază
                        de arie.</p>
                </div>

                <div class="service-card"
                    style="background: white; padding: 30px; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.1);">
                    <i class="fas fa-layer-group service-icon mapping-icon"
                        style="font-size: 3em; color: #9b59b6; margin-bottom: 20px;"></i>
                    <h3>Advanced Geospatial Services</h3>
                    <p>Cartografiere 3D, fotogrammetrie și analize topografice folosind drone Wingtra și DJI Matrice.
                    </p>
                </div>

            </div>
        </section>
    </main>

    <footer class="site-footer">
        <p>&copy; 2025 DroneFleet Manager. Toate drepturile rezervate.</p>
        <p>Contact: <a href="mailto:support@dronefleet.com">support@dronefleet.com</a></p>
    </footer>
</body>

</html>