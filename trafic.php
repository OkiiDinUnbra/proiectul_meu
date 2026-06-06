<?php
require_once 'db_connect.php';

if (!isset($_SESSION['confirmate'])) {
    $_SESSION['confirmate'] = [];
}

$mesaj_raport = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['adauga_raport'])) {
    if (isset($_SESSION['user_id'])) {
        $autor = isset($_SESSION['nume']) ? $_SESSION['nume'] : 'Vizitator';
        $tip = mysqli_real_escape_string($conn, $_POST['tip_problema']);
        $locatie = mysqli_real_escape_string($conn, $_POST['locatie']);
        $descriere = mysqli_real_escape_string($conn, $_POST['descriere']);
        
        $lat = isset($_POST['lat']) && !empty($_POST['lat']) ? floatval($_POST['lat']) : 45.2692;
        $lng = isset($_POST['lng']) && !empty($_POST['lng']) ? floatval($_POST['lng']) : 27.9575;
        
        if(!empty($locatie)) {
            $sql = "INSERT INTO rapoarte_trafic (autor, tip_problema, locatie, descriere, lat, lng) VALUES ('$autor', '$tip', '$locatie', '$descriere', $lat, $lng)";
            $conn->query($sql);
            $mesaj_raport = "Raportul a fost trimis și apare pe hartă!";
        }
    }
}

if (isset($_GET['confirma_id']) && isset($_SESSION['user_id'])) {
    $id_raport = (int)$_GET['confirma_id'];
    if (!in_array($id_raport, $_SESSION['confirmate'])) {
        $conn->query("UPDATE rapoarte_trafic SET confirmari = confirmari + 1 WHERE id = $id_raport");
        $_SESSION['confirmate'][] = $id_raport;

        $res_check = $conn->query("SELECT tip_problema, locatie, confirmari FROM rapoarte_trafic WHERE id = $id_raport");
        if ($res_check && $row = $res_check->fetch_assoc()) {
            if ($row['confirmari'] == 5) {
                require_once 'notificari.php';
                $subiect = "🚨 Alertă Trafic: " . $row['tip_problema'] . " în Brăila";
                $mesaj_html = "<h3 style='color: #ff4d4d;'>Atenție în trafic!</h3><p>Comunitatea a confirmat un incident major:</p><ul><li><strong>Tip:</strong> {$row['tip_problema']}</li><li><strong>Locație:</strong> {$row['locatie']}</li></ul><p>Accesează harta live pe site pentru a vedea rute ocolitoare!</p>";
                trimiteNotificareNewsletter($conn, $subiect, $mesaj_html);
            }
        }
    }
    header("Location: trafic.php"); 
    exit();
}

$rapoarte = [];
$res = $conn->query("SELECT * FROM rapoarte_trafic WHERE status = 'activ' ORDER BY confirmari DESC, data_raport DESC");
if ($res) { 
    while($row = $res->fetch_assoc()) { 
        $rapoarte[] = $row; 
    } 
}

$page_title = "Info Trafic Live | Descoperă Brăila";
include 'header.php';
?>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<style>
    .trafic-page { padding: 140px 20px 60px; max-width: 1200px; margin: 0 auto; color: var(--text-main); }
    .trafic-header { text-align: center; margin-bottom: 40px; }
    .trafic-header h1 { color: var(--text-main); font-size: 36px; margin-bottom: 10px; }
    
    .trafic-grid { display: flex; gap: 30px; flex-wrap: wrap; }
    .map-container { flex: 2; min-width: 300px; background: var(--card-bg); padding: 15px; border-radius: 16px; border: 1px solid var(--border-color); backdrop-filter: blur(10px); display: flex; flex-direction: column;}
    
    #mapaBraila { height: 500px; width: 100%; border-radius: 12px; z-index: 1;}
    .instructiuni-harta { display: none; padding: 10px; background: rgba(0, 123, 255, 0.1); border: 1px solid var(--link-color); border-radius: 8px; margin-bottom: 15px; text-align: center; font-weight: bold; color: var(--link-color);}
    
    /* BARA DE CĂUTARE STRADĂ */
    .harta-search-container { display: flex; gap: 10px; margin-top: 15px; }
    .harta-search-container input { flex: 1; padding: 12px 15px; border-radius: 8px; border: 1px solid var(--border-color); background: var(--input-bg); color: var(--text-main); font-family: inherit; font-size: 15px; outline: none; transition: border-color 0.3s;}
    .harta-search-container input:focus { border-color: var(--link-color); }
    .harta-search-container button { padding: 12px 25px; background: var(--link-color); color: white; border: none; border-radius: 8px; font-weight: bold; cursor: pointer; transition: 0.2s; font-size: 15px;}
    .harta-search-container button:hover { background: #0056b3; transform: translateY(-2px); }

    .reports-container { flex: 1; min-width: 300px; background: transparent; }

    .btn-toggle-raport { background: var(--accent-delete); color: white; border: none; padding: 14px; width: 100%; border-radius: 12px; font-size: 16px; font-weight: bold; cursor: pointer; transition: 0.3s; margin-bottom: 20px; box-shadow: 0 4px 15px rgba(220,53,69,0.3); }
    .btn-toggle-raport:hover { opacity: 0.9; transform: translateY(-2px); }
    .btn-login-raport { background: transparent; color: var(--text-main); border: 1px solid var(--border-color); padding: 14px; width: 100%; border-radius: 12px; font-size: 16px; font-weight: bold; cursor: pointer; transition: 0.3s; margin-bottom: 20px; }

    .form-raport-box { display: none; background: var(--card-bg); padding: 20px; border-radius: 15px; margin-bottom: 25px; border: 1px solid var(--accent-delete); color: var(--text-main); backdrop-filter: blur(10px); }
    .form-raport-box.active { display: block; animation: fadeInDown 0.3s; }
    
    .form-group input[type="text"], .form-group select, .form-group textarea { width: 100%; padding: 12px; border-radius: 8px; border: 1px solid var(--border-color); background: var(--input-bg); color: var(--text-main); margin-bottom: 15px; outline: none; }
    .btn-trimite { background: var(--accent-success); color: #000; border: none; padding: 12px; width: 100%; border-radius: 8px; font-weight: 700; cursor: pointer; }

    .raport-card-modern { background: var(--card-bg); border: 1px solid var(--border-color); border-left-width: 3px; border-radius: 12px; padding: 16px; margin-bottom: 16px; display: flex; justify-content: space-between; align-items: flex-start; backdrop-filter: blur(10px); transition: 0.2s;}
    .raport-card-modern:hover { transform: translateX(3px); cursor: pointer;} 
    .urgenta-mare { border-left-color: var(--accent-delete); }
    .urgenta-medie { border-left-color: var(--accent-edit); }
    .urgenta-mica { border-left-color: var(--text-lighter); }
    .raport-left { display: flex; flex-direction: column; gap: 4px; }
    .raport-titlu { font-size: 15px; font-weight: 700; display: flex; align-items: center; gap: 6px; }
    .urgenta-mare .raport-titlu { color: var(--accent-delete); }
    .urgenta-medie .raport-titlu { color: var(--accent-edit); }
    .urgenta-mica .raport-titlu { color: var(--text-light); }
    .raport-locatie { font-size: 14px; color: var(--text-main); font-weight: 500; }
    .raport-timp { font-size: 11px; color: var(--text-lighter); margin-top: 4px; }
    .raport-right { display: flex; flex-direction: column; align-items: flex-end; gap: 10px; }
    .confirmari-badge { font-size: 12px; font-weight: 700; padding: 4px 10px; border-radius: 20px; white-space: nowrap; }
    .confirmari-badge.mare { color: var(--accent-delete); background: rgba(220,53,69,0.1); }
    .confirmari-badge.medie { color: var(--accent-edit); background: rgba(255,193,7,0.1); }
    .confirmari-badge.mica { color: var(--text-light); background: rgba(255,255,255,0.05); }
    .btn-confirma { background: transparent; border: 1px solid var(--border-color); color: var(--text-main); text-decoration: none; padding: 4px 10px; border-radius: 6px; font-size: 11px; font-weight: 600; transition: 0.2s; }
    @keyframes fadeInDown { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
    
    .leaflet-popup-content-wrapper { background: var(--card-bg); color: var(--text-main); border: 1px solid var(--border-color); border-radius: 12px;}
    .leaflet-popup-tip { background: var(--card-bg); }
    .leaflet-popup-content h3 { margin: 0 0 5px 0; font-size: 16px; }
    .leaflet-popup-content p { margin: 0; font-size: 13px; color: var(--text-light);}
</style>

<div class="trafic-page">
    <div class="trafic-header">
        <h1>🚦 Info Trafic & Radar Brăila</h1>
        <p style="color: var(--text-light);">Harta live interactivă și alertele raportate de comunitate.</p>
    </div>

    <div class="trafic-grid">
        <div class="map-container">
            <div id="instructiuniHarta" class="instructiuni-harta">
                📍 Click pe hartă în locul exact unde se află incidentul!
            </div>
            
            <div id="mapaBraila"></div>
            
            <div class="harta-search-container">
                <input type="text" id="inputCautaStrada" placeholder="Caută o stradă (ex: Călărașilor, Brăila)...">
                <button type="button" onclick="cautaStrada()">🔍 Caută</button>
            </div>
        </div>

        <div class="reports-container">
            <?php if($mesaj_raport) echo "<div style='background:rgba(40,167,69,0.15); color:var(--accent-success); border: 1px solid var(--accent-success); padding:10px; border-radius:8px; margin-bottom:15px; text-align:center; font-weight:bold;'>$mesaj_raport</div>"; ?>
            
            <?php if(isset($_SESSION['user_id'])): ?>
                <button class="btn-toggle-raport" id="btnRaporteaza" onclick="toggleForm()">🚨 Raportează un incident</button>
                
                <div class="form-raport-box" id="boxRaportare">
                    <h3 style="margin-bottom: 15px; font-size: 18px;">Adaugă o alertă nouă</h3>
                    <form method="POST" action="" id="formTrafic">
                        <div class="form-group">
                            <select name="tip_problema" required>
                                <option value="" disabled selected>Alege tipul incidentului...</option>
                                <option value="Accident">Accident Rutier</option>
                                <option value="Trafic Blocat">Trafic Blocat / Aglomerație</option>
                                <option value="Groapă Periculoasă">Groapă Periculoasă</option>
                                <option value="Semafor Defect">Semafor Defect</option>
                                <option value="Filtru Poliție">Filtru Poliție</option>
                            </select>
                            <input type="text" name="locatie" id="numeStradalGasit" placeholder="Nume Stradă / Intersecție..." required>
                            <textarea name="descriere" rows="2" placeholder="Detalii scurte (opțional)..."></textarea>
                            
                            <input type="hidden" name="lat" id="formLat">
                            <input type="hidden" name="lng" id="formLng">
                            
                            <button type="submit" name="adauga_raport" class="btn-trimite" id="btnTrimite" disabled style="opacity: 0.5; cursor: not-allowed;">Alege locația pe hartă mai întâi</button>
                        </div>
                    </form>
                </div>
            <?php else: ?>
                <button class="btn-login-raport" onclick="openPopup('loginPopup')">🔒 Autentifică-te pentru a raporta</button>
            <?php endif; ?>

            <div style="max-height: 480px; overflow-y: auto; padding-right: 5px;">
                <?php if(empty($rapoarte)): ?>
                    <p style="color: var(--text-light); text-align: center; margin-top: 20px;">Niciun incident raportat recent.</p>
                <?php else: ?>
                    <?php foreach($rapoarte as $r): 
                        $tip = $r['tip_problema'];
                        $class_urgenta = 'urgenta-mica'; $badge_urgenta = 'mica'; $icon = '⚠️';
                        if (in_array($tip, ['Accident', 'Trafic Blocat'])) {
                            $class_urgenta = 'urgenta-mare'; $badge_urgenta = 'mare'; $icon = '🚨';
                        } elseif (in_array($tip, ['Groapă Periculoasă', 'Semafor Defect', 'Filtru Poliție'])) {
                            $class_urgenta = 'urgenta-medie'; $badge_urgenta = 'medie'; $icon = '🚧';
                        }
                        $dejaConfirmat = in_array($r['id'], $_SESSION['confirmate']);
                        $esteAutorul = (isset($_SESSION['nume']) && $r['autor'] === $_SESSION['nume']);
                        $esteLogat = isset($_SESSION['user_id']);
                    ?>
                        <div class="raport-card-modern <?= $class_urgenta ?>" onclick="focalizeazaHarta(<?= $r['lat'] ?? 45.2692 ?>, <?= $r['lng'] ?? 27.9575 ?>)">
                            <div class="raport-left">
                                <div class="raport-titlu"><?= $icon ?> <?= htmlspecialchars($tip) ?></div>
                                <div class="raport-locatie"><?= htmlspecialchars($r['locatie']) ?></div>
                                <div class="raport-timp">Raportat de <?= htmlspecialchars($r['autor']) ?> • <?= date('d.m H:i', strtotime($r['data_raport'])) ?></div>
                            </div>
                            
                            <div class="raport-right">
                                <div class="confirmari-badge <?= $badge_urgenta ?>"><?= $r['confirmari'] ?> confirmări</div>
                                <?php if ($esteLogat): ?>
                                    <?php if ($esteAutorul): ?>
                                        <span class="btn-confirma disabled">Raportul tău</span>
                                    <?php elseif ($dejaConfirmat): ?>
                                        <span class="btn-confirma disabled">Confirmat</span>
                                    <?php else: ?>
                                        <a href="trafic.php?confirma_id=<?= $r['id'] ?>" class="btn-confirma" onclick="event.stopPropagation();">+ Confirmă</a>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
// Inițializăm harta
var map = L.map('mapaBraila').setView([45.2692, 27.9575], 13);
L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png', {
    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
    maxZoom: 19
}).addTo(map);

var rapoarteJS = <?php echo json_encode($rapoarte); ?>;

function getIconForType(tip) {
    let emoji = '⚠️'; let color = '#6c757d';
    if (['Accident', 'Trafic Blocat'].includes(tip)) { emoji = '🚨'; color = '#dc3545'; } 
    else if (['Groapă Periculoasă', 'Semafor Defect', 'Filtru Poliție'].includes(tip)) { emoji = '🚧'; color = '#ffc107'; }

    return L.divIcon({
        className: 'custom-pin',
        html: `<div style="background:${color}; color:white; width:30px; height:30px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:16px; box-shadow:0 0 10px rgba(0,0,0,0.5); border:2px solid white;">${emoji}</div>`,
        iconSize: [30, 30], iconAnchor: [15, 15], popupAnchor: [0, -15]
    });
}

rapoarteJS.forEach(function(r) {
    if(r.lat && r.lng) {
        var marker = L.marker([parseFloat(r.lat), parseFloat(r.lng)], {icon: getIconForType(r.tip_problema)}).addTo(map);
        marker.bindPopup(`
            <h3 style="color:var(--text-main); margin-bottom:5px;">${r.tip_problema}</h3>
            <p><strong>Locație:</strong> ${r.locatie}</p>
            <p><strong>Confirmări:</strong> ${r.confirmari}</p>
        `);
    }
});

var modRaportare = false;
var markerNou = null;

function toggleForm() {
    var box = document.getElementById("boxRaportare");
    var instr = document.getElementById("instructiuniHarta");
    var btnRaporteaza = document.getElementById("btnRaporteaza");
    
    box.classList.toggle("active");
    
    if (box.classList.contains("active")) {
        modRaportare = true; instr.style.display = "block"; btnRaporteaza.innerText = "❌ Anulează raportarea"; btnRaporteaza.style.background = "#6c757d"; map.getContainer().style.cursor = 'crosshair';
    } else {
        modRaportare = false; instr.style.display = "none"; btnRaporteaza.innerText = "🚨 Raportează un incident"; btnRaporteaza.style.background = "var(--accent-delete)"; map.getContainer().style.cursor = '';
        if(markerNou) map.removeLayer(markerNou);
    }
}

map.on('click', function(e) {
    if(!modRaportare) return;
    
    var lat = e.latlng.lat;
    var lng = e.latlng.lng;
    
    document.getElementById('formLat').value = lat;
    document.getElementById('formLng').value = lng;
    
    var btnTrimite = document.getElementById('btnTrimite');
    btnTrimite.disabled = false; btnTrimite.style.opacity = 1; btnTrimite.style.cursor = "pointer"; btnTrimite.innerText = "Trimite Alerta";
    document.getElementById('instructiuniHarta').innerHTML = "✅ Locație preluată! Completează formularul.";
    document.getElementById('instructiuniHarta').style.color = "var(--accent-success)";
    
    if (markerNou) { map.removeLayer(markerNou); }
    var userIcon = L.divIcon({
        className: 'custom-pin-new',
        html: `<div style="background:#007bff; color:white; width:30px; height:30px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:16px; box-shadow:0 0 10px rgba(0,0,0,0.5); border:2px solid white;">📍</div>`,
        iconSize: [30, 30]
    });
    markerNou = L.marker([lat, lng], {icon: userIcon}).addTo(map);
});

function focalizeazaHarta(lat, lng) { map.flyTo([lat, lng], 16, { animate: true, duration: 1.5 }); }

// === LOGICA NOUĂ: CĂUTAREA STRĂZILOR ===
function cautaStrada() {
    var input = document.getElementById("inputCautaStrada").value;
    if (input.trim() === "") return;

    // Forțăm căutarea în zona orașului Brăila
    var query = encodeURIComponent(input + ", Brăila, România");
    var url = "https://nominatim.openstreetmap.org/search?format=json&limit=1&q=" + query;

    fetch(url)
        .then(response => response.json())
        .then(data => {
            if (data && data.length > 0) {
                var gasitLat = parseFloat(data[0].lat);
                var gasitLon = parseFloat(data[0].lon);
                
                // Zburăm către locație
                map.flyTo([gasitLat, gasitLon], 17, { animate: true, duration: 1.5 });
                
                // Dacă utilizatorul este în modul "Raportare", completăm automat inputul de locație
                if(modRaportare) {
                    document.getElementById('numeStradalGasit').value = data[0].name || input;
                }
            } else {
                alert("Nu am găsit această locație. Încearcă să fii mai specific (ex: Calea Călărașilor).");
            }
        })
        .catch(err => console.error("Eroare Nominatim:", err));
}

// Permite căutarea direct cu tasta Enter
document.getElementById("inputCautaStrada").addEventListener("keypress", function(event) {
    if (event.key === "Enter") {
        event.preventDefault();
        cautaStrada();
    }
});
</script>

<?php include 'footer.php'; ?>