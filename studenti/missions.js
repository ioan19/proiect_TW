document.addEventListener('DOMContentLoaded', function() {
    
    // Configurare Harta
    var locationIqKey = 'pk.0b94b0593741512f45037c4493333333'; // Folosește token-ul tău dacă ai unul
    var map = L.map('map').setView([44.4268, 26.1025], 13);
    L.tileLayer('https://{s}-tiles.locationiq.com/v3/streets/r/{z}/{x}/{y}.png?key=' + locationIqKey, {
        maxZoom: 18, attribution: '© LocationIQ'
    }).addTo(map);

    var startMarker = null, endMarker = null, shapeLayer = null;

    // Elemente DOM
    const typeSelect = document.querySelector('select[name="type"]');
    const weightInput = document.getElementById('payload_weight');
    const startInput = document.getElementById('start_latlng_hidden');
    const endInput = document.getElementById('end_latlng_hidden');
    const weightGroup = document.getElementById('weightGroup');

    // Ascultători evenimente
    map.on('click', handleMapClick);
    typeSelect.addEventListener('change', function() {
        // Ascunde câmpul de greutate dacă nu e livrare
        if (this.value === 'livrare') {
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
            // Reset complet
            if(startMarker) map.removeLayer(startMarker);
            if(endMarker) map.removeLayer(endMarker);
            if(shapeLayer) map.removeLayer(shapeLayer);
            endMarker = null; shapeLayer = null;

            startMarker = L.marker(latlng, {icon: getIcon('green')}).addTo(map);
            startInput.value = coordStr;
            document.getElementById('start_address').value = coordStr; // Simplificat, fără reverse geo pt viteză
        } else {
            endMarker = L.marker(latlng, {icon: getIcon('red')}).addTo(map);
            endInput.value = coordStr;
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
            // Linie simplă
            shapeLayer = L.polyline([p1, p2], {color: 'blue', weight: 4}).addTo(map);
            map.fitBounds(shapeLayer.getBounds());
            calculateDelivery(p1, p2);
        } else {
            // Dreptunghi (Bounding Box) pentru Survey
            let bounds = [[p1.lat, p1.lng], [p2.lat, p2.lng]];
            shapeLayer = L.rectangle(bounds, {color: 'orange', weight: 1}).addTo(map);
            map.fitBounds(bounds);
            calculateSurvey(p1, p2, missionType);
        }
    }

    function calculateDelivery(p1, p2) {
        let distKm = p1.distanceTo(p2) / 1000;
        let weight = parseFloat(weightInput.value) || 0;
        
        // 40km/h viteza medie
        let duration = (distKm / 40) * 60 + 10; 
        
        // Filtrare drone: Tip=transport și Capacitate >= Greutate
        let candidates = window.availableDrones.filter(d => d.Type === 'transport' && parseFloat(d.PayloadCapacity) >= weight);
        candidates.sort((a,b) => parseFloat(a.PayloadCapacity) - parseFloat(b.PayloadCapacity)); // Cea mai mică eficientă

        // Cost: Bază 20 + 2/km + 5/kg
        let cost = 20 + (distKm * 2) + (weight * 5);

        displayResult(candidates[0], cost, duration, 'Distanță', distKm.toFixed(2) + ' km');
    }

    function calculateSurvey(p1, p2, type) {
        // Calcul simplificat Arie în Km2
        // Distanța latitudinală (~111km per grad) * Distanța longitudinală
        let latDist = Math.abs(p1.lat - p2.lat) * 111;
        let lngDist = Math.abs(p1.lng - p2.lng) * 111 * Math.cos(p1.lat * Math.PI / 180);
        let areaSqKm = latDist * lngDist;

        // Estimare timp: 0.05 km2 pe minut pt inspecție detaliată, 0.1 pt mapare
        let speedFactor = (type === 'inspectie') ? 0.05 : 0.1;
        let duration = (areaSqKm / speedFactor) + 15; // +15 min setup

        // Filtrare drone: Tip=survey, sortate după autonomie descrescătoare
        let candidates = window.availableDrones.filter(d => d.Type === 'survey' && parseFloat(d.AutonomyMin) >= duration);
        candidates.sort((a,b) => parseFloat(b.AutonomyMin) - parseFloat(a.AutonomyMin));

        // Cost: Bază 100 + 500/km2
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
            errBox.innerText = "Nicio dronă disponibilă pentru acești parametri (Durata depășește autonomia sau greutatea e prea mare).";
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