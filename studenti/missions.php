<?php
require 'auth.php';
require 'db.php';

$message = '';
$messageType = '';

// Verificăm rolul
$isAdmin = (isset($_SESSION['role']) && $_SESSION['role'] === 'admin');
$currentUserId = $_SESSION['user_id'];

// --- 1. PRELUARE DRONE PENTRU ALGORITM (Trimitem asta in JS) ---
// Luăm toate detaliile tehnice pentru a le folosi în calculatorul JS
$stmtDrones = $pdo->query("SELECT DroneID, Model, PayloadCapacity, AutonomyMin FROM Drones WHERE Status = 'activa'");
$dronesList = $stmtDrones->fetchAll(PDO::FETCH_ASSOC);
// Convertim în JSON pentru a fi citit de JavaScript
$jsonDrones = json_encode($dronesList);

// --- 2. LOGICA BACKEND ---

// Aprobare / Respingere (Admin)
if ($isAdmin && isset($_POST['approve_mission'])) {
    $stmt = $pdo->prepare("UPDATE Missions SET MissionStatus = 'planificata' WHERE MissionID = ?");
    $stmt->execute([$_POST['mission_id']]);
}
if ($isAdmin && isset($_POST['delete_mission'])) {
    $stmt = $pdo->prepare("DELETE FROM Missions WHERE MissionID = ?");
    $stmt->execute([$_POST['mission_id']]);
}

// Procesare Formular (Adăugare Misiune)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_mission'])) {
    // Atenție: drone_id vine acum dintr-un input hidden, calculat de JS
    $droneId = $_POST['drone_id_hidden']; 
    $type = $_POST['type'];
    $startLatLong = $_POST['start_latlng_hidden'];
    $endLatLong = $_POST['end_latlng_hidden'];
    $duration = $_POST['duration']; 
    $cost = $_POST['cost_estimat_hidden']; // Salvăm și costul dacă vrei (opțional în DB, momentan nu avem coloană, dar îl folosim la afișare)

    if (empty($droneId)) {
        $message = "Eroare: Nu s-a găsit o dronă potrivită pentru parametrii introduși!";
        $messageType = "error";
    } else {
        $initialStatus = $isAdmin ? 'planificata' : 'in_asteptare';

        try {
            $sql = "INSERT INTO Missions (DroneID, StartTime, DurationMin, Type, StartCoord, EndCoord, MissionStatus, RequesterID) 
                    VALUES (?, NOW(), ?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$droneId, $duration, $type, $startLatLong, $endLatLong, $initialStatus, $currentUserId]);
            
            $message = $isAdmin ? "Misiune planificată cu succes!" : "Cererea a fost trimisă și drona a fost rezervată!";
            $messageType = "success";
        } catch (PDOException $e) {
            $message = "Eroare SQL: " . $e->getMessage();
            $messageType = "error";
        }
    }
}

// --- 3. PRELUARE LISTA MISIUNI ---
if ($isAdmin) {
    $sql = "SELECT m.*, d.Model, u.Username as RequesterName
            FROM Missions m 
            LEFT JOIN Drones d ON m.DroneID = d.DroneID 
            LEFT JOIN Users u ON m.RequesterID = u.UserID
            ORDER BY m.MissionID DESC";
    $stmtMissions = $pdo->query($sql);
} else {
    $sql = "SELECT m.*, d.Model 
            FROM Missions m 
            LEFT JOIN Drones d ON m.DroneID = d.DroneID 
            WHERE m.RequesterID = ?
            ORDER BY m.MissionID DESC";
    $stmtMissions = $pdo->prepare($sql);
    $stmtMissions->execute([$currentUserId]);
}
$missions = $stmtMissions->fetchAll();
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <title>DroneFleet - Misiuni Inteligente</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    
    <style>
        .data-table { width: 100%; border-collapse: collapse; margin-top: 20px; background: white; }
        .data-table th, .data-table td { padding: 10px; border-bottom: 1px solid #ddd; text-align: left; }
        .data-table th { background-color: #f8f9fa; }
        
        .planning-container { display: flex; gap: 20px; margin-bottom: 30px; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .form-section { flex: 1; }
        .map-section { flex: 2; }
        #map { height: 550px; width: 100%; border-radius: 4px; border: 1px solid #ccc; }
        
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; font-weight: bold; margin-bottom: 5px; }
        .form-group input, .form-group select { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
        .btn-submit { background-color: #3498db; color: white; border: none; padding: 12px 20px; cursor: pointer; border-radius: 4px; font-weight: bold; width: 100%; font-size: 1rem; }
        .btn-submit:disabled { background-color: #ccc; cursor: not-allowed; }

        .status-badge { padding: 5px 10px; border-radius: 15px; font-size: 0.8em; font-weight: bold; text-transform: uppercase; }
        .status-planificata { background: #e8f5e9; color: #2e7d32; }
        .status-in_asteptare { background: #fff3e0; color: #ef6c00; border: 1px solid #ffe0b2; }

        .btn-approve { background: #2ecc71; color: white; border: none; padding: 5px 10px; border-radius: 4px; cursor: pointer; }
        .btn-reject { background: #e74c3c; color: white; border: none; padding: 5px 10px; border-radius: 4px; cursor: pointer; }
        
        /* Stiluri pentru Rezultatul Calculului */
        .calculation-result {
            background: #f8f9fa;
            border-left: 5px solid #3498db;
            padding: 15px;
            margin-bottom: 15px;
            display: none; /* Ascuns inițial */
        }
        .cost-display { font-size: 1.4em; color: #27ae60; font-weight: bold; display: block; margin-top: 5px; }
        .drone-match { color: #2980b9; font-weight: bold; }
        .error-match { color: #c0392b; font-weight: bold; display: none; margin-bottom: 10px;}
    </style>
</head>
<body class="dashboard-layout">
    
    <div class="top-bar-dashboard">
        <div class="logo"><a href="home.php"><img src="logo1.png" alt="Logo"></a></div>
        <nav>
            <ul class="dashboard-nav-links">
                <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li class="active"><a href="missions.php"><i class="fas fa-route"></i> <?= $isAdmin ? 'Misiuni' : 'Cererile Mele' ?></a></li>
                <?php if($isAdmin): ?>
                    <li><a href="drones.php"><i class="fas fa-plane"></i> Drone</a></li>
                    <li><a href="users.php"><i class="fas fa-users"></i> Utilizatori</a></li>
                    <li><a href="maintenance.php"><i class="fas fa-tools"></i> Mentenanță</a></li>
                    <li><a href="locations.php"><i class="fas fa-map-marker-alt"></i> Locații</a></li>
                <?php endif; ?>
            </ul>
        </nav>
        <div class="user-controls-top">
             <div class="user-info-top"><i class="fas fa-user-circle"></i> <?= $isAdmin ? 'Admin' : 'Operator' ?></div>
             <a href="logout.php" class="logout-top-button">Deconectare</a>
        </div>
    </div>
    
    <div class="main-content">
        <section id="missions">
            <header class="dashboard-header">
                <h1><?= $isAdmin ? 'Planificare și Aprobare' : 'Solicitare Zbor Inteligent' ?></h1>
            </header>

            <?php if ($message): ?>
                <div style="background: <?= $messageType == 'success' ? '#d4edda' : '#f8d7da' ?>; color: <?= $messageType == 'success' ? '#155724' : '#721c24' ?>; padding: 10px; margin-bottom: 20px; border-radius: 4px;">
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>
            
            <div class="planning-container">
                <div class="form-section">
                    <h3><i class="fas fa-robot"></i> Configurare Misiune</h3>
                    <p style="font-size: 0.9em; color: #666;">Introduceți greutatea și alegeți traseul pe hartă. Sistemul va aloca automat cea mai bună dronă.</p>

                    <form method="POST" action="missions.php" id="missionForm">
                        
                        <div class="form-group">
                            <label>Greutate Colet (kg):</label>
                            <input type="number" name="payload_weight" id="payload_weight" step="0.1" min="0.1" max="50" placeholder="Ex: 2.5 kg" required oninput="calculateBestDrone()">
                        </div>

                        <div class="form-group">
                            <label>Tip Misiune:</label>
                            <select name="type" required>
                                <option value="livrare">Livrare</option>
                                <option value="inspectie">Inspecție</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Start:</label>
                            <input type="text" name="start_coord" id="start_address" readonly required placeholder="Selectați pe hartă..." style="background-color: #e9ecef;">
                            <input type="hidden" name="start_latlng_hidden" id="start_latlng_hidden">
                        </div>

                        <div class="form-group">
                            <label>Destinație:</label>
                            <input type="text" name="end_coord" id="end_address" readonly required placeholder="Selectați pe hartă..." style="background-color: #e9ecef;">
                            <input type="hidden" name="end_latlng_hidden" id="end_latlng_hidden">
                        </div>
                        
                        <input type="hidden" name="duration" id="duration_input" value="0">
                        <input type="hidden" name="drone_id_hidden" id="drone_id_hidden">
                        <input type="hidden" name="cost_estimat_hidden" id="cost_estimat_hidden">

                        <div id="error_box" class="error-match">
                            <i class="fas fa-times-circle"></i> Nicio dronă disponibilă pentru această distanță și greutate!
                        </div>

                        <div id="result_box" class="calculation-result">
                            <div><i class="fas fa-route"></i> Distanță: <strong id="dist_display">0</strong> km</div>
                            <div><i class="fas fa-hourglass-half"></i> Durată: <strong id="dur_display">0</strong> min</div>
                            <hr style="margin: 10px 0; border: 0; border-top: 1px solid #ddd;">
                            <div><i class="fas fa-plane"></i> Dronă Alocată: <span id="drone_name_display" class="drone-match">...</span></div>
                            <div class="cost-display">Cost: <span id="cost_display">0.00</span> RON</div>
                        </div>

                        <button type="submit" name="add_mission" id="submit_btn" class="btn-submit" disabled>
                            Confirmați și Trimiteți
                        </button>
                    </form>
                </div>

                <div class="map-section">
                    <div id="map"></div>
                </div>
            </div>

            <div style="overflow-x: auto;">
                <h3><?= $isAdmin ? 'Toate Misiunile' : 'Istoric Cereri' ?></h3>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <?php if($isAdmin): ?><th>Solicitant</th><?php endif; ?>
                            <th>Dronă Alocată</th>
                            <th>Traseu</th>
                            <th>Status</th>
                            <?php if($isAdmin): ?><th>Acțiuni</th><?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($missions as $m): ?>
                        <tr>
                            <td><?= $m['MissionID'] ?></td>
                            <?php if($isAdmin): ?>
                                <td><?= htmlspecialchars($m['RequesterName'] ?? 'N/A') ?></td>
                            <?php endif; ?>
                            <td><strong><?= htmlspecialchars($m['Model'] ?? 'N/A') ?></strong></td>
                            <td style="font-size: 0.85em;">
                                <i class="fas fa-play" style="color:green;"></i> <?= htmlspecialchars($m['StartCoord']) ?><br>
                                <i class="fas fa-stop" style="color:red;"></i> <?= htmlspecialchars($m['EndCoord']) ?>
                            </td>
                            <td>
                                <span class="status-badge status-<?= htmlspecialchars($m['MissionStatus']) ?>">
                                    <?= htmlspecialchars($m['MissionStatus']) ?>
                                </span>
                            </td>
                            <?php if($isAdmin): ?>
                            <td>
                                <?php if($m['MissionStatus'] == 'in_asteptare'): ?>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="mission_id" value="<?= $m['MissionID'] ?>">
                                        <button type="submit" name="approve_mission" class="btn-approve" title="Aprobă"><i class="fas fa-check"></i></button>
                                    </form>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="mission_id" value="<?= $m['MissionID'] ?>">
                                        <button type="submit" name="delete_mission" class="btn-reject" title="Respinge" onclick="return confirm('Respingi?');"><i class="fas fa-times"></i></button>
                                    </form>
                                <?php endif; ?>
                            </td>
                            <?php endif; ?>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        // --- 1. DATELE DESPRE DRONE DIN PHP ---
        const drones = <?= $jsonDrones; ?>; 
        console.log("Drone disponibile:", drones);

        // --- 2. CONFIGURARE HARTĂ ---
        var locationIqKey = 'pk.158719224b8587f1e2f1cd81fed13147'; 
        var map = L.map('map').setView([44.4268, 26.1025], 13);
        L.tileLayer('https://{s}-tiles.locationiq.com/v3/streets/r/{z}/{x}/{y}.png?key=' + locationIqKey, { maxZoom: 18, attribution: '© LocationIQ' }).addTo(map);

        var startMarker = null; var endMarker = null; var routeLine = null;
        var currentDuration = 0; // în minute
        var currentDistance = 0; // în km

        // --- 3. INTERACȚIUNE HARTĂ ---
        map.on('click', async function(e) {
            var lat = e.latlng.lat; var lng = e.latlng.lng;
            var coordString = lat.toFixed(5) + ", " + lng.toFixed(5);

            if (!startMarker || (startMarker && endMarker)) {
                // Reset
                if (startMarker && endMarker) { 
                    map.removeLayer(startMarker); map.removeLayer(endMarker); map.removeLayer(routeLine); 
                    endMarker = null; 
                    resetCalculation();
                }
                startMarker = L.marker([lat, lng], {icon: getIcon('green')}).addTo(map);
                document.getElementById('start_latlng_hidden').value = coordString;
                
                let addr = await getAddress(lat, lng);
                document.getElementById('start_address').value = addr;
            } else {
                endMarker = L.marker([lat, lng], {icon: getIcon('red')}).addTo(map);
                document.getElementById('end_latlng_hidden').value = coordString;
                
                let addr = await getAddress(lat, lng);
                document.getElementById('end_address').value = addr;

                routeLine = L.polyline([startMarker.getLatLng(), endMarker.getLatLng()], {color: 'blue', weight: 4}).addTo(map);
                map.fitBounds(routeLine.getBounds());
                
                // Calculare rutei
                updateRouteMetrics(startMarker.getLatLng(), endMarker.getLatLng());
            }
        });

        // --- 4. LOGICA ALGORITM SELECȚIE DRONĂ ---
        function updateRouteMetrics(p1, p2) {
            var distMeters = p1.distanceTo(p2);
            currentDistance = (distMeters / 1000); // km
            
            // Estimare: 40km/h viteza medie + 5 min decolare/aterizare
            currentDuration = Math.round((currentDistance / 40) * 60) + 5; 
            
            document.getElementById('dist_display').innerText = currentDistance.toFixed(2);
            document.getElementById('dur_display').innerText = currentDuration;
            document.getElementById('duration_input').value = currentDuration;

            // Trigger algoritm selecție
            calculateBestDrone();
        }

        function calculateBestDrone() {
            var weight = parseFloat(document.getElementById('payload_weight').value);
            
            // Dacă nu avem rută sau greutate validă, ieșim
            if (!currentDuration || !weight || weight <= 0) {
                resetCalculation();
                return;
            }

            // 1. FILTRARE: Găsim dronele capabile
            // Condiții: Capacitate >= Greutate ȘI Autonomie >= Durată (cu 10% marjă)
            let safeDuration = currentDuration * 1.1;
            
            let capableDrones = drones.filter(d => {
                return parseFloat(d.PayloadCapacity) >= weight && parseFloat(d.AutonomyMin) >= safeDuration;
            });

            // 2. SORTARE: Alegem cea mai "slabă" dronă care face față (cea mai eficientă)
            // Sortăm după capacitate crescător
            capableDrones.sort((a, b) => parseFloat(a.PayloadCapacity) - parseFloat(b.PayloadCapacity));

            var resultBox = document.getElementById('result_box');
            var errorBox = document.getElementById('error_box');
            var submitBtn = document.getElementById('submit_btn');

            if (capableDrones.length > 0) {
                let bestDrone = capableDrones[0]; // Prima din listă e cea mai eficientă
                
                // 3. CALCUL COST
                // Formula: 20 RON Start + 2 RON/km + 5 RON/kg
                let cost = 20 + (currentDistance * 2) + (weight * 5);
                
                // UI UPDATE
                document.getElementById('drone_name_display').innerText = bestDrone.Model + " (Cap: " + bestDrone.PayloadCapacity + "kg)";
                document.getElementById('cost_display').innerText = cost.toFixed(2) + " RON";
                
                // Hidden inputs for backend
                document.getElementById('drone_id_hidden').value = bestDrone.DroneID;
                document.getElementById('cost_estimat_hidden').value = cost.toFixed(2);

                resultBox.style.display = 'block';
                errorBox.style.display = 'none';
                submitBtn.disabled = false;
            } else {
                // Nicio dronă găsită
                resultBox.style.display = 'none';
                errorBox.style.display = 'block';
                submitBtn.disabled = true;
                document.getElementById('drone_id_hidden').value = "";
            }
        }

        function resetCalculation() {
            document.getElementById('result_box').style.display = 'none';
            document.getElementById('error_box').style.display = 'none';
            document.getElementById('submit_btn').disabled = true;
        }

        // --- Helpers ---
        async function getAddress(lat, lng) {
            try {
                let r = await fetch(`https://us1.locationiq.com/v1/reverse.php?key=${locationIqKey}&lat=${lat}&lon=${lng}&format=json`);
                let d = await r.json();
                return d.display_name || "Coordonate selectate";
            } catch { return lat.toFixed(5)+", "+lng.toFixed(5); }
        }

        function getIcon(c) {
            return new L.Icon({
                iconUrl: `https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-${c}.png`,
                shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
                iconSize: [25, 41], iconAnchor: [12, 41], popupAnchor: [1, -34], shadowSize: [41, 41]
            });
        }
    </script>
</body>
</html>