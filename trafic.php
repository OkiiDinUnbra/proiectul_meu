<?php
require_once 'db_connect.php';

$mesaj_raport = '';

// 1. Logica pentru adăugarea unui raport nou
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['adauga_raport'])) {
    $autor = isset($_SESSION['nume']) ? $_SESSION['nume'] : 'Vizitator';
    $tip = mysqli_real_escape_string($conn, $_POST['tip_problema']);
    $locatie = mysqli_real_escape_string($conn, $_POST['locatie']);
    $descriere = mysqli_real_escape_string($conn, $_POST['descriere']);
    
    if(!empty($locatie)) {
        $sql = "INSERT INTO rapoarte_trafic (autor, tip_problema, locatie, descriere) VALUES ('$autor', '$tip', '$locatie', '$descriere')";
        $conn->query($sql);
        $mesaj_raport = "✅ Raportul tău a fost trimis și apare acum pe radar!";
    }
}

// 2. Logica pentru confirmarea unui incident (AICI FACEM REDIRECȚIONAREA)
if (isset($_GET['confirma_id'])) {
    $id_raport = (int)$_GET['confirma_id'];
    $conn->query("UPDATE rapoarte_trafic SET confirmari = confirmari + 1 WHERE id = $id_raport");
    header("Location: trafic.php"); // Acum va funcționa perfect!
    exit();
}

// 3. Preluăm toate rapoartele din comunitate
$rapoarte = [];
$alerte_majore = [];
$res = $conn->query("SELECT * FROM rapoarte_trafic WHERE status = 'activ' ORDER BY confirmari DESC, data_raport DESC");
if ($res) { 
    while($row = $res->fetch_assoc()) { 
        $rapoarte[] = $row; 
        if ($row['confirmari'] >= 3) {
            $alerte_majore[] = $row; // Salvăm separat alertele grave
        }
    } 
}

// 4. Abia ACUM, după ce am terminat cu redirecționările, includem design-ul paginii!
$page_title = "Info Trafic Live | Descoperă Brăila";
include 'header.php';
?>

<style>
    .trafic-page { padding: 140px 20px 60px; max-width: 1200px; margin: 0 auto; color: white; }
    .trafic-header { text-align: center; margin-bottom: 40px; }
    .trafic-header h1 { color: #ffd700; font-size: 36px; margin-bottom: 10px; }
    
    /* Layout pentru hartă și rapoarte */
    .trafic-grid { display: flex; gap: 30px; flex-wrap: wrap; }
    .map-container { flex: 2; min-width: 300px; background: rgba(255,255,255,0.05); padding: 15px; border-radius: 20px; border: 1px solid rgba(255,255,255,0.1); }
    .reports-container { flex: 1; min-width: 300px; background: rgba(255,255,255,0.05); padding: 25px; border-radius: 20px; border: 1px solid rgba(255,255,255,0.1); }

    /* Buton și Formular Raportare */
    .btn-toggle-raport { background: #dc3545; color: white; border: none; padding: 15px; width: 100%; border-radius: 12px; font-size: 16px; font-weight: bold; cursor: pointer; transition: 0.3s; margin-bottom: 20px; box-shadow: 0 5px 15px rgba(220, 53, 69, 0.4); }
    .btn-toggle-raport:hover { background: #c82333; transform: translateY(-2px); }
    
    .form-raport-box { display: none; background: rgba(0,0,0,0.5); padding: 20px; border-radius: 15px; margin-bottom: 25px; border: 1px solid #dc3545; animation: fadeInDown 0.4s; }
    .form-raport-box.active { display: block; }
    
    .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 12px; border-radius: 8px; border: 1px solid rgba(255,255,255,0.2); background: rgba(255,255,255,0.1); color: white; margin-bottom: 15px; outline: none; font-family: 'Poppins', sans-serif; }
    .form-group input:focus, .form-group select:focus, .form-group textarea:focus { border-color: #ffd700; }
    .form-group select option { background: #111; color: white; }
    .btn-trimite { background: #28a745; color: white; border: none; padding: 12px; width: 100%; border-radius: 8px; font-weight: bold; cursor: pointer; }

    /* Carduri Rapoarte */
    .raport-card { background: rgba(0,0,0,0.4); padding: 15px; border-radius: 10px; margin-bottom: 15px; border-left: 4px solid #007bff; }
    .raport-card.critic { border-left: 4px solid #dc3545; background: rgba(220, 53, 69, 0.15); }
    .raport-titlu { font-weight: bold; color: #fff; font-size: 16px; margin-bottom: 5px; }
    .raport-detalii { font-size: 13px; color: #aaa; margin-bottom: 10px; }
    .btn-confirma { background: rgba(255,255,255,0.1); border: 1px solid #ffd700; color: #ffd700; text-decoration: none; padding: 5px 12px; border-radius: 5px; font-size: 12px; font-weight: bold; transition: 0.2s; display: inline-block; }
    .btn-confirma:hover { background: #ffd700; color: #000; }

    @keyframes fadeInDown { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
</style>

<div class="trafic-page">
    <div class="trafic-header">
        <h1>🚗 Info Trafic & Radar Brăila</h1>
        <p style="color: #aaa;">Harta live Waze și alertele raportate de comunitatea locală.</p>
    </div>

    <?php if (!empty($alerte_majore)): ?>
        <div style="margin-bottom: 25px; padding: 15px; background: rgba(220, 53, 69, 0.2); border: 1px solid #dc3545; border-radius: 12px;">
            <h3 style="color: #ff4d4d; margin-bottom: 10px;">🔥 STRĂZI BLOCATE / ALERTE MAJORE:</h3>
            <?php foreach($alerte_majore as $alerta): ?>
                <div style="font-weight: 600; color: #fff; margin-bottom: 5px;">
                    🚨 <?= htmlspecialchars($alerta['tip_problema']) ?> - <?= htmlspecialchars($alerta['locatie']) ?> (Confirmat de <?= $alerta['confirmari'] ?> persoane)
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="trafic-grid">
        
        <div class="map-container">
            <div style="background: rgba(0, 123, 255, 0.1); padding: 10px; border-radius: 8px; margin-bottom: 15px; color: #66b2ff; font-size: 14px; text-align: center; border: 1px solid rgba(0, 123, 255, 0.3);">
                ℹ️ Harta de mai jos afișează automat străzile aglomerate (linii roșii) și accidentele detectate de Waze.
            </div>
            <iframe 
                src="https://embed.waze.com/iframe?zoom=14&lat=45.2692&lon=27.9575&ct=livemap" 
                width="100%" 
                height="550" 
                style="border: none; border-radius: 12px;"
                allowfullscreen>
            </iframe>
        </div>

        <div class="reports-container">
            <?php if($mesaj_raport) echo "<div style='background:rgba(40,167,69,0.2); color:#28a745; padding:10px; border-radius:8px; margin-bottom:15px; text-align:center; font-weight:bold;'>$mesaj_raport</div>"; ?>
            
            <button class="btn-toggle-raport" onclick="toggleForm()">🚨 Raportează un incident</button>
            
            <div class="form-raport-box" id="boxRaportare">
                <h3 style="color: #ffd700; margin-bottom: 15px; font-size: 18px;">Adaugă o alertă nouă</h3>
                <form method="POST" action="">
                    <div class="form-group">
                        <select name="tip_problema" required>
                            <option value="" disabled selected>Alege tipul incidentului...</option>
                            <option value="Accident">💥 Accident Rutier</option>
                            <option value="Trafic Blocat">🚗 Trafic Blocat / Aglomerație</option>
                            <option value="Groapă Periculoasă">🕳️ Groapă Periculoasă</option>
                            <option value="Semafor Defect">🚥 Semafor Defect</option>
                            <option value="Filtru Poliție">👮 Filtru Poliție</option>
                        </select>
                        <input type="text" name="locatie" placeholder="Ex: Intersecția Călărașilor cu Bariera" required>
                        <textarea name="descriere" rows="2" placeholder="Detalii scurte (opțional)..."></textarea>
                        <button type="submit" name="adauga_raport" class="btn-trimite">Trimite Alerta</button>
                    </div>
                </form>
            </div>

            <h3 style="margin: 10px 0 15px; font-size: 20px; color: #fff; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 10px;">📡 Radar Live Brăila</h3>
            
            <div style="max-height: 400px; overflow-y: auto; padding-right: 10px;">
                <?php if(empty($rapoarte)): ?>
                    <p style="color: #aaa; text-align: center; margin-top: 20px;">Drumuri libere! Niciun incident raportat recent.</p>
                <?php else: ?>
                    <?php foreach($rapoarte as $r): ?>
                        <?php $eCritic = $r['confirmari'] >= 3; ?>
                        <div class="raport-card <?= $eCritic ? 'critic' : '' ?>">
                            <div class="raport-titlu">
                                <?= $eCritic ? '🔥' : '⚠️' ?> <?= htmlspecialchars($r['tip_problema']) ?>
                            </div>
                            <div class="raport-detalii">
                                📍 <strong><?= htmlspecialchars($r['locatie']) ?></strong><br>
                                👤 Raportat de: <?= htmlspecialchars($r['autor']) ?> • 🕒 <?= date('d.m H:i', strtotime($r['data_raport'])) ?><br>
                                <i>"<?= htmlspecialchars($r['descriere']) ?>"</i>
                            </div>
                            <a href="trafic.php?confirma_id=<?= $r['id'] ?>" class="btn-confirma">👍 Confirm incidentul (<?= $r['confirmari'] ?>)</a>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

    </div>
</div>

<script>
// Script pentru a ascunde / afișa formularul de raportare
function toggleForm() {
    var box = document.getElementById("boxRaportare");
    if (box.classList.contains("active")) {
        box.classList.remove("active");
    } else {
        box.classList.add("active");
    }
}
</script>

<?php include 'footer.php'; ?>