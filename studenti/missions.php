<?php
require 'auth.php';
require 'db.php';

$isAdmin = (isset($_SESSION['role']) && $_SESSION['role'] === 'admin');
$userId = $_SESSION['user_id'];
$message = '';

// 1. PRELUĂM DRONELE PENTRU JAVASCRIPT
// Acum avem coloana 'Type' corectă în baza de date
$drones = $pdo->query("SELECT DroneID, Model, Type, PayloadCapacity, AutonomyMin FROM Drones WHERE Status = 'activa'")->fetchAll(PDO::FETCH_ASSOC);
$jsonDrones = json_encode($drones);

// 2. PROCESARE FORMULAR (Salvare Misiune)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_mission'])) {
    $droneId = $_POST['drone_id_hidden'];
    $type = $_POST['type'];
    $start = $_POST['start_latlng_hidden'];
    $end = $_POST['end_latlng_hidden'];
    $dur = $_POST['duration'];
    
    // Adminul planifică direct, Operatorul face cerere
    $status = $isAdmin ? 'planificata' : 'in_asteptare';

    if(!empty($droneId)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO Missions (DroneID, StartTime, DurationMin, Type, StartCoord, EndCoord, MissionStatus, RequesterID) VALUES (?, NOW(), ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$droneId, $dur, $type, $start, $end, $status, $userId]);
            
            $message = $isAdmin ? "Misiune planificată cu succes!" : "Cererea ta a fost trimisă!";
            $msgType = "success";
        } catch (PDOException $e) {
            $message = "Eroare: " . $e->getMessage();
            $msgType = "error";
        }
    } else {
        $message = "Eroare: Nu a fost selectată nicio dronă validă.";
    }
}

// 3. LOGICA ADMIN (Aprobare/Stergere)
if ($isAdmin && isset($_POST['approve_mission'])) {
    $pdo->prepare("UPDATE Missions SET MissionStatus = 'planificata' WHERE MissionID = ?")->execute([$_POST['mission_id']]);
}
if ($isAdmin && isset($_POST['delete_mission'])) {
    $pdo->prepare("DELETE FROM Missions WHERE MissionID = ?")->execute([$_POST['mission_id']]);
}

// 4. PRELUARE ISTORIC MISIUNI
if ($isAdmin) {
    $sql = "SELECT m.*, d.Model, u.Username as RequesterName FROM Missions m LEFT JOIN Drones d ON m.DroneID = d.DroneID LEFT JOIN Users u ON m.RequesterID = u.UserID ORDER BY m.MissionID DESC";
    $missions = $pdo->query($sql)->fetchAll();
} else {
    $stmt = $pdo->prepare("SELECT m.*, d.Model FROM Missions m LEFT JOIN Drones d ON m.DroneID = d.DroneID WHERE m.RequesterID = ? ORDER BY m.MissionID DESC");
    $stmt->execute([$userId]);
    $missions = $stmt->fetchAll();
}
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <title>Misiuni - DroneFleet</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    
    <style>
        .planning-container { display: flex; gap: 20px; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); margin-bottom: 30px; }
        .form-section { flex: 1; }
        .map-section { flex: 2; }
        #map { height: 500px; width: 100%; border-radius: 4px; border: 1px solid #ccc; z-index: 1; }
        
        .data-table { width: 100%; border-collapse: collapse; margin-top: 20px; background: white; }
        .data-table th, .data-table td { padding: 10px; border-bottom: 1px solid #ddd; text-align: left; }
        .data-table th { background-color: #f8f9fa; }
        
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; font-weight: bold; margin-bottom: 5px; color: #34495e; }
        input, select { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
        
        .calculation-result { background: #f8f9fa; border-left: 5px solid #3498db; padding: 15px; margin-top: 15px; display: none; }
        .status-badge { padding: 4px 8px; border-radius: 4px; font-size: 0.85em; font-weight: bold; text-transform: uppercase; }
        .status-planificata { background: #e8f5e9; color: #2e7d32; }
        .status-in_asteptare { background: #fff3e0; color: #ef6c00; }
        
        .btn-action { padding: 5px 10px; border: none; border-radius: 3px; cursor: pointer; color: white; margin-right: 5px; }
        .btn-yes { background: #27ae60; }
        .btn-no { background: #c0392b; }
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
             <div class="user-info-top"><i class="fas fa-user-circle"></i> <?= htmlspecialchars($_SESSION['fullname']) ?></div>
             <a href="logout.php" class="logout-top-button">Deconectare</a>
        </div>
    </div>
    
    <div class="main-content">
        <section>
            <header class="dashboard-header">
                <h1><?= $isAdmin ? 'Planificare Misiuni' : 'Solicitare Misiune Nouă' ?></h1>
            </header>
            
            <?php if ($message): ?>
                <div style="background: #d4edda; color: #155724; padding: 15px; margin-bottom: 20px; border-radius: 4px;">
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>

            <div class="planning-container">
                <div class="form-section">
                    <h3>Configurare Parametri</h3>
                    <p style="font-size: 0.9em; color: #7f8c8d; margin-bottom: 20px;">
                        Selectați tipul misiunii și marcați punctele pe hartă.
                    </p>

                    <form method="POST" id="missionForm">
                        <div class="form-group">
                            <label>Tip Misiune:</label>
                            <select name="type" id="missionType">
                                <option value="livrare">Livrare (Transport)</option>
                                <option value="inspectie">Inspecție Tehnică (Survey)</option>
                                <option value="mapare_3d">Advanced Geospatial (3D)</option>
                            </select>
                        </div>

                        <div class="form-group" id="weightGroup">
                            <label>Greutate Colet (kg):</label>
                            <input type="number" id="payload_weight" step="0.1" value="1.0" min="0.1" max="50">
                        </div>

                        <div class="form-group">
                            <label>Coordonate:</label>
                            <input type="text" id="start_address" readonly placeholder="Click Punct A pe hartă" style="background:#eef; margin-bottom: 5px;">
                            <input type="text" id="end_address" readonly placeholder="Click Punct B pe hartă" style="background:#eef;">
                        </div>

                        <input type="hidden" name="drone_id_hidden" id="drone_id_hidden">
                        <input type="hidden" name="duration" id="duration_input">
                        <input type="hidden" name="start_latlng_hidden" id="start_latlng_hidden">
                        <input type="hidden" name="end_latlng_hidden" id="end_latlng_hidden">

                        <div id="result_box" class="calculation-result">
                            <p><strong>Dronă Alocată:</strong> <span id="drone_name_display" style="color: #2980b9;">-</span></p>
                            <p><strong id="info_label">Distanță:</strong> <span id="info_value">-</span></p>
                            <p><strong>Durată Estimat:</strong> <span id="dur_display">-</span></p>
                            <p style="font-size: 1.2em; color: #27ae60; margin-top: 10px;"><strong>Cost: <span id="cost_display">0.00</span> RON</strong></p>
                        </div>
                        
                        <div id="error_box" style="color: #c0392b; font-weight: bold; margin-top: 15px; display: none;"></div>

                        <button type="submit" name="add_mission" id="submit_btn" disabled class="btn-auth" style="margin-top: 20px; background: #3498db; color: white; border: none; padding: 12px; border-radius: 5px; cursor: pointer; width: 100%;">
                            <?= $isAdmin ? 'Planifică Misiunea' : 'Trimite Cererea' ?>
                        </button>
                    </form>
                </div>

                <div class="map-section">
                    <div id="map"></div>
                </div>
            </div>

            <div style="overflow-x: auto;">
                <h3><?= $isAdmin ? 'Toate Misiunile' : 'Istoric Misiuni' ?></h3>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <?php if($isAdmin): ?><th>Solicitant</th><?php endif; ?>
                            <th>Dronă</th>
                            <th>Tip</th>
                            <th>Status</th>
                            <?php if($isAdmin): ?><th>Acțiuni</th><?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($missions as $m): ?>
                        <tr>
                            <td>#<?= $m['MissionID'] ?></td>
                            <?php if($isAdmin): ?><td><?= htmlspecialchars($m['RequesterName'] ?? 'N/A') ?></td><?php endif; ?>
                            <td><strong><?= htmlspecialchars($m['Model'] ?? 'N/A') ?></strong></td>
                            <td>
                                <?php 
                                    $displayType = $m['Type'];
                                    if($m['Type'] == 'mapare_3d') $displayType = 'GeoSpatial 3D';
                                    echo htmlspecialchars(ucfirst($displayType)); 
                                ?>
                            </td>
                            <td><span class="status-badge status-<?= $m['MissionStatus'] ?>"><?= $m['MissionStatus'] ?></span></td>
                            <?php if($isAdmin): ?>
                            <td>
                                <?php if($m['MissionStatus'] == 'in_asteptare'): ?>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="mission_id" value="<?= $m['MissionID'] ?>">
                                        <button type="submit" name="approve_mission" class="btn-action btn-yes" title="Aprobă"><i class="fas fa-check"></i></button>
                                    </form>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="mission_id" value="<?= $m['MissionID'] ?>">
                                        <button type="submit" name="delete_mission" class="btn-action btn-no" title="Respinge" onclick="return confirm('Sigur?');"><i class="fas fa-times"></i></button>
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

    <script>
        // Datele despre drone din PHP
        const availableDrones = <?= $jsonDrones ?>;
    </script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Inițializare Hartă
            var locationIqKey = 'pk.158719224b8587f1e2f1cd81fed13147'; 
            var map = L.map('map').setView([44.4268, 26.1025], 13);
            
            L.tileLayer('https://{s}-tiles.locationiq.com/v3/streets/r/{z}/{x}/{y}.png?key=' + locationIqKey, {
                maxZoom: 18,
                attribution: '&copy; LocationIQ'
            }).addTo(map);

            var startMarker = null, endMarker = null, shapeLayer = null;

            // Referințe DOM
            const typeSelect = document.getElementById('missionType');
            const weightInput = document.getElementById('payload_weight');
            const weightGroup = document.getElementById('weightGroup');
            
            // Ascultători
            map.on('click', handleMapClick);
            typeSelect.addEventListener('change', function() {
                if(this.value === 'livrare') {
                    weightGroup.style.display = 'block';
                } else {
                    weightGroup.style.display = 'none';
                }
                drawShapeAndCalculate();
            });
            weightInput.addEventListener('input', drawShapeAndCalculate);

            function handleMapClick(e) {
                var latlng = e.latlng;
                var coordStr = latlng.lat.toFixed(5) + ", " + latlng.lng.toFixed(5);

                if (!startMarker || (startMarker && endMarker)) {
                    // Reset
                    if(startMarker) map.removeLayer(startMarker);
                    if(endMarker) map.removeLayer(endMarker);
                    if(shapeLayer) map.removeLayer(shapeLayer);
                    endMarker = null; shapeLayer = null;

                    startMarker = L.marker(latlng, {icon: getIcon('green')}).addTo(map);
                    document.getElementById('start_latlng_hidden').value = coordStr;
                    document.getElementById('start_address').value = coordStr;
                    
                    // Reset UI
                    document.getElementById('result_box').style.display = 'none';
                    document.getElementById('submit_btn').disabled = true;

                } else {
                    endMarker = L.marker(latlng, {icon: getIcon('red')}).addTo(map);
                    document.getElementById('end_latlng_hidden').value = coordStr;
                    document.getElementById('end_address').value = coordStr;

                    drawShapeAndCalculate();
                }
            }

            function drawShapeAndCalculate() {
                if (!startMarker || !endMarker) return;

                let p1 = startMarker.getLatLng();
                let p2 = endMarker.getLatLng();
                let missionType = typeSelect.value;

                // Curățăm desenul vechi
                if(shapeLayer) map.removeLayer(shapeLayer);

                if (missionType === 'livrare') {
                    shapeLayer = L.polyline([p1, p2], {color: 'blue', weight: 4}).addTo(map);
                    map.fitBounds(shapeLayer.getBounds(), {padding: [50,50]});
                    calculateDelivery(p1, p2);
                } else {
                    // Dreptunghi pentru Survey
                    let bounds = [[p1.lat, p1.lng], [p2.lat, p2.lng]];
                    shapeLayer = L.rectangle(bounds, {color: 'orange', weight: 2}).addTo(map);
                    map.fitBounds(bounds, {padding: [50,50]});
                    calculateSurvey(p1, p2, missionType);
                }
            }

            function calculateDelivery(p1, p2) {
                let distKm = p1.distanceTo(p2) / 1000;
                let weight = parseFloat(weightInput.value) || 0;
                
                // 40km/h viteza
                let duration = (distKm / 40) * 60 + 10; 
                
                // Filtrare: Tip='transport' și Capacitate >= Greutate
                let candidates = availableDrones.filter(d => d.Type === 'transport' && parseFloat(d.PayloadCapacity) >= weight);
                // Sortare: cea mai mică capacitate care face față
                candidates.sort((a,b) => parseFloat(a.PayloadCapacity) - parseFloat(b.PayloadCapacity));

                let cost = 20 + (distKm * 2) + (weight * 5);

                displayResult(candidates[0], cost, duration, 'Distanță', distKm.toFixed(2) + ' km');
            }

            function calculateSurvey(p1, p2, type) {
                // Calcul Arie (Simplificat)
                let latDist = Math.abs(p1.lat - p2.lat) * 111;
                let lngDist = Math.abs(p1.lng - p2.lng) * 111 * Math.cos(p1.lat * Math.PI / 180);
                let areaSqKm = latDist * lngDist;

                // Viteză de scanare
                let speedFactor = (type === 'inspectie') ? 0.05 : 0.1; // km2 per min
                let duration = (areaSqKm / speedFactor) + 15;

                // Filtrare: Tip='survey', sortat după autonomie
                let candidates = availableDrones.filter(d => d.Type === 'survey' && parseFloat(d.AutonomyMin) >= duration);
                candidates.sort((a,b) => parseFloat(b.AutonomyMin) - parseFloat(a.AutonomyMin));

                let cost = 100 + (areaSqKm * 500);

                displayResult(candidates[0], cost, duration, 'Arie Scanată', areaSqKm.toFixed(3) + ' km²');
            }

            function displayResult(drone, cost, duration, labelInfo, valueInfo) {
                let resBox = document.getElementById('result_box');
                let errBox = document.getElementById('error_box');
                let btn = document.getElementById('submit_btn');

                if (drone) {
                    document.getElementById('drone_name_display').innerText = drone.Model;
                    document.getElementById('cost_display').innerText = cost.toFixed(2) + " RON";
                    document.getElementById('dur_display').innerText = Math.round(duration) + " min";
                    document.getElementById('info_label').innerText = labelInfo + ":";
                    document.getElementById('info_value').innerText = valueInfo;

                    document.getElementById('drone_id_hidden').value = drone.DroneID;
                    document.getElementById('duration_input').value = Math.round(duration);

                    resBox.style.display = 'block';
                    errBox.style.display = 'none';
                    btn.disabled = false;
                } else {
                    resBox.style.display = 'none';
                    errBox.style.display = 'block';
                    errBox.innerText = "Nicio dronă disponibilă! (Durata depășește autonomia sau greutatea e prea mare).";
                    btn.disabled = true;
                }
            }

            function getIcon(c) {
                return new L.Icon({
                    iconUrl: `https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-${c}.png`,
                    shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
                    iconSize: [25, 41], iconAnchor: [12, 41], popupAnchor: [1, -34], shadowSize: [41, 41]
                });
            }
        });
    </script>
</body>
</html>