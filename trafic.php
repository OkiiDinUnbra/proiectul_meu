<?php
require_once 'db_connect.php';

if (!isset($_SESSION['confirmate'])) {
    $_SESSION['confirmate'] = [];
}
if (!isset($_SESSION['dislikes'])) {
    $_SESSION['dislikes'] = [];
}

$mesaj_raport = '';

// === 1. ADAUGĂ RAPORT ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['adauga_raport'])) {
    if (isset($_SESSION['user_id'])) {
        $autor = isset($_SESSION['nume']) ? $_SESSION['nume'] : 'Vizitator';
        $tip = $_POST['tip_problema'];
        $locatie = trim($_POST['locatie']);
        $descriere = trim($_POST['descriere']);
        
        $lat = isset($_POST['lat']) && !empty($_POST['lat']) ? floatval($_POST['lat']) : 45.2692;
        $lng = isset($_POST['lng']) && !empty($_POST['lng']) ? floatval($_POST['lng']) : 27.9575;

        if(!empty($locatie)) {
            $stmt = $conn->prepare("INSERT INTO rapoarte_trafic (autor, tip_problema, locatie, descriere, lat, lng) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssdd", $autor, $tip, $locatie, $descriere, $lat, $lng);
            
            if ($stmt->execute()) {
                $mesaj_raport = "Raportul a fost adăugat pe hartă!";
            }
            $stmt->close();
        }
    }
}

// === 2. CONFIRMĂ RAPORT & TRIMITE NOTIFICARE LA 5 CONFIRMĂRI ===
if (isset($_GET['confirma_id']) && isset($_SESSION['user_id'])) {
    $id_raport = (int)$_GET['confirma_id'];
    if (!in_array($id_raport, $_SESSION['confirmate'])) {
        $conn->query("UPDATE rapoarte_trafic SET confirmari = confirmari + 1 WHERE id = $id_raport");
        $_SESSION['confirmate'][] = $id_raport;

        // VERIFICARE PENTRU NOTIFICARE NEWSLETTER (REINTEGRATĂ)
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

// === 3. DISLIKE RAPORT (Filtru Poliție / Semafor Defect) ===
if (isset($_GET['dislike_id']) && isset($_SESSION['user_id'])) {
    $id_dislike = (int)$_GET['dislike_id'];
    if (!in_array($id_dislike, $_SESSION['dislikes'])) {
        $conn->query("UPDATE rapoarte_trafic SET dislikes = COALESCE(dislikes, 0) + 1 WHERE id = $id_dislike");
        $_SESSION['dislikes'][] = $id_dislike;
    }
    header("Location: trafic.php"); 
    exit();
}

// === 4. ȘTERGERE ADMIN ===
if (isset($_GET['sterge_id']) && isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin') {
    $id_stergere = (int)$_GET['sterge_id'];
    $conn->query("UPDATE rapoarte_trafic SET status = 'rezolvat' WHERE id = $id_stergere");
    header("Location: trafic.php");
    exit();
}

// === 5. PRELUARE RAPOARTE ===
// Pentru a activa expirarea la 3 ore / 5 zile, șterge comentariile /* și */ de mai jos
$rapoarte = [];
$query = "
    SELECT * FROM rapoarte_trafic 
    WHERE status = 'activ' 
      AND COALESCE(dislikes, 0) < 10 
      
      /* DECOMENTEAZĂ LINIA DE MAI JOS PENTRU A ACTIVA EXPIRAREA AUTOMATĂ */
      /* AND (
        (tip_problema IN ('Accident', 'Trafic Blocat') AND data_raport >= DATE_SUB(NOW(), INTERVAL 3 HOUR))
        OR 
        (tip_problema IN ('Semafor Defect', 'Filtru Poliție') AND data_raport >= DATE_SUB(NOW(), INTERVAL 12 HOUR))
        OR 
        (tip_problema = 'Groapă Periculoasă' AND data_raport >= DATE_SUB(NOW(), INTERVAL 5 DAY))
      ) */
      
    ORDER BY confirmari DESC, data_raport DESC
";

$res = $conn->query($query);
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
    /* === IDENTITATE VIZUALĂ PREMIUM === */
    html, body {
        margin: 0 !important;
        padding: 0 !important;
        background: url('img/braila1.jpg') no-repeat center center fixed; 
        background-size: cover;
        overflow: hidden; 
    }

    .overlay-bg { 
        position: fixed; top: 0; left: 0; width: 100%; height: 100%; 
        background: rgba(10, 25, 47, 0.85); 
        z-index: 0;
    }

    /* === LAYOUT FULL SCREEN === */
    .trafic-page { 
        position: relative; z-index: 1; 
        padding: 90px 20px 20px; 
        max-width: 100%; 
        height: 100vh; 
        display: flex; 
        flex-direction: column; 
        box-sizing: border-box;
    }

    .trafic-header { text-align: left; margin-bottom: 20px; padding-left: 10px;}
    .trafic-header h1 { color: #ffffff; font-size: 38px; margin-bottom: 5px; font-weight: 800; text-shadow: 0 4px 10px rgba(0,0,0,0.5);}
    .trafic-header p { color: #8892b0; font-size: 16px; margin: 0; font-weight: 500;}
    
    .trafic-grid { 
        display: flex; 
        gap: 20px; 
        flex: 1; 
        overflow: hidden; 
    }

    /* === HARTA (75% STÂNGA) === */
    .map-container { 
        flex: 3; 
        display: flex; 
        flex-direction: column; 
        background: rgba(255,255,255,0.03); 
        padding: 15px; 
        border-radius: 16px; 
        border: 1px solid rgba(255,255,255,0.1); 
        backdrop-filter: blur(15px); 
    }
    
    #mapaBraila { flex: 1; width: 100%; border-radius: 12px; z-index: 1;}
    .instructiuni-harta { display: none; padding: 15px; background: rgba(56, 189, 248, 0.1); border: 1px solid #38bdf8; border-radius: 12px; margin-bottom: 15px; text-align: center; font-weight: 700; color: #38bdf8;}
    
    .harta-search-container { display: flex; gap: 15px; margin-top: 15px; }
    .harta-search-container input { flex: 1; padding: 15px 20px; border-radius: 12px; border: 1px solid rgba(255,255,255,0.2); background: rgba(0,0,0,0.3); color: #fff; font-family: inherit; font-size: 16px; outline: none; transition: 0.3s;}
    .harta-search-container input:focus { border-color: #38bdf8; background: rgba(0,0,0,0.5);}
    .harta-search-container button { padding: 15px 30px; background: #007bff; color: white; border: none; border-radius: 12px; font-weight: bold; cursor: pointer; transition: 0.2s; font-size: 16px;}
    .harta-search-container button:hover { background: #38bdf8; color: #0f172a; }

    /* === CHAT / RAPOARTE (25% DREAPTA) === */
    .reports-container { 
        flex: 1; 
        min-width: 380px; 
        max-width: 450px;
        background: rgba(10, 25, 47, 0.6); 
        border-radius: 16px; 
        border: 1px solid rgba(255,255,255,0.1); 
        backdrop-filter: blur(20px); 
        display: flex; 
        flex-direction: column; 
        padding: 20px;
        box-sizing: border-box;
    }

    .reports-list {
        flex: 1;
        overflow-y: auto; 
        padding-right: 10px;
    }
    .reports-list::-webkit-scrollbar { width: 6px; }
    .reports-list::-webkit-scrollbar-track { background: rgba(255,255,255,0.05); border-radius: 10px; }
    .reports-list::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.2); border-radius: 10px; }

    .btn-toggle-raport { background: #dc3545; color: white; border: none; padding: 16px; width: 100%; border-radius: 12px; font-size: 16px; font-weight: 800; cursor: pointer; transition: 0.3s; margin-bottom: 20px; box-shadow: 0 4px 15px rgba(220,53,69,0.3); flex-shrink: 0;}
    .btn-toggle-raport:hover { background: #c82333; transform: translateY(-2px); }
    .btn-login-raport { background: rgba(0,123,255,0.1); color: #38bdf8; border: 1px solid #38bdf8; padding: 16px; width: 100%; border-radius: 12px; font-size: 16px; font-weight: bold; cursor: pointer; transition: 0.3s; margin-bottom: 20px; flex-shrink: 0;}

    /* FORMULAR RAPORTARE */
    .form-raport-box { display: none; background: rgba(0,0,0,0.4); padding: 20px; border-radius: 12px; margin-bottom: 20px; border: 1px solid #38bdf8; flex-shrink: 0; }
    .form-raport-box.active { display: block; animation: fadeInDown 0.3s; }
    .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 12px; border-radius: 8px; border: 1px solid rgba(255,255,255,0.2); background: rgba(255,255,255,0.05); color: #fff; margin-bottom: 15px; outline: none; box-sizing: border-box;}
    .form-group select option { background: #0A192F; }
    .btn-trimite { background: #10b981; color: #fff; border: none; padding: 14px; width: 100%; border-radius: 8px; font-weight: 800; cursor: pointer; transition: 0.3s; font-size: 15px;}

    /* CARDURI RAPOARTE */
    .raport-card-modern { background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.05); border-left-width: 4px; border-radius: 12px; padding: 16px; margin-bottom: 15px; display: flex; justify-content: space-between; align-items: flex-start; transition: 0.2s; position: relative;}
    .raport-card-modern:hover { background: rgba(255,255,255,0.08); transform: translateX(4px); cursor: pointer;} 
    
    .urgenta-mare { border-left-color: #dc3545; }
    .urgenta-medie { border-left-color: #f59e0b; }
    .urgenta-mica { border-left-color: #38bdf8; }
    
    .raport-left { display: flex; flex-direction: column; gap: 4px; padding-right: 30px;}
    .raport-titlu { font-size: 16px; font-weight: 800; display: flex; align-items: center; gap: 6px; }
    .urgenta-mare .raport-titlu { color: #ef4444; }
    .urgenta-medie .raport-titlu { color: #f59e0b; }
    .urgenta-mica .raport-titlu { color: #38bdf8; }
    .raport-locatie { font-size: 14px; color: #e2e8f0; font-weight: 600; line-height: 1.4; margin-top: 5px;}
    .raport-timp { font-size: 12px; color: #8892b0; margin-top: 8px; font-weight: 500;}
    
    .raport-right { display: flex; flex-direction: column; align-items: flex-end; gap: 10px; }
    .confirmari-badge { font-size: 12px; font-weight: 800; padding: 5px 12px; border-radius: 20px; white-space: nowrap; }
    .confirmari-badge.mare { color: #ef4444; background: rgba(239, 68, 68, 0.1); }
    .confirmari-badge.medie { color: #f59e0b; background: rgba(245, 158, 11, 0.1); }
    .confirmari-badge.mica { color: #38bdf8; background: rgba(56, 189, 248, 0.1); }
    
    .action-buttons { display: flex; gap: 5px; }
    .btn-action { background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); color: #fff; text-decoration: none; padding: 6px 12px; border-radius: 6px; font-size: 12px; font-weight: 700; transition: 0.2s; }
    .btn-action:hover { background: #007bff; border-color: #007bff; }
    .btn-dislike:hover { background: #f59e0b; border-color: #f59e0b; color: #000; }
    
    .btn-admin-delete { position: absolute; top: 10px; right: 10px; background: rgba(220,53,69,0.2); color: #ff4d4d; border: 1px solid #ff4d4d; width: 24px; height: 24px; border-radius: 50%; display: flex; align-items: center; justify-content: center; text-decoration: none; font-size: 12px; font-weight: bold; transition: 0.2s;}
    .btn-admin-delete:hover { background: #ff4d4d; color: white; transform: scale(1.1); }

    @keyframes fadeInDown { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
</style>

<div class="overlay-bg"></div>

<div class="trafic-page">
    <div class="trafic-header">
        <h1>🚦 Info Trafic & Radar Live</h1>
        <p>Monitorizare inteligentă cu alerte în timp real și confirmări din partea comunității.</p>
    </div>

    <div class="trafic-grid fade-up-element">
        <!-- HARTA STÂNGA -->
        <div class="map-container">
            <div id="instructiuniHarta" class="instructiuni-harta">
                📍 Click pe hartă în locul exact unde se află incidentul! Preluăm automat numele străzii.
            </div>
            
            <div id="mapaBraila"></div>
            
            <div class="harta-search-container">
                <button type="button" onclick="centerOnMe()" style="background: #10b981; min-width: 170px;">📍 Locația Mea</button>
                <input type="text" id="inputCautaStrada" placeholder="Caută o stradă pentru a naviga harta (ex: Călărașilor)...">
                <button type="button" onclick="cautaStrada()">🔍 Navighează</button>
            </div>
        </div>

        <!-- RAPOARTE DREAPTA -->
        <div class="reports-container">
            <?php if($mesaj_raport) echo "<div style='background:rgba(16,185,129,0.2); color:#10b981; border: 1px solid #10b981; padding:12px; border-radius:8px; margin-bottom:15px; text-align:center; font-weight:bold;'>$mesaj_raport</div>"; ?>
            
            <?php if(isset($_SESSION['user_id'])): ?>
                <button class="btn-toggle-raport" id="btnRaporteaza" onclick="toggleForm()">🚨 Raportează un incident</button>
                
                <div class="form-raport-box" id="boxRaportare">
                    <h3 style="margin-bottom: 15px; font-size: 18px; color: #fff;">Completează Detaliile</h3>
                    <form method="POST" action="" id="formTrafic">
                        <div class="form-group">
                            <select name="tip_problema" required>
                                <option value="" disabled selected>Alege tipul incidentului...</option>
                                <option value="Accident">Accident Rutier (dispare în 3h)</option>
                                <option value="Trafic Blocat">Trafic Blocat (dispare în 3h)</option>
                                <option value="Filtru Poliție">Filtru Poliție (dispare în 12h / 10 dislikes)</option>
                                <option value="Semafor Defect">Semafor Defect (dispare în 12h / 10 dislikes)</option>
                                <option value="Groapă Periculoasă">Groapă Periculoasă (dispare în 5 zile)</option>
                            </select>
                            <input type="text" name="locatie" id="numeStradalGasit" placeholder="Nume Stradă va fi completat automat..." required>
                            <textarea name="descriere" rows="2" placeholder="Detalii suplimentare (opțional)..."></textarea>
                            
                            <input type="hidden" name="lat" id="formLat">
                            <input type="hidden" name="lng" id="formLng">
                            
                            <button type="submit" name="adauga_raport" class="btn-trimite" id="btnTrimite" disabled style="opacity: 0.5; cursor: not-allowed;">Alege locația pe hartă mai întâi</button>
                        </div>
                    </form>
                </div>
            <?php else: ?>
                <button class="btn-login-raport" onclick="openPopup('loginPopup')">🔒 Autentifică-te pentru a raporta</button>
            <?php endif; ?>

            <div class="reports-list">
                <?php if(empty($rapoarte)): ?>
                    <p style="color: #8892b0; text-align: center; margin-top: 40px; font-size: 16px;">Traficul este curat! Niciun incident activ momentan. ✅</p>
                <?php else: ?>
                    <?php foreach($rapoarte as $r): 
                        $tip = $r['tip_problema'];
                        $class_urgenta = 'urgenta-mica'; $badge_urgenta = 'mica'; $icon = '⚠️';
                        $permite_dislike = false;

                        if (in_array($tip, ['Accident', 'Trafic Blocat'])) {
                            $class_urgenta = 'urgenta-mare'; $badge_urgenta = 'mare'; $icon = '🚨';
                        } elseif (in_array($tip, ['Groapă Periculoasă'])) {
                            $class_urgenta = 'urgenta-mica'; $badge_urgenta = 'mica'; $icon = '🚧';
                        } elseif (in_array($tip, ['Semafor Defect', 'Filtru Poliție'])) {
                            $class_urgenta = 'urgenta-medie'; $badge_urgenta = 'medie'; $icon = '🚓';
                            $permite_dislike = true;
                        }

                        $dejaConfirmat = in_array($r['id'], $_SESSION['confirmate']);
                        $dejaDislike = isset($_SESSION['dislikes']) && in_array($r['id'], $_SESSION['dislikes']);
                        $esteAutorul = (isset($_SESSION['nume']) && $r['autor'] === $_SESSION['nume']);
                        $esteLogat = isset($_SESSION['user_id']);
                        $isAdmin = (isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin');
                    ?>
                        <div class="raport-card-modern <?= $class_urgenta ?>" onclick="focalizeazaHarta(<?= $r['lat'] ?? 45.2692 ?>, <?= $r['lng'] ?? 27.9575 ?>)">
                            
                            <?php if ($isAdmin): ?>
                                <a href="trafic.php?sterge_id=<?= $r['id'] ?>" class="btn-admin-delete" onclick="return confirm('Ești sigur că problema s-a rezolvat și vrei să ștergi raportul?');" title="Șterge Raport">✖</a>
                            <?php endif; ?>

                            <div class="raport-left">
                                <div class="raport-titlu"><?= $icon ?> <?= htmlspecialchars($tip) ?></div>
                                <div class="raport-locatie"><?= htmlspecialchars($r['locatie']) ?></div>
                                <div class="raport-timp">Raportat de <?= htmlspecialchars($r['autor']) ?> • <?= date('H:i (d.m)', strtotime($r['data_raport'])) ?></div>
                            </div>
                            
                            <div class="raport-right" style="margin-top: 25px;">
                                <div class="confirmari-badge <?= $badge_urgenta ?>"><?= $r['confirmari'] ?> de acord</div>
                                
                                <div class="action-buttons">
                                    <?php if ($esteLogat && !$esteAutorul && !$dejaConfirmat): ?>
                                        <a href="trafic.php?confirma_id=<?= $r['id'] ?>" class="btn-action" onclick="event.stopPropagation();">👍 Da</a>
                                    <?php endif; ?>

                                    <?php if ($permite_dislike && $esteLogat && !$esteAutorul && !$dejaDislike): ?>
                                        <a href="trafic.php?dislike_id=<?= $r['id'] ?>" class="btn-action btn-dislike" onclick="event.stopPropagation();">👎 Nu (<?= $r['dislikes'] ?? 0 ?>)</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
// Inițializare Hartă
var map = L.map('mapaBraila').setView([45.2692, 27.9575], 14);
L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png', {
    attribution: '&copy; OpenStreetMap',
    maxZoom: 19
}).addTo(map);

var rapoarteJS = <?php echo json_encode($rapoarte); ?>;

function getIconForType(tip) {
    let emoji = '⚠️'; let color = '#38bdf8';
    if (['Accident', 'Trafic Blocat'].includes(tip)) { emoji = '🚨'; color = '#dc3545'; } 
    else if (['Groapă Periculoasă'].includes(tip)) { emoji = '🚧'; color = '#38bdf8'; }
    else if (['Semafor Defect', 'Filtru Poliție'].includes(tip)) { emoji = '🚓'; color = '#f59e0b'; }

    return L.divIcon({
        className: 'custom-pin',
        html: `<div style="background:${color}; color:white; width:34px; height:34px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:18px; box-shadow:0 0 15px rgba(0,0,0,0.6); border:2px solid white;">${emoji}</div>`,
        iconSize: [34, 34], iconAnchor: [17, 17], popupAnchor: [0, -17]
    });
}

rapoarteJS.forEach(function(r) {
    if(r.lat && r.lng) {
        var marker = L.marker([parseFloat(r.lat), parseFloat(r.lng)], {icon: getIconForType(r.tip_problema)}).addTo(map);
        marker.bindPopup(`
            <h3 style="color:#0f172a; margin-bottom:5px;">${r.tip_problema}</h3>
            <p style="color:#333; margin:0;"><strong>Locație:</strong> ${r.locatie}</p>
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
        modRaportare = true; instr.style.display = "block"; btnRaporteaza.innerText = "❌ Anulează raportarea"; btnRaporteaza.style.background = "#475569"; map.getContainer().style.cursor = 'crosshair';
    } else {
        modRaportare = false; instr.style.display = "none"; btnRaporteaza.innerText = "🚨 Raportează un incident"; btnRaporteaza.style.background = "#dc3545"; map.getContainer().style.cursor = '';
        if(markerNou) map.removeLayer(markerNou);
        document.getElementById('numeStradalGasit').value = '';
        document.getElementById('btnTrimite').disabled = true;
        document.getElementById('btnTrimite').style.opacity = 0.5;
    }
}

var userLocationMarker = null;
var userLocationCircle = null;

function centerOnMe() {
    map.locate({setView: true, maxZoom: 16, enableHighAccuracy: true, timeout: 10000});
    document.getElementById('instructiuniHarta').style.display = "block";
    document.getElementById('instructiuniHarta').innerHTML = "⏳ Se caută semnal GPS precis...";
    document.getElementById('instructiuniHarta').style.color = "#f59e0b";
}

map.on('locationfound', function(e) {
    var radius = e.accuracy / 2;
    
    if (userLocationMarker) map.removeLayer(userLocationMarker);
    if (userLocationCircle) map.removeLayer(userLocationCircle);

    var meIcon = L.divIcon({
        className: 'custom-pin-me',
        html: `<div style="background:#0A192F; color:white; width:34px; height:34px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:18px; box-shadow:0 0 15px rgba(0,0,0,0.6); border:2px solid #38bdf8;">📍</div>`,
        iconSize: [34, 34], iconAnchor: [17, 17], popupAnchor: [0, -17]
    });

    userLocationMarker = L.marker(e.latlng, {icon: meIcon}).addTo(map)
        .bindPopup("Te afli aici! (Marjă precizie: " + Math.round(e.accuracy) + "m)").openPopup();
    
    userLocationCircle = L.circle(e.latlng, radius, {color: '#38bdf8', fillColor: '#38bdf8', fillOpacity: 0.2}).addTo(map);

    document.getElementById('instructiuniHarta').innerHTML = "✅ Locație GPS preluată cu succes!";
    document.getElementById('instructiuniHarta').style.color = "#10b981";
    setTimeout(() => { 
        if(!modRaportare) document.getElementById('instructiuniHarta').style.display = "none"; 
    }, 4000);
});

map.on('locationerror', function(e) {
    alert("Nu am putut prelua locația! Te rog să dai 'Permite' (Allow) browser-ului să îți acceseze locația și să te asiguri că GPS-ul este pornit.");
    if(!modRaportare) document.getElementById('instructiuniHarta').style.display = "none";
});

document.addEventListener("DOMContentLoaded", () => {
    map.locate({setView: false, maxZoom: 15, enableHighAccuracy: true});
});

map.on('click', function(e) {
    if(!modRaportare) return;
    
    var lat = e.latlng.lat;
    var lng = e.latlng.lng;
    
    document.getElementById('formLat').value = lat;
    document.getElementById('formLng').value = lng;
    
    document.getElementById('instructiuniHarta').innerHTML = "⏳ Se identifică strada...";
    document.getElementById('instructiuniHarta').style.color = "#f59e0b";

    fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}`)
        .then(response => response.json())
        .then(data => {
            let address = data.address;
            let streetName = address.road || address.pedestrian || address.suburb || "Locație selectată pe hartă";
            
            document.getElementById('numeStradalGasit').value = streetName;
            
            var btnTrimite = document.getElementById('btnTrimite');
            btnTrimite.disabled = false; btnTrimite.style.opacity = 1; btnTrimite.style.cursor = "pointer"; btnTrimite.innerText = "Trimite Alerta";
            
            document.getElementById('instructiuniHarta').innerHTML = "✅ Locație preluată! Selectează tipul și trimite.";
            document.getElementById('instructiuniHarta').style.color = "#10b981";
        })
        .catch(err => {
            document.getElementById('numeStradalGasit').value = "Locație selectată manual";
            document.getElementById('btnTrimite').disabled = false; document.getElementById('btnTrimite').style.opacity = 1;
        });
    
    if (markerNou) { map.removeLayer(markerNou); }
    var userIcon = L.divIcon({
        className: 'custom-pin-new',
        html: `<div style="background:#10b981; color:white; width:34px; height:34px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:18px; box-shadow:0 0 15px rgba(0,0,0,0.6); border:2px solid white;">📍</div>`,
        iconSize: [34, 34]
    });
    markerNou = L.marker([lat, lng], {icon: userIcon}).addTo(map);
});

function focalizeazaHarta(lat, lng) { map.flyTo([lat, lng], 17, { animate: true, duration: 1.2 }); }

function cautaStrada() {
    var input = document.getElementById("inputCautaStrada").value;
    if (input.trim() === "") return;

    var query = encodeURIComponent(input + ", Brăila, România");
    var url = "https://nominatim.openstreetmap.org/search?format=json&limit=1&q=" + query;

    fetch(url)
        .then(response => response.json())
        .then(data => {
            if (data && data.length > 0) {
                var gasitLat = parseFloat(data[0].lat);
                var gasitLon = parseFloat(data[0].lon);
                map.flyTo([gasitLat, gasitLon], 17, { animate: true, duration: 1.5 });
            } else {
                alert("Nu am găsit această locație. Încearcă să fii mai specific (ex: Calea Călărașilor).");
            }
        })
        .catch(err => console.error("Eroare Nominatim:", err));
}

document.getElementById("inputCautaStrada").addEventListener("keypress", function(event) {
    if (event.key === "Enter") {
        event.preventDefault();
        cautaStrada();
    }
});
</script>

<?php include 'footer.php'; ?>