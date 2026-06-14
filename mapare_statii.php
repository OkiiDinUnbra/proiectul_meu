<?php
session_start();
require_once 'db_connect.php';

// Securitate: Doar adminul ar trebui să aibă acces la mapare
if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'admin') {
    die("Acces interzis. Trebuie să fii admin.");
}

// ==========================================
// AJAX: Salvarea coordonatelor în baza de date
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['actiune']) && $_POST['actiune'] == 'salveaza_gps') {
    $id = intval($_POST['statie_id']);
    $lat = floatval($_POST['lat']);
    $lng = floatval($_POST['lng']);

    $stmt = $conn->prepare("UPDATE transport_statii SET lat = ?, lng = ? WHERE id = ?");
    $stmt->bind_param("ddi", $lat, $lng, $id);
    
    if ($stmt->execute()) {
        echo "OK";
    } else {
        echo "EROARE";
    }
    $stmt->close();
    exit();
}

// Preluăm stațiile care NU au coordonate
$statii_lipsa = [];
$res = $conn->query("SELECT id, nume_statie FROM transport_statii WHERE lat IS NULL OR lng IS NULL ORDER BY nume_statie ASC");
while($row = $res->fetch_assoc()) {
    $statii_lipsa[] = $row;
}
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mapare GPS Stații | Descoperă Brăila</title>
    
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <style>
        body { margin: 0; padding: 0; display: flex; height: 100vh; font-family: 'Segoe UI', sans-serif; background: #0A192F; color: #fff; }
        
        .sidebar { width: 350px; background: rgba(10, 25, 47, 0.95); border-right: 1px solid rgba(255,255,255,0.1); display: flex; flex-direction: column; }
        .sidebar-header { padding: 20px; background: #112240; border-bottom: 2px solid #38bdf8; }
        .sidebar-header h2 { margin: 0; font-size: 20px; color: #38bdf8; }
        .sidebar-header p { margin: 5px 0 0; font-size: 13px; color: #8892b0; }
        
        .station-list { flex: 1; overflow-y: auto; padding: 10px; }
        .station-item { 
            padding: 12px 15px; background: rgba(255,255,255,0.05); margin-bottom: 8px; border-radius: 8px; 
            cursor: pointer; border: 1px solid rgba(255,255,255,0.1); transition: 0.2s;
        }
        .station-item:hover { background: rgba(56, 189, 248, 0.2); border-color: #38bdf8; }
        .station-item.active { background: #38bdf8; color: #0f172a; font-weight: bold; }
        .station-item.done { display: none; /* Ascundem stația după ce o mapăm */ }

        #map { flex: 1; height: 100vh; z-index: 1; }

        .btn-inapoi { display: block; text-align: center; padding: 15px; background: #dc3545; color: white; text-decoration: none; font-weight: bold; }
        .btn-inapoi:hover { background: #c82333; }
        
        .instructiuni {
            position: absolute; top: 20px; left: 380px; z-index: 999; background: #10b981; color: white;
            padding: 15px 25px; border-radius: 8px; font-weight: bold; box-shadow: 0 4px 15px rgba(0,0,0,0.3);
            pointer-events: none;
        }
    </style>
</head>
<body>

    <div class="sidebar">
        <div class="sidebar-header">
            <h2>📍 Mapare GPS Stații</h2>
            <p>Au mai rămas <b id="counter"><?= count($statii_lipsa) ?></b> stații de setat.</p>
        </div>
        
        <div class="station-list" id="stationList">
            <?php if(empty($statii_lipsa)): ?>
                <div style="padding: 20px; text-align: center; color: #10b981; font-weight: bold;">
                    🎉 Toate stațiile au fost mapate!
                </div>
            <?php else: ?>
                <?php foreach($statii_lipsa as $statie): ?>
                    <div class="station-item" data-id="<?= $statie['id'] ?>" onclick="selectStation(this)">
                        <?= htmlspecialchars($statie['nume_statie']) ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <a href="admin.php" class="btn-inapoi">⬅️ Înapoi la Admin</a>
    </div>

    <div id="map"></div>
    
    <div class="instructiuni" id="msgBox">
        1. Selectează o stație din stânga!<br>
        2. Dă click pe hartă unde se află.
    </div>

    <script>
        // Inițializăm Harta (Centrată pe orașul Brăila)
        var map = L.map('map').setView([45.2692, 27.9575], 14);

        // Adăugăm layer-ul grafic (străzile de la OpenStreetMap)
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors',
            maxZoom: 19
        }).addTo(map);

        var currentStationId = null;
        var currentStationEl = null;
        var marker = null;
        var counter = <?= count($statii_lipsa) ?>;

        // Când dăm click pe un nume de stație în stânga
        function selectStation(element) {
            // Curățăm selecția veche
            document.querySelectorAll('.station-item').forEach(el => el.classList.remove('active'));
            
            // Setăm noua selecție
            element.classList.add('active');
            currentStationId = element.getAttribute('data-id');
            currentStationEl = element;

            document.getElementById('msgBox').innerHTML = "📍 Ai selectat <b>" + element.innerText + "</b>.<br>Acum dă click pe hartă!";
            document.getElementById('msgBox').style.background = "#38bdf8";
        }

        // Când dăm click pe Hartă
        map.on('click', function(e) {
            if(!currentStationId) {
                alert("Te rog să selectezi o stație din stânga mai întâi!");
                return;
            }

            var lat = e.latlng.lat;
            var lng = e.latlng.lng;

            // Punem un pin vizual pe hartă
            if(marker) map.removeLayer(marker);
            marker = L.marker([lat, lng]).addTo(map);

            // Salvăm în baza de date prin AJAX
            document.getElementById('msgBox').innerHTML = "⏳ Se salvează...";
            
            var formData = new FormData();
            formData.append('actiune', 'salveaza_gps');
            formData.append('statie_id', currentStationId);
            formData.append('lat', lat);
            formData.append('lng', lng);

            fetch('mapare_statii.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(data => {
                if(data.trim() === 'OK') {
                    document.getElementById('msgBox').innerHTML = "✅ Salvat cu succes!";
                    document.getElementById('msgBox').style.background = "#10b981";
                    
                    // Ascundem stația mapată din listă
                    currentStationEl.classList.add('done');
                    currentStationId = null;
                    
                    // Actualizăm contorul
                    counter--;
                    document.getElementById('counter').innerText = counter;

                    if(counter === 0) {
                        document.getElementById('msgBox').innerHTML = "🎉 Ai terminat de mapat toate stațiile!";
                    }
                } else {
                    alert("A apărut o eroare la salvare!");
                }
            });
        });
    </script>
</body>
</html>