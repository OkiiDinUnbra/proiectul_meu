<?php
session_start();
require_once 'db_connect.php';

// Securitate: Doar adminul are acces
if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'admin') {
    die("Acces interzis. Trebuie să fii admin.");
}

// ==========================================
// AJAX: Salvare, Adăugare, Redenumire și ȘTERGERE
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['actiune'])) {
    
    // 1. UPDATE GPS
    if ($_POST['actiune'] == 'salveaza_gps') {
        $id = intval($_POST['statie_id']);
        $lat = floatval($_POST['lat']);
        $lng = floatval($_POST['lng']);

        $stmt = $conn->prepare("UPDATE transport_statii SET lat = ?, lng = ? WHERE id = ?");
        $stmt->bind_param("ddi", $lat, $lng, $id);
        if ($stmt->execute()) echo "OK"; else echo "EROARE";
        $stmt->close();
        exit();
    }
    
    // 2. ADĂUGARE (și de pe hartă, și manual prin coordonate)
    if ($_POST['actiune'] == 'adauga_statie') {
        $nume = trim($_POST['nume']);
        $lat = floatval($_POST['lat']);
        $lng = floatval($_POST['lng']);

        $check = $conn->query("SELECT id FROM transport_statii WHERE nume_statie = '$nume'");
        if($check->num_rows > 0) { echo "EROARE_DUPLICAT"; exit(); }

        $stmt = $conn->prepare("INSERT INTO transport_statii (nume_statie, lat, lng) VALUES (?, ?, ?)");
        $stmt->bind_param("sdd", $nume, $lat, $lng);
        if ($stmt->execute()) echo "OK|" . $stmt->insert_id; else echo "EROARE";
        $stmt->close();
        exit();
    }

    // 3. REDENUMIRE
    if ($_POST['actiune'] == 'redenumeste_statie') {
        $id = intval($_POST['statie_id']);
        $nume = trim($_POST['nume']);

        $check = $conn->query("SELECT id FROM transport_statii WHERE nume_statie = '$nume' AND id != $id");
        if($check->num_rows > 0) { echo "EROARE_DUPLICAT"; exit(); }

        $stmt = $conn->prepare("UPDATE transport_statii SET nume_statie = ? WHERE id = ?");
        $stmt->bind_param("si", $nume, $id);
        if ($stmt->execute()) echo "OK"; else echo "EROARE";
        $stmt->close();
        exit();
    }

    // 4. ȘTERGERE STAȚIE
    if ($_POST['actiune'] == 'sterge_statie') {
        $id = intval($_POST['statie_id']);
        $stmt = $conn->prepare("DELETE FROM transport_statii WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) echo "OK"; else echo "EROARE";
        $stmt->close();
        exit();
    }
}

$toate_statiile = [];
$res = $conn->query("SELECT id, nume_statie, lat, lng FROM transport_statii ORDER BY nume_statie ASC");
while($row = $res->fetch_assoc()) {
    $toate_statiile[] = $row;
}
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Map Editor | Descoperă Brăila</title>
    
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <style>
        body { margin: 0; padding: 0; display: flex; height: 100vh; font-family: 'Segoe UI', sans-serif; background: #0A192F; color: #fff; }
        
        .sidebar { width: 400px; background: rgba(10, 25, 47, 0.95); border-right: 1px solid rgba(255,255,255,0.1); display: flex; flex-direction: column; z-index: 1000;}
        .sidebar-header { padding: 20px; background: #112240; border-bottom: 2px solid #38bdf8; }
        .sidebar-header h2 { margin: 0; font-size: 22px; color: #38bdf8; display: flex; justify-content: space-between; align-items: center;}
        
        .btn-add-new { background: #10b981; color: white; border: none; padding: 10px 15px; border-radius: 8px; font-weight: bold; cursor: pointer; width: 100%; font-size: 14px; transition: 0.2s;}
        .btn-add-new:hover { filter: brightness(0.9); }
        .btn-add-new.active { background: #f59e0b; animation: pulse 1.5s infinite; }

        @keyframes pulse { 0% { transform: scale(1); } 50% { transform: scale(1.02); } 100% { transform: scale(1); } }

        .search-container { padding: 15px 20px 0 20px; }
        .search-input { width: 100%; box-sizing: border-box; padding: 12px 15px; border-radius: 8px; border: 1px solid rgba(255,255,255,0.2); background: rgba(0,0,0,0.3); color: white; outline: none; font-size: 15px; }
        .search-input:focus { border-color: #38bdf8; }

        .station-list { flex: 1; overflow-y: auto; padding: 10px 20px; }
        .station-item { padding: 12px 15px; background: rgba(255,255,255,0.05); margin-bottom: 8px; border-radius: 8px; cursor: pointer; border: 1px solid rgba(255,255,255,0.1); transition: 0.2s; display: flex; justify-content: space-between; align-items: center;}
        .station-item:hover { background: rgba(56, 189, 248, 0.2); border-color: #38bdf8; }
        .station-item.active { background: #38bdf8; color: #0f172a; font-weight: bold; }
        .station-item.mapped { border-left: 4px solid #10b981; }
        .station-item.unmapped { border-left: 4px solid #dc3545; }
        
        .station-name { flex: 1; margin-right: 10px; font-size: 14px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;}
        .station-actions { display: flex; gap: 8px; align-items: center; }
        
        .action-btn { background: none; border: none; cursor: pointer; padding: 0; font-size: 15px; opacity: 0.7; transition: 0.2s; }
        .action-btn:hover { opacity: 1; transform: scale(1.2); }

        #map { flex: 1; height: 100vh; z-index: 1; }

        .btn-inapoi { display: block; text-align: center; padding: 15px; background: #dc3545; color: white; text-decoration: none; font-weight: bold; }
        .instructiuni { position: absolute; top: 20px; left: 420px; z-index: 999; background: #112240; color: white; padding: 15px 25px; border-radius: 8px; font-weight: bold; box-shadow: 0 4px 15px rgba(0,0,0,0.5); border: 1px solid #38bdf8; pointer-events: none;}
        
        /* Box pentru Adăugare Manuală Coordonate */
        .manual-add-box { display: none; background: rgba(0, 0, 0, 0.3); padding: 15px; border-radius: 8px; margin-top: 15px; border: 1px dashed #0ea5e9; }
        .manual-add-box input { margin-bottom: 10px; font-size: 13px; padding: 10px;}
    </style>
</head>
<body>

    <div class="sidebar">
        <div class="sidebar-header">
            <h2>📍 Map Editor</h2>
            <p style="margin: 5px 0 10px 0; font-size: 12px; color: #8892b0;">Adaugă o stație nouă prin:</p>
            
            <div style="display: flex; gap: 10px;">
                <button class="btn-add-new" id="btnAddMode" onclick="toggleAddMode()">🗺️ Pe Hartă</button>
                <button class="btn-add-new" style="background: #0ea5e9;" onclick="toggleManualAdd()">⌨️ Coordonate</button>
            </div>

            <div class="manual-add-box" id="manualAddBox">
                <input type="text" id="manNume" class="search-input" placeholder="Numele noiei stații...">
                <div style="display: flex; gap: 10px;">
                    <input type="text" id="manLat" class="search-input" placeholder="Latitudine (ex: 45.26)">
                    <input type="text" id="manLng" class="search-input" placeholder="Longitudine (ex: 27.95)">
                </div>
                <button class="btn-add-new" style="background: #38bdf8; color: #111; margin-top: 5px;" onclick="salveazaManual()">✅ Salvează direct</button>
            </div>
        </div>
        
        <div class="search-container">
            <input type="text" id="searchInput" class="search-input" placeholder="🔍 Caută o stație..." onkeyup="filtreazaStatii()">
        </div>
        
        <div class="station-list" id="stationList">
            <?php foreach($toate_statiile as $statie): ?>
                <?php $e_mapata = ($statie['lat'] !== null && $statie['lng'] !== null); ?>
                <div class="station-item <?= $e_mapata ? 'mapped' : 'unmapped' ?>" 
                     data-id="<?= $statie['id'] ?>" 
                     data-nume="<?= htmlspecialchars($statie['nume_statie']) ?>"
                     onclick="selectStation(this)">
                    
                    <span class="station-name"><?= htmlspecialchars($statie['nume_statie']) ?></span>
                    
                    <div class="station-actions">
                        <button class="action-btn" onclick="redenumesteStatie(event, <?= $statie['id'] ?>, this.closest('.station-item'))" title="Schimbă numele">✏️</button>
                        <button class="action-btn" onclick="stergeStatie(event, <?= $statie['id'] ?>)" title="Șterge stația">🗑️</button>
                        <span class="status-icon" style="margin-left: 5px;"><?= $e_mapata ? '✅' : '❌' ?></span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <a href="admin.php" class="btn-inapoi">⬅️ Înapoi la Admin</a>
    </div>

    <div id="map"></div>
    
    <div class="instructiuni" id="msgBox">
        Alege o acțiune din stânga!
    </div>

    <script>
        var toateStatiile = <?= json_encode($toate_statiile) ?>;

        var map = L.map('map').setView([45.2692, 27.9575], 14);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19 }).addTo(map);

        var markersLayer = L.layerGroup().addTo(map);
        
        function adaugaMarkerPeHarta(id, nume, lat, lng) {
            var popupContinut = "<div style='text-align:center;'><b>" + nume + "</b><br>" + 
                                "<button onclick='stergeStatie(event, " + id + ")' style='background:#dc3545; color:white; border:none; padding:6px 12px; border-radius:6px; margin-top:10px; cursor:pointer; font-weight:bold; width:100%;'>🗑️ Șterge Stația</button></div>";
            L.marker([lat, lng]).bindPopup(popupContinut).addTo(markersLayer);
        }

        toateStatiile.forEach(function(s) {
            if(s.lat && s.lng) {
                adaugaMarkerPeHarta(s.id, s.nume_statie, s.lat, s.lng);
            }
        });

        var appMode = 'IDLE'; 
        var currentStationId = null;
        var currentStationEl = null;

        function filtreazaStatii() {
            var input = document.getElementById("searchInput").value.toLowerCase();
            var statii = document.getElementsByClassName("station-item");
            for (var i = 0; i < statii.length; i++) {
                var numeStatie = statii[i].getAttribute('data-nume').toLowerCase();
                statii[i].style.display = numeStatie.includes(input) ? "flex" : "none";
            }
        }

        // --- FUNCȚIILE NOI PENTRU ADĂUGAREA MANUALĂ (COORDONATE) ---
        function toggleManualAdd() {
            var box = document.getElementById('manualAddBox');
            if(box.style.display === 'none' || box.style.display === '') {
                box.style.display = 'block';
                // Oprim adăugarea pe hartă dacă era activă
                if(appMode === 'ADD') toggleAddMode();
            } else {
                box.style.display = 'none';
            }
        }

        function salveazaManual() {
            var nume = document.getElementById('manNume').value.trim();
            var latVal = document.getElementById('manLat').value.trim().replace(',', '.'); // Permitem și virgulă
            var lngVal = document.getElementById('manLng').value.trim().replace(',', '.');

            var lat = parseFloat(latVal);
            var lng = parseFloat(lngVal);

            if (!nume) { alert("Te rog să introduci un nume pentru stație!"); return; }
            if (isNaN(lat) || isNaN(lng)) { alert("Coordonatele trebuie să fie numere valide (ex: 45.26)!"); return; }

            // Folosim aceeași funcție de salvare în baza de date
            salveazaStatieNoua(nume, lat, lng);
        }
        // -------------------------------------------------------------

        function toggleAddMode() {
            var btn = document.getElementById('btnAddMode');
            document.querySelectorAll('.station-item').forEach(el => el.classList.remove('active'));
            currentStationId = null;
            currentStationEl = null;
            document.getElementById('manualAddBox').style.display = 'none'; // Ascundem form-ul manual

            if (appMode !== 'ADD') {
                appMode = 'ADD';
                btn.classList.add('active');
                btn.innerText = "🛑 Anulează";
                document.getElementById('msgBox').innerHTML = "🟢 Ai intrat în modul creare!<br><b>Dă click pe hartă unde vrei să adaugi stația.</b>";
                document.getElementById('msgBox').style.borderColor = "#10b981";
            } else {
                appMode = 'IDLE';
                btn.classList.remove('active');
                btn.innerText = "🗺️ Pe Hartă";
                document.getElementById('msgBox').innerHTML = "Alege o acțiune din stânga!";
                document.getElementById('msgBox').style.borderColor = "#38bdf8";
            }
        }

        function selectStation(element) {
            if(appMode === 'ADD') toggleAddMode();
            document.getElementById('manualAddBox').style.display = 'none';

            appMode = 'UPDATE';
            document.querySelectorAll('.station-item').forEach(el => el.classList.remove('active'));
            element.classList.add('active');
            
            currentStationId = element.getAttribute('data-id');
            currentStationEl = element;

            document.getElementById('msgBox').innerHTML = "📍 Mutare stație: <b>" + element.getAttribute('data-nume') + "</b><br>Dă click pe hartă pentru a-i salva noua locație!";
            document.getElementById('msgBox').style.borderColor = "#f59e0b";
        }

        function redenumesteStatie(event, id, elementLista) {
            event.stopPropagation();
            var numeVechi = elementLista.getAttribute('data-nume');
            var numeNou = prompt("Introdu noul nume pentru această stație:", numeVechi);

            if (numeNou && numeNou.trim() !== "" && numeNou !== numeVechi) {
                var formData = new FormData();
                formData.append('actiune', 'redenumeste_statie');
                formData.append('statie_id', id);
                formData.append('nume', numeNou.trim());

                fetch('mapare_statii.php', { method: 'POST', body: formData })
                .then(res => res.text())
                .then(data => {
                    if(data.trim() === 'EROARE_DUPLICAT') {
                        alert("Există deja o altă stație cu acest nume în baza de date!");
                    } else if(data.trim() === 'OK') {
                        elementLista.setAttribute('data-nume', numeNou.trim());
                        elementLista.querySelector('.station-name').innerText = numeNou.trim();
                    } else {
                        alert("A apărut o eroare la redenumire!");
                    }
                });
            }
        }

        function stergeStatie(event, id) {
            event.stopPropagation();
            if(confirm("⚠️ Ești sigur că vrei să ștergi DEFINITIV această stație din baza de date?\n\n(Atenție: Dacă această stație era folosită în anumite rute, acele bucăți de rute se vor șterge și ele automat!)")) {
                var formData = new FormData();
                formData.append('actiune', 'sterge_statie');
                formData.append('statie_id', id);

                fetch('mapare_statii.php', { method: 'POST', body: formData })
                .then(res => res.text())
                .then(data => {
                    if(data.trim() === 'OK') {
                        window.location.reload(); 
                    } else {
                        alert("A apărut o eroare la ștergere!");
                    }
                });
            }
        }

        map.on('click', function(e) {
            var lat = e.latlng.lat;
            var lng = e.latlng.lng;

            if (appMode === 'IDLE') {
                alert("Te rog să alegi din stânga ce vrei să faci: Editezi o stație sau adaugi una nouă?");
                return;
            }

            if (appMode === 'ADD') {
                var numeNou = prompt("Cum se numește noua stație?");
                if (numeNou && numeNou.trim() !== "") {
                    salveazaStatieNoua(numeNou.trim(), lat, lng);
                }
            }

            if (appMode === 'UPDATE') {
                salveazaUpdate(currentStationId, lat, lng, currentStationEl);
            }
        });

        function salveazaUpdate(id, lat, lng, elementLista) {
            var formData = new FormData();
            formData.append('actiune', 'salveaza_gps');
            formData.append('statie_id', id);
            formData.append('lat', lat);
            formData.append('lng', lng);

            fetch('mapare_statii.php', { method: 'POST', body: formData })
            .then(res => res.text())
            .then(data => {
                if(data.trim() === 'OK') {
                    elementLista.classList.remove('unmapped');
                    elementLista.classList.add('mapped');
                    elementLista.querySelector('.status-icon').innerText = '✅';
                    
                    adaugaMarkerPeHarta(id, elementLista.getAttribute('data-nume'), lat, lng);
                    document.getElementById('msgBox').innerHTML = "✅ Locație salvată cu succes!";
                } else {
                    alert("Eroare la salvare!");
                }
            });
        }

        function salveazaStatieNoua(nume, lat, lng) {
            var formData = new FormData();
            formData.append('actiune', 'adauga_statie');
            formData.append('nume', nume);
            formData.append('lat', lat);
            formData.append('lng', lng);

            fetch('mapare_statii.php', { method: 'POST', body: formData })
            .then(res => res.text())
            .then(data => {
                if(data.trim() === 'EROARE_DUPLICAT') {
                    alert("Există deja o stație cu acest nume în baza de date!");
                } else if(data.startsWith('OK|')) {
                    alert("Stația " + nume + " a fost salvată cu succes și marcată pe hartă!");
                    window.location.reload();
                } else {
                    alert("Eroare la adăugarea stației!");
                }
            });
        }
    </script>
</body>
</html>