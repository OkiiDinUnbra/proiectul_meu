<?php
session_start();
require_once 'db_connect.php';

// Securitate: Doar adminul are acces
if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'admin') {
    die("Acces interzis.");
}

// ==========================================
// AJAX PENTRU SALVARE / ȘTERGERE / REORDONARE
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['actiune'])) {
    
    // 1. ADĂUGARE STAȚIE LA RUTĂ (la final)
    if ($_POST['actiune'] == 'adauga_la_ruta') {
        $dir_id = intval($_POST['directie_id']);
        $statie_id = intval($_POST['statie_id']);
        
        $res = $conn->query("SELECT MAX(ordine) as max_ord FROM transport_rute WHERE directie_id = $dir_id");
        $row = $res->fetch_assoc();
        $noua_ordine = ($row['max_ord'] !== null) ? $row['max_ord'] + 1 : 1;
        
        $stmt = $conn->prepare("INSERT INTO transport_rute (directie_id, statie_id, ordine) VALUES (?, ?, ?)");
        $stmt->bind_param("iii", $dir_id, $statie_id, $noua_ordine);
        if ($stmt->execute()) echo "OK"; else echo "EROARE";
        $stmt->close();
        exit();
    }
    
    // 2. ȘTERGERE RUTĂ COMPLETĂ (Resetare)
    if ($_POST['actiune'] == 'reseteaza_ruta') {
        $dir_id = intval($_POST['directie_id']);
        $conn->query("DELETE FROM transport_rute WHERE directie_id = $dir_id");
        echo "OK";
        exit();
    }

    // 3. ȘTERGE O SINGURĂ STAȚIE DIN RUTĂ
    if ($_POST['actiune'] == 'sterge_din_ruta') {
        $dir_id = intval($_POST['directie_id']);
        $statie_id = intval($_POST['statie_id']);
        $ordine = intval($_POST['ordine']);
        
        $conn->query("DELETE FROM transport_rute WHERE directie_id = $dir_id AND statie_id = $statie_id AND ordine = $ordine LIMIT 1");
        $conn->query("UPDATE transport_rute SET ordine = ordine - 1 WHERE directie_id = $dir_id AND ordine > $ordine");
        
        echo "OK";
        exit();
    }

    // 4. REORDONARE DRAG & DROP
    if ($_POST['actiune'] == 'reordoneaza_ruta') {
        $dir_id = intval($_POST['directie_id']);
        $statii = json_decode($_POST['statii_array'], true);

        $conn->query("DELETE FROM transport_rute WHERE directie_id = $dir_id");
        
        $stmt = $conn->prepare("INSERT INTO transport_rute (directie_id, statie_id, ordine) VALUES (?, ?, ?)");
        $ordine = 1;
        foreach($statii as $statie_id) {
            $stmt->bind_param("iii", $dir_id, $statie_id, $ordine);
            $stmt->execute();
            $ordine++;
        }
        $stmt->close();
        echo "OK";
        exit();
    }

    // 5. ADĂUGARE LINIE NOUĂ
    if ($_POST['actiune'] == 'adauga_linie') {
        $numar = trim($_POST['numar']);
        $tip = trim($_POST['tip']);
        $stmt = $conn->prepare("INSERT INTO transport_linii (numar_linia, tip_vehicul) VALUES (?, ?)");
        $stmt->bind_param("ss", $numar, $tip);
        if ($stmt->execute()) echo "OK"; else echo "EROARE";
        $stmt->close();
        exit();
    }

    // 6. ADĂUGARE DIRECȚIE NOUĂ
    if ($_POST['actiune'] == 'adauga_directie') {
        $linie_id = intval($_POST['linie_id']);
        $nume = trim($_POST['nume']);
        $sens = trim($_POST['sens']);
        $stmt = $conn->prepare("INSERT INTO transport_directii (linia_id, nume_directie, sens) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $linie_id, $nume, $sens);
        if ($stmt->execute()) echo "OK"; else echo "EROARE";
        $stmt->close();
        exit();
    }
}

// ==========================================
// PRELUARE DATE PENTRU INTERFAȚĂ
// ==========================================
$toate_statiile = [];
$res_st = $conn->query("SELECT id, nume_statie, lat, lng FROM transport_statii WHERE lat IS NOT NULL");
while($r = $res_st->fetch_assoc()) { $toate_statiile[$r['id']] = $r; }

$linii = [];
$res_l = $conn->query("SELECT * FROM transport_linii ORDER BY tip_vehicul, numar_linia");
while($r = $res_l->fetch_assoc()) { $linii[] = $r; }

$directii = [];
$res_d = $conn->query("SELECT * FROM transport_directii");
while($r = $res_d->fetch_assoc()) { $directii[$r['linia_id']][] = $r; }

$rute = [];
$res_r = $conn->query("SELECT * FROM transport_rute ORDER BY directie_id, ordine ASC");
while($r = $res_r->fetch_assoc()) { 
    $rute[$r['directie_id']][] = [
        'statie_id' => $r['statie_id'],
        'ordine' => $r['ordine']
    ]; 
}
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <title>Route Builder | Descoperă Brăila</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>

    <style>
        body { margin: 0; padding: 0; display: flex; height: 100vh; font-family: 'Segoe UI', sans-serif; background: #0A192F; color: #fff; }
        
        .sidebar { width: 420px; background: rgba(10, 25, 47, 0.95); border-right: 1px solid rgba(255,255,255,0.1); display: flex; flex-direction: column; z-index: 1000;}
        .sidebar-header { padding: 20px; background: #112240; border-bottom: 2px solid #38bdf8; }
        .sidebar-header h2 { margin: 0; font-size: 22px; color: #38bdf8; }
        
        .form-group { padding: 15px 20px 0; }
        select, input { width: 100%; padding: 10px; border-radius: 8px; background: rgba(0,0,0,0.3); color: white; border: 1px solid rgba(255,255,255,0.2); font-size: 15px; outline:none; margin-bottom: 10px;}
        select:focus, input:focus { border-color: #38bdf8; }
        select option { background: #112240; }
        
        .btn { background: #38bdf8; color: #111; border: none; padding: 10px; border-radius: 8px; font-weight: bold; cursor: pointer; width: 100%; font-size: 14px; transition: 0.2s; margin-bottom: 5px;}
        .btn:hover { opacity: 0.8; }
        .btn-danger { background: #dc3545; color: white; }

        .route-list { flex: 1; overflow-y: auto; padding: 10px 20px; background: rgba(0,0,0,0.2); margin: 15px; border-radius: 8px; border: 1px solid rgba(255,255,255,0.05);}
        
        .route-item { padding: 10px; background: rgba(255,255,255,0.05); border-left: 4px solid #10b981; margin-bottom: 8px; border-radius: 6px; font-size: 14px; display: flex; align-items: center; justify-content: space-between; cursor: grab; transition: 0.2s;}
        .route-item:active { cursor: grabbing; background: rgba(56, 189, 248, 0.2); border-left-color: #38bdf8;}
        .route-number { background: #10b981; color: #fff; border-radius: 50%; width: 24px; height: 24px; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 12px; flex-shrink: 0;}
        .drag-handle { color: #8892b0; font-size: 18px; margin-right: 10px; opacity: 0.5; }
        .btn-remove { background: none; border: none; color: #dc3545; font-size: 16px; cursor: pointer; opacity: 0.7; transition: 0.2s;}
        .btn-remove:hover { opacity: 1; transform: scale(1.2); }

        #map { flex: 1; height: 100vh; z-index: 1; }
        .btn-inapoi { display: block; text-align: center; padding: 15px; background: #dc3545; color: white; text-decoration: none; font-weight: bold; }
        
        .instructiuni { position: absolute; top: 20px; left: 440px; z-index: 999; background: #112240; color: white; padding: 15px 25px; border-radius: 8px; font-weight: bold; box-shadow: 0 4px 15px rgba(0,0,0,0.5); border: 1px solid #38bdf8;}
    </style>
</head>
<body>

    <div class="sidebar">
        <div class="sidebar-header">
            <h2>🚌 Route Builder Smart</h2>
            <p style="margin: 5px 0 0; font-size: 13px; color: #8892b0;">Traseul urmărește automat străzile!</p>
        </div>
        
        <div class="form-group">
            <label style="font-size: 13px; color: #8892b0; margin-bottom: 5px; display: block;">1. Alege Linia:</label>
            <select id="selectLinie" onchange="schimbaLinia()">
                <option value="">-- Selectează Linia --</option>
                <?php foreach($linii as $l): ?>
                    <option value="<?= $l['id'] ?>"><?= strtoupper($l['tip_vehicul']) ?> <?= htmlspecialchars($l['numar_linia']) ?></option>
                <?php endforeach; ?>
            </select>
            <button class="btn" style="background: transparent; border: 1px dashed #38bdf8; color: #38bdf8;" onclick="adaugaLinie()">+ Creează Linie Nouă</button>
        </div>

        <div class="form-group" id="divDirectie" style="display:none;">
            <label style="font-size: 13px; color: #8892b0; margin-bottom: 5px; display: block;">2. Alege Direcția (Sensul):</label>
            <select id="selectDirectie" onchange="schimbaDirectia()">
                </select>
            <button class="btn" style="background: transparent; border: 1px dashed #10b981; color: #10b981;" onclick="adaugaDirectie()">+ Creează Direcție Nouă</button>
        </div>

        <div class="route-list" id="routeList" style="display:none;">
            <h4 style="margin: 0 0 10px; color: #10b981;">🛤️ Ordine Traseu:</h4>
            <div id="routeItems"></div>
            
            <button class="btn btn-danger" style="margin-top: 25px;" onclick="reseteazaRuta()">🗑️ Șterge TOT Traseul</button>
        </div>

        <a href="admin.php" class="btn-inapoi">⬅️ Înapoi la Admin</a>
    </div>

    <div id="map"></div>
    
    <div class="instructiuni" id="msgBox">
        👈 Alege o Linie și o Direcție din stânga!
    </div>

    <script>
        var map = L.map('map').setView([45.2692, 27.9575], 14);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19 }).addTo(map);

        var toateStatiile = <?= json_encode($toate_statiile) ?>;
        var directii = <?= json_encode($directii) ?>;
        var rute = <?= json_encode($rute) ?>;
        
        var currentDirId = null;
        var markers = {}; 
        var polyline = null; 

        var defaultIcon = L.icon({
            iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-grey.png',
            shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
            iconSize: [25, 41], iconAnchor: [12, 41], popupAnchor: [1, -34], shadowSize: [41, 41]
        });

        var activeIcon = L.icon({
            iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-green.png',
            shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
            iconSize: [25, 41], iconAnchor: [12, 41], popupAnchor: [1, -34], shadowSize: [41, 41]
        });

        Object.values(toateStatiile).forEach(function(s) {
            var marker = L.marker([s.lat, s.lng], {icon: defaultIcon}).addTo(map);
            marker.bindTooltip(s.nume_statie, {permanent: false, direction: 'top'});
            marker.on('click', function() { adaugaStatieLaRuta(s.id, s.nume_statie); });
            markers[s.id] = marker;
        });

        // DRAG & DROP
        var sortableList = document.getElementById('routeItems');
        new Sortable(sortableList, {
            animation: 150,
            onEnd: function (evt) {
                var items = document.querySelectorAll('.route-item');
                var newOrder = [];
                items.forEach(function(item) { newOrder.push(item.getAttribute('data-statie-id')); });

                var fd = new FormData();
                fd.append('actiune', 'reordoneaza_ruta');
                fd.append('directie_id', currentDirId);
                fd.append('statii_array', JSON.stringify(newOrder));

                fetch('mapare_rute.php', { method: 'POST', body: fd }).then(r => r.text()).then(data => {
                    if(data.trim() === 'OK') {
                        rute[currentDirId] = [];
                        newOrder.forEach((sid, idx) => {
                            rute[currentDirId].push({statie_id: parseInt(sid), ordine: idx + 1});
                        });
                        afiseazaRutaCurenta();
                    } else { alert("Eroare la reordonare!"); }
                });
            }
        });

        function schimbaLinia() {
            var linieId = document.getElementById('selectLinie').value;
            var selDir = document.getElementById('selectDirectie');
            selDir.innerHTML = '<option value="">-- Selectează Direcția --</option>';
            document.getElementById('routeList').style.display = 'none';
            if (polyline) map.removeLayer(polyline);
            currentDirId = null;

            if (linieId && directii[linieId]) {
                document.getElementById('divDirectie').style.display = 'block';
                directii[linieId].forEach(function(d) {
                    selDir.innerHTML += `<option value="${d.id}">${d.nume_directie} (${d.sens.toUpperCase()})</option>`;
                });
            } else {
                document.getElementById('divDirectie').style.display = 'linieId' ? 'block' : 'none';
            }
            document.getElementById('msgBox').innerHTML = "👈 Acum alege o Direcție!";
        }

        function schimbaDirectia() {
            currentDirId = document.getElementById('selectDirectie').value;
            if (!currentDirId) {
                document.getElementById('routeList').style.display = 'none';
                if (polyline) map.removeLayer(polyline);
                return;
            }

            document.getElementById('msgBox').innerHTML = "🟢 Traseul urmărește străzile automat!<br><b>Trage de stații în stânga sau dă click pe hartă!</b>";
            document.getElementById('routeList').style.display = 'block';
            
            afiseazaRutaCurenta();
        }

        function afiseazaRutaCurenta() {
            var itemsDiv = document.getElementById('routeItems');
            itemsDiv.innerHTML = '';
            var rutaCurenta = rute[currentDirId] || [];
            
            if (rutaCurenta.length === 0) {
                itemsDiv.innerHTML = '<p style="color:#8892b0; font-size:13px; text-align:center;">Traseul este gol.</p>';
            }

            var latlngs = [];
            Object.values(markers).forEach(m => m.setIcon(defaultIcon));

            rutaCurenta.forEach(function(r, index) {
                var statie = toateStatiile[r.statie_id];
                if(statie) {
                    itemsDiv.innerHTML += `
                        <div class="route-item" data-statie-id="${statie.id}">
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <span class="drag-handle">☰</span>
                                <div class="route-number">${index + 1}</div>
                                <span>${statie.nume_statie}</span>
                            </div>
                            <button class="btn-remove" onclick="stergeDinRuta(${statie.id}, ${r.ordine})" title="Elimină stația din traseu">✖</button>
                        </div>`;
                    
                    latlngs.push([statie.lat, statie.lng]);
                    markers[statie.id].setIcon(activeIcon);
                }
            });

            deseneazaTraseuPeHartaOSRM(latlngs);
        }

        // =========================================================
        // FUNCȚIA MAGICĂ: Desenează urmărind străzile (OSRM API)
        // =========================================================
        function deseneazaTraseuPeHartaOSRM(latlngs) {
            if (polyline) map.removeLayer(polyline);
            
            if (latlngs.length > 1) {
                // OSRM folosește formatul Lng,Lat
                var osrmCoords = latlngs.map(ll => ll[1] + ',' + ll[0]).join(';');
                var osrmUrl = 'https://router.project-osrm.org/route/v1/driving/' + osrmCoords + '?overview=full&geometries=geojson';

                fetch(osrmUrl)
                .then(res => res.json())
                .then(data => {
                    if(data.code === 'Ok' && data.routes.length > 0) {
                        // Extragem calea exactă de pe străzi și o facem înapoi [Lat, Lng]
                        var routeStrada = data.routes[0].geometry.coordinates.map(coord => [coord[1], coord[0]]);
                        
                        // Desenăm linia spectaculoasă
                        polyline = L.polyline(routeStrada, {
                            color: '#38bdf8', 
                            weight: 6, 
                            opacity: 0.8, 
                            lineJoin: 'round'
                        }).addTo(map);
                        
                        map.fitBounds(polyline.getBounds(), {padding: [50, 50]});
                    } else {
                        fallbackLinieDreapta(latlngs);
                    }
                })
                .catch(err => {
                    fallbackLinieDreapta(latlngs);
                });
            }
        }

        function fallbackLinieDreapta(latlngs) {
            polyline = L.polyline(latlngs, {color: '#dc3545', weight: 4, opacity: 0.8, dashArray: '10, 10'}).addTo(map);
            map.fitBounds(polyline.getBounds(), {padding: [50, 50]});
        }
        // =========================================================

        function adaugaStatieLaRuta(statieId, numeStatie) {
            if (!currentDirId) { alert("Alege linia și direcția mai întâi!"); return; }

            var rutaCurenta = rute[currentDirId] || [];
            if (rutaCurenta.length > 0 && rutaCurenta[rutaCurenta.length-1].statie_id == statieId) { return; }

            var formData = new FormData();
            formData.append('actiune', 'adauga_la_ruta');
            formData.append('directie_id', currentDirId);
            formData.append('statie_id', statieId);

            fetch('mapare_rute.php', { method: 'POST', body: formData })
            .then(res => res.text())
            .then(data => {
                if (data.trim() === 'OK') {
                    if (!rute[currentDirId]) rute[currentDirId] = [];
                    rute[currentDirId].push({statie_id: statieId, ordine: rutaCurenta.length + 1});
                    afiseazaRutaCurenta();
                } else { alert("Eroare la adăugarea în rută!"); }
            });
        }

        function stergeDinRuta(statieId, ordine) {
            if(!confirm("Ești sigur că vrei să elimini această stație din traseu?")) return;
            
            var formData = new FormData();
            formData.append('actiune', 'sterge_din_ruta');
            formData.append('directie_id', currentDirId);
            formData.append('statie_id', statieId);
            formData.append('ordine', ordine);

            fetch('mapare_rute.php', { method: 'POST', body: formData })
            .then(res => res.text())
            .then(data => {
                if (data.trim() === 'OK') {
                    rute[currentDirId] = rute[currentDirId].filter(r => !(r.statie_id == statieId && r.ordine == ordine));
                    rute[currentDirId].forEach(function(r, idx) { r.ordine = idx + 1; });
                    afiseazaRutaCurenta();
                } else { alert("Eroare la ștergerea stației!"); }
            });
        }

        function reseteazaRuta() {
            if (!currentDirId) return;
            if (confirm("⚠️ Ești sigur că vrei să ștergi TOT acest traseu?")) {
                var formData = new FormData();
                formData.append('actiune', 'reseteaza_ruta');
                formData.append('directie_id', currentDirId);

                fetch('mapare_rute.php', { method: 'POST', body: formData })
                .then(res => res.text())
                .then(data => {
                    if (data.trim() === 'OK') {
                        rute[currentDirId] = [];
                        afiseazaRutaCurenta();
                    }
                });
            }
        }

        function adaugaLinie() {
            var numar = prompt("Care este numărul liniei?");
            if (!numar) return;
            var tip = prompt("Ce tip de vehicul este? Scrie 'autobuz' sau 'tramvai'");
            if (tip !== 'autobuz' && tip !== 'tramvai') { alert("Te rog scrie exact 'autobuz' sau 'tramvai'!"); return; }

            var fd = new FormData();
            fd.append('actiune', 'adauga_linie'); fd.append('numar', numar); fd.append('tip', tip);
            fetch('mapare_rute.php', { method: 'POST', body: fd }).then(res => res.text()).then(data => {
                if(data.trim() === 'OK') window.location.reload(); else alert("Eroare!");
            });
        }

        function adaugaDirectie() {
            var linieId = document.getElementById('selectLinie').value;
            if (!linieId) { alert("Alege linia părinte mai întâi!"); return; }
            var nume = prompt("Cum se numește direcția?");
            if (!nume) return;
            var sens = prompt("Ce sens este? Scrie 'dus' sau 'intors'");
            if (sens !== 'dus' && sens !== 'intors') { alert("Te rog scrie exact 'dus' sau 'intors'!"); return; }

            var fd = new FormData();
            fd.append('actiune', 'adauga_directie'); fd.append('linie_id', linieId); fd.append('nume', nume); fd.append('sens', sens);
            fetch('mapare_rute.php', { method: 'POST', body: fd }).then(res => res.text()).then(data => {
                if(data.trim() === 'OK') window.location.reload(); else alert("Eroare!");
            });
        }
    </script>
</body>
</html>