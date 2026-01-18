<?php
session_start();
?>
<!DOCTYPE html>
<html lang="ro">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DroneFleet Manager - Estimare Cost Livrare</title>
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
                <?php if (isset($_SESSION['user_id'])): ?>
                    <li><a href="dashboard.php" class="cta-button">Dashboard</a></li>
                <?php else: ?>
                    <li><a href="index.php" class="cta-button">Intră în Sistem (Login)</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </header>

    <main class="landing-content">
        <h1>Estimator de Cost pentru Livrări cu Drone</h1>
        <p>Alegeți orașul de plecare și destinația pentru a obține o estimare de cost logistic bazată pe parametrii
            flotei.</p>

        <section class="delivery-simulator">
            <h2>Calculează Cost Livrare 📦</h2>

            <div class="simulator-form-container">
                <form id="delivery-form">
                    <select id="oras-plecare" required>
                        <option value="">-- Alege Oras Plecare --</option>
                        <option value="Bucuresti">București</option>
                        <option value="Cluj-Napoca">Cluj-Napoca</option>
                        <option value="Timisoara">Timișoara</option>
                        <option value="Iasi">Iași</option>
                        <option value="Constanta">Constanța</option>
                    </select>

                    <select id="oras-destinatie" required>
                        <option value="">-- Alege Oras Destinatie --</option>
                        <option value="Bucuresti">București</option>
                        <option value="Cluj-Napoca">Cluj-Napoca</option>
                        <option value="Timisoara">Timișoara</option>
                        <option value="Iasi">Iași</option>
                        <option value="Constanta">Constanța</option>
                    </select>

                    <input type="number" id="greutate-colet" placeholder="Greutate Colet (kg, max 20)" min="0.1"
                        max="20" step="0.1" required>

                    <button type="submit">Calculează Cost</button>
                </form>

                <div id="rezultat-simulare" class="result-box">
                    <i class="fas fa-info-circle"></i> Vă rugăm introduceți datele de livrare pentru a obține o estimare
                    de cost.
                </div>
            </div>
        </section>

        <a href="home.php" class="cta-large">Înapoi la Servicii</a>
    </main>

    <footer class="site-footer">
        <p>&copy; 2025 DroneFleet Manager. Toate drepturile rezervate.</p>
        <p>Contact: <a href="mailto:support@dronefleet.com">support@dronefleet.com</a></p>
    </footer>

    <script src="app.js"></script>
</body>

</html>