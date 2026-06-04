<?php
require_once 'db_connect.php';

if (!isset($_SESSION['confirmate'])) {
    $_SESSION['confirmate'] = [];
}

$mesaj_raport = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['adauga_raport'])) {
    // Verificăm dacă utilizatorul are cont înainte să procesăm formularul
    if (isset($_SESSION['user_id'])) {
        $autor = isset($_SESSION['nume']) ? $_SESSION['nume'] : 'Vizitator';
        $tip = mysqli_real_escape_string($conn, $_POST['tip_problema']);
        $locatie = mysqli_real_escape_string($conn, $_POST['locatie']);
        $descriere = mysqli_real_escape_string($conn, $_POST['descriere']);
        
        if(!empty($locatie)) {
            $sql = "INSERT INTO rapoarte_trafic (autor, tip_problema, locatie, descriere) VALUES ('$autor', '$tip', '$locatie', '$descriere')";
            $conn->query($sql);
            $mesaj_raport = "Raportul a fost trimis și apare pe radar.";
        }
    }
}

if (isset($_GET['confirma_id']) && isset($_SESSION['user_id'])) {
    $id_raport = (int)$_GET['confirma_id'];
    
    if (!in_array($id_raport, $_SESSION['confirmate'])) {
        // Incrementăm confirmarea
        $conn->query("UPDATE rapoarte_trafic SET confirmari = confirmari + 1 WHERE id = $id_raport");
        $_SESSION['confirmate'][] = $id_raport;

        // Verificăm dacă a ajuns fix la 5 confirmări pentru a trimite notificare
        $res_check = $conn->query("SELECT tip_problema, locatie, confirmari FROM rapoarte_trafic WHERE id = $id_raport");
        if ($res_check && $row = $res_check->fetch_assoc()) {
            if ($row['confirmari'] == 5) {
                require_once 'notificari.php';
                
                $subiect = "🚨 Alertă Trafic: " . $row['tip_problema'] . " în Brăila";
                $mesaj_html = "
                    <h3 style='color: #ff4d4d;'>Atenție în trafic!</h3>
                    <p>Comunitatea a confirmat un incident major:</p>
                    <ul>
                        <li><strong>Tip:</strong> {$row['tip_problema']}</li>
                        <li><strong>Locație:</strong> {$row['locatie']}</li>
                    </ul>
                    <p>Accesează harta live pe site pentru a vedea rute ocolitoare!</p>
                ";
                
                trimiteNotificareNewsletter($conn, $subiect, $mesaj_html);
            }
        }
    }
    
    header("Location: trafic.php"); 
    exit();
}

$rapoarte = [];
$alerte_majore = [];
$res = $conn->query("SELECT * FROM rapoarte_trafic WHERE status = 'activ' ORDER BY confirmari DESC, data_raport DESC");
if ($res) { 
    while($row = $res->fetch_assoc()) { 
        $rapoarte[] = $row; 
        if ($row['confirmari'] >= 3) {
            $alerte_majore[] = $row;
        }
    } 
}

$page_title = "Info Trafic Live | Descoperă Brăila";
include 'header.php';
?>

<style>
    .trafic-page { padding: 140px 20px 60px; max-width: 1200px; margin: 0 auto; color: var(--text-main); }
    .trafic-header { text-align: center; margin-bottom: 40px; }
    .trafic-header h1 { color: var(--text-main); font-size: 36px; margin-bottom: 10px; }
    
    .trafic-grid { display: flex; gap: 30px; flex-wrap: wrap; }
    .map-container { flex: 2; min-width: 300px; background: var(--card-bg); padding: 15px; border-radius: 16px; border: 1px solid var(--border-color); backdrop-filter: blur(10px); }
    .reports-container { flex: 1; min-width: 300px; background: transparent; }

    .btn-toggle-raport { background: var(--accent-delete); color: white; border: none; padding: 14px; width: 100%; border-radius: 12px; font-size: 16px; font-weight: bold; cursor: pointer; transition: 0.3s; margin-bottom: 20px; box-shadow: 0 4px 15px rgba(220,53,69,0.3); }
    .btn-toggle-raport:hover { opacity: 0.9; transform: translateY(-2px); }
    
    .btn-login-raport { background: transparent; color: var(--text-main); border: 1px solid var(--border-color); padding: 14px; width: 100%; border-radius: 12px; font-size: 16px; font-weight: bold; cursor: pointer; transition: 0.3s; margin-bottom: 20px; }
    .btn-login-raport:hover { background: rgba(255,255,255,0.1); }

    .form-raport-box { display: none; background: var(--card-bg); padding: 20px; border-radius: 15px; margin-bottom: 25px; border: 1px solid var(--accent-delete); color: var(--text-main); backdrop-filter: blur(10px); }
    .form-raport-box.active { display: block; animation: fadeInDown 0.3s; }
    
    .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 12px; border-radius: 8px; border: 1px solid var(--border-color); background: var(--input-bg); color: var(--text-main); margin-bottom: 15px; outline: none; }
    .btn-trimite { background: var(--accent-success); color: #000; border: none; padding: 12px; width: 100%; border-radius: 8px; font-weight: 700; cursor: pointer; }

    .raport-card-modern { 
        background: var(--card-bg); 
        border: 1px solid var(--border-color); 
        border-left-width: 3px; 
        border-radius: 12px; 
        padding: 16px; 
        margin-bottom: 16px; 
        display: flex; 
        justify-content: space-between; 
        align-items: flex-start; 
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
        transition: 0.2s;
    }
    .raport-card-modern:hover { transform: translateX(3px); }

    .raport-card-modern.urgenta-mare { border-left-color: var(--accent-delete); }
    .raport-card-modern.urgenta-medie { border-left-color: var(--accent-edit); }
    .raport-card-modern.urgenta-mica { border-left-color: var(--text-lighter); }

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
    .btn-confirma:hover { background: rgba(255,255,255,0.1); }
    .btn-confirma.disabled { opacity: 0.4; cursor: not-allowed; pointer-events: none; border-style: dashed; }

    @keyframes fadeInDown { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
</style>

<div class="trafic-page">
    <div class="trafic-header">
        <h1>🚦 Info Trafic & Radar Brăila</h1>
        <p style="color: var(--text-light);">Harta live Waze și alertele raportate de comunitate.</p>
    </div>

    <div class="trafic-grid">
        <div class="map-container">
            <iframe 
                id="wazeIframe"
                src="https://embed.waze.com/iframe?zoom=14&lat=45.2692&lon=27.9575&ct=livemap" 
                width="100%" 
                height="550" 
                style="border: none; border-radius: 12px;"
                allowfullscreen>
            </iframe>
        </div>

        <div class="reports-container">
            <?php if($mesaj_raport) echo "<div style='background:rgba(40,167,69,0.15); color:var(--accent-success); border: 1px solid var(--accent-success); padding:10px; border-radius:8px; margin-bottom:15px; text-align:center; font-weight:bold;'>$mesaj_raport</div>"; ?>
            
            <?php if(isset($_SESSION['user_id'])): ?>
                <button class="btn-toggle-raport" onclick="toggleForm()">🚨 Raportează un incident</button>
                
                <div class="form-raport-box" id="boxRaportare">
                    <h3 style="margin-bottom: 15px; font-size: 18px;">Adaugă o alertă nouă</h3>
                    <form method="POST" action="">
                        <div class="form-group">
                            <select name="tip_problema" required>
                                <option value="" disabled selected>Alege tipul incidentului...</option>
                                <option value="Accident">Accident Rutier</option>
                                <option value="Trafic Blocat">Trafic Blocat / Aglomerație</option>
                                <option value="Groapă Periculoasă">Groapă Periculoasă</option>
                                <option value="Semafor Defect">Semafor Defect</option>
                                <option value="Filtru Poliție">Filtru Poliție</option>
                            </select>
                            <input type="text" name="locatie" placeholder="Ex: Intersecția Călărașilor cu Bariera" required>
                            <textarea name="descriere" rows="2" placeholder="Detalii scurte (opțional)..."></textarea>
                            <button type="submit" name="adauga_raport" class="btn-trimite">Trimite Alerta</button>
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
                        $class_urgenta = 'urgenta-mica';
                        $badge_urgenta = 'mica';
                        $icon = '⚠️';
                        
                        if (in_array($tip, ['Accident', 'Trafic Blocat'])) {
                            $class_urgenta = 'urgenta-mare';
                            $badge_urgenta = 'mare';
                            $icon = '🚨';
                        } elseif (in_array($tip, ['Groapă Periculoasă', 'Semafor Defect', 'Filtru Poliție'])) {
                            $class_urgenta = 'urgenta-medie';
                            $badge_urgenta = 'medie';
                            $icon = '🚧';
                        }
                        
                        $dejaConfirmat = in_array($r['id'], $_SESSION['confirmate']);
                        $esteAutorul = (isset($_SESSION['nume']) && $r['autor'] === $_SESSION['nume']);
                        $esteLogat = isset($_SESSION['user_id']);
                    ?>
                        <div class="raport-card-modern <?= $class_urgenta ?>">
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
                                        <a href="trafic.php?confirma_id=<?= $r['id'] ?>" class="btn-confirma">+ Confirmă</a>
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
function toggleForm() {
    var box = document.getElementById("boxRaportare");
    box.classList.toggle("active");
}

document.addEventListener('DOMContentLoaded', function() {
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(function(position) {
            var lat = position.coords.latitude;
            var lon = position.coords.longitude;
            var wazeIframe = document.getElementById('wazeIframe');
            wazeIframe.src = "https://embed.waze.com/iframe?zoom=14&lat=" + lat + "&lon=" + lon + "&ct=livemap";
        });
    }
});
</script>

<?php include 'footer.php'; ?>