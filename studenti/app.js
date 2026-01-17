document.addEventListener('DOMContentLoaded', () => {
    const deliveryForm = document.getElementById('delivery-form');
    const rezultatSimulare = document.getElementById('rezultat-simulare');

    // Harta de simulare a distanțelor (în km) între orașe majore din România
    // Cheia este "OrasA-OrasB" (ordinea alfabetică a orașelor pentru a evita dublurile)
    const distanteSimulate = {
        "Bucuresti-Cluj-Napoca": 380,
        "Bucuresti-Constanta": 220,
        "Bucuresti-Iasi": 330,
        "Bucuresti-Timisoara": 440,
        "Cluj-Napoca-Constanta": 580,
        "Cluj-Napoca-Iasi": 300,
        "Cluj-Napoca-Timisoara": 270,
        "Constanta-Iasi": 420,
        "Constanta-Timisoara": 680,
        "Iasi-Timisoara": 560
    };

    /**
     * Simulează o cerere API pentru a obține distanța între două orașe.
     * @param {string} orasPlecare 
     * @param {string} orasDestinatie 
     * @returns {number|null} Distanța în km sau null dacă orașele sunt identice.
     */
    function getDistantaSimulata(orasPlecare, orasDestinatie) {
        if (orasPlecare === orasDestinatie) {
            return 0; // Livrare locală
        }

        // Crează o cheie standardizată (ordinea alfabetică)
        const key = [orasPlecare, orasDestinatie].sort().join('-');
        
        // Returnează distanța, dacă există, altfel returnează o valoare implicită mare
        return distanteSimulate[key] || 999; 
    }

    /**
     * Calculează costul estimat al livrării pe baza distanței, greutății și a factorilor de bază.
     * @param {number} distantaKm 
     * @param {number} greutateKg 
     * @returns {number} Costul total estimat în RON.
     */
    function calculeazaCost(distantaKm, greutateKg) {
        const costBaza = 30; // Cost fix de pornire (administrare, manipulare)
        const costPerKm = 0.5; // Cost per kilometru (combustibil/energie)
        const costPerKg = 1.5; // Cost per kilogram (uzura dronei, risc)
        
        let costTotal = costBaza;
        
        // Adaugă costul pe distanță
        costTotal += distantaKm * costPerKm;
        
        // Adaugă costul pe greutate
        costTotal += greutateKg * costPerKg;
        
        // Bonus pentru livrare locală (o mică reducere)
        if (distantaKm === 0) {
            costTotal *= 0.8; // 20% reducere pentru local
        } else if (distantaKm > 500) {
             costTotal *= 1.2; // 20% majorare pentru distanțe mari
        }

        // Rotunjeste la două zecimale
        return Math.round(costTotal * 100) / 100;
    }

    // Adaugă ascultătorul de evenimente pentru formular
    deliveryForm.addEventListener('submit', (e) => {
        e.preventDefault(); // Oprește reîncărcarea paginii

        const orasPlecare = document.getElementById('oras-plecare').value;
        const orasDestinatie = document.getElementById('oras-destinatie').value;
        const greutateColet = parseFloat(document.getElementById('greutate-colet').value);
        
        // Validare de bază (greutatea maximă de 20kg este importantă)
        if (greutateColet > 20) {
            rezultatSimulare.innerHTML = `<i class="fas fa-exclamation-triangle" style="color: red;"></i> <strong>Eroare:</strong> Greutatea maximă acceptată este de 20 kg.`;
            return;
        }

        if (orasPlecare === '' || orasDestinatie === '') {
             rezultatSimulare.innerHTML = `<i class="fas fa-exclamation-triangle" style="color: red;"></i> <strong>Eroare:</strong> Vă rugăm selectați ambele orașe.`;
             return;
        }


        // 1. Obține Distanța (Simulare API)
        const distantaKm = getDistantaSimulata(orasPlecare, orasDestinatie);

        // 2. Calculează Costul
        const costEstimat = calculeazaCost(distantaKm, greutateColet);

        // 3. Afișează Rezultatul
        let mesajHTML = '';

        if (distantaKm === 0) {
            mesajHTML = `
                <i class="fas fa-shipping-fast" style="color: #4CAF50;"></i> <strong>Livrare Locală!</strong>
                <br>Distanță estimată: <strong>0 km</strong> (Local)
                <br>Greutate colet: <strong>${greutateColet.toFixed(1)} kg</strong>
                <br>Cost total estimat: <strong style="color: #2c3e50;">${costEstimat.toFixed(2)} RON</strong>
            `;
        } else if (distantaKm === 999) {
             mesajHTML = `<i class="fas fa-exclamation-triangle" style="color: orange;"></i> <strong>Avertisment:</strong> Rută nevalidă sau nedefinită. Vă rugăm contactați-ne pentru o ofertă personalizată.`;
        } else {
             mesajHTML = `
                <i class="fas fa-route" style="color: #2196F3;"></i> <strong>Detalii Estimare:</strong>
                <br>Ruta: <strong>${orasPlecare}</strong> la <strong>${orasDestinatie}</strong>
                <br>Distanță estimată: <strong>${distantaKm} km</strong>
                <br>Greutate colet: <strong>${greutateColet.toFixed(1)} kg</strong>
                <br>Cost total estimat: <strong style="font-size: 1.3em; color: #e74c3c;">${costEstimat.toFixed(2)} RON</strong>
            `;
        }


        rezultatSimulare.innerHTML = mesajHTML;
    });

    // Incarca o descriere initiala
    rezultatSimulare.innerHTML = `<i class="fas fa-info-circle"></i> Vă rugăm introduceți datele de livrare pentru a obține o estimare de cost.`;
});