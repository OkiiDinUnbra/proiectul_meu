<?php
session_start();
$page_title = "Profilul Meu | Descoperă Brăila";
include 'header.php';
require_once 'db_connect.php';

// Redirecționăm dacă nu e logat
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Preluăm toate biletele utilizatorului
$bilete = [];
$stmt = $conn->prepare("SELECT cod_qr_unic, data_achizitie, data_expirare, tip_bilet, id_eveniment FROM bilete_achizitionate WHERE user_id = ? ORDER BY data_achizitie DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    // Verificăm dacă este bilet de eveniment (vechi sau nou - fizic/online)
    if (in_array($row['tip_bilet'], ['eveniment', 'fizic', 'online']) && $row['id_eveniment']) {
        $stmt_ev = $conn->prepare("SELECT titlu FROM evenimente WHERE id = ?");
        $stmt_ev->bind_param("i", $row['id_eveniment']);
        $stmt_ev->execute();
        $result_ev = $stmt_ev->get_result();
        if ($result_ev->num_rows > 0) {
            $ev = $result_ev->fetch_assoc();
            $row['eveniment_titlu'] = $ev['titlu'];
        }
        $stmt_ev->close();
    }
    $bilete[] = $row;
}
$stmt->close();
?>

<!-- Importăm librăria HTML2PDF -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>

<style>
    .profil-container { padding: 120px 20px 60px; max-width: 1000px; margin: auto; min-height: 70vh; }
    .header-profil { display: flex; align-items: center; justify-content: space-between; margin-bottom: 30px; border-bottom: 2px solid var(--border-color); padding-bottom: 20px;}
    
    .grid-bilete { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 25px; }
    
    .card-bilet-wrapper {
        background: var(--card-bg);
        border-radius: 15px;
        box-shadow: 0 5px 20px rgba(0,0,0,0.08);
        padding: 20px;
        position: relative;
        border-left: 5px solid #ccc;
        display: flex;
        flex-direction: column;
        transition: transform 0.2s;
    }
    .card-bilet-wrapper:hover { transform: translateY(-5px); }
    
    .bilet-activ { border-left-color: #28a745; }
    .bilet-expirat { border-left-color: #dc3545; opacity: 0.85;}
    
    /* Zona care va fi exportată efectiv în PDF */
    .bilet-print-area {
        background: #fff; /* Fundal alb forțat pentru PDF */
        padding: 15px;
        border-radius: 8px;
        color: #111; /* Text negru pentru claritate la print */
    }

    .badge-status {
        position: absolute; top: 15px; right: 15px;
        padding: 5px 12px; border-radius: 20px;
        font-size: 11px; font-weight: bold; color: white; letter-spacing: 1px;
    }
    .badge-activ { background: #28a745; }
    .badge-expirat { background: #dc3545; }
    
    .bilet-cod { font-family: 'Courier New', monospace; font-size: 17px; color: #333; font-weight: bold; margin: 15px 0; background: #f4f4f4; padding: 5px 10px; border-radius: 6px; display: inline-block;}
    .bilet-detalii p { margin: 6px 0; font-size: 14px; color: #555; border-bottom: 1px solid #f0f0f0; padding-bottom: 4px;}
    .bilet-detalii p:last-child { border-bottom: none; }
</style>

<section class="profil-container">
    <div class="header-profil">
        <h2 style="color: var(--text-main);">👋 Salut, <?= htmlspecialchars($_SESSION['nume']) ?>!</h2>
        <p style="color: var(--text-light);">Aici este istoricul biletelor tale</p>
    </div>

    <?php if (empty($bilete)): ?>
        <div style="text-align: center; padding: 50px; background: var(--card-bg); border-radius: 15px; border: 1px solid var(--border-color);">
            <h3 style="color: var(--text-main);">Nu ai niciun bilet achiziționat momentan.</h3>
            <p style="color: var(--text-light); margin-bottom: 20px;">Descoperă evenimentele și bucură-te de facilitățile orașului Brăila!</p>
            <div style="display: flex; gap: 15px; justify-content: center;">
                <a href="transport.php" class="btn-submit-modern" style="text-decoration: none; padding: 10px 20px; width: auto; margin: 0;">🚌 Transport</a>
                <a href="evenimente.php" class="btn-submit-modern" style="text-decoration: none; padding: 10px 20px; width: auto; margin: 0; background: var(--link-color);">🎫 Evenimente</a>
            </div>
        </div>
    <?php else: ?>
        <div class="grid-bilete">
            <?php foreach ($bilete as $bilet): 
                $tip_bilet = isset($bilet['tip_bilet']) ? $bilet['tip_bilet'] : 'bus';
                $este_activ = true;
                $data_expirare = null;
                
                if ($bilet['data_expirare'] !== null) {
                    $data_expirare = strtotime($bilet['data_expirare']);
                    $timp_curent = time();
                    $este_activ = ($data_expirare > $timp_curent);
                }
            ?>
                <div class="card-bilet-wrapper <?= $este_activ ? 'bilet-activ' : 'bilet-expirat' ?>">
                    <span class="badge-status <?= $este_activ ? 'badge-activ' : 'badge-expirat' ?>">
                        <?= $este_activ ? 'ACTIV' : 'EXPIRAT' ?>
                    </span>
                    
                    <!-- ZONA PENTRU PDF -->
                    <div id="bilet_export_<?= $bilet['cod_qr_unic'] ?>" class="bilet-print-area">
                        <?php if (in_array($tip_bilet, ['eveniment', 'fizic', 'online'])): ?>
                            <h4 style="margin: 0; color: #0056b3; font-size: 18px;">
                                🎫 <?= isset($bilet['eveniment_titlu']) ? htmlspecialchars($bilet['eveniment_titlu']) : 'Bilet Eveniment' ?>
                                <?= ($tip_bilet === 'online') ? ' <span style="color:#dc3545; font-size:14px;">[LIVE ONLINE]</span>' : '' ?>
                            </h4>
                        <?php else: ?>
                            <h4 style="margin: 0; color: #0056b3; font-size: 18px;">🚌 Bilet Braicar (60 Min)</h4>
                        <?php endif; ?>
                        
                        <div class="bilet-cod"><?= $bilet['cod_qr_unic'] ?></div>
                        
                        <div class="bilet-detalii">
                            <p><strong>Călător:</strong> <?= htmlspecialchars($_SESSION['nume']) ?></p>
                            <p><strong>Achiziționat:</strong> <?= date('d/m/Y H:i', strtotime($bilet['data_achizitie'])) ?></p>
                            <?php if ($tip_bilet === 'bus'): ?>
                                <p><strong>Expiră la:</strong> <?= date('d/m/Y H:i', $data_expirare) ?></p>
                            <?php else: ?>
                                <p><strong>Valabilitate:</strong> <?= $bilet['data_expirare'] ? 'până la ' . date('d/m/Y', $data_expirare) : 'Valabil 1 intrare' ?></p>
                            <?php endif; ?>
                        </div>

                        <?php if ($tip_bilet === 'online'): ?>
                            <div style="margin-top: 15px; padding: 10px; background: rgba(0, 123, 255, 0.05); border: 2px dashed #0056b3; border-radius: 8px; text-align: center;">
                                <p style="color: #0056b3; font-weight: bold; margin: 0; font-size: 13px;">🔴 Acces Exclusiv Virtual</p>
                                <p style="color: #666; font-size: 11px; margin: 5px 0 0 0;">Nu necesită scanare QR.</p>
                            </div>
                        <?php else: ?>
                            <?php if ($este_activ): ?>
                                <div style="margin-top: 15px; border-top: 2px dashed #ddd; padding-top: 15px; text-align: center;">
                                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=<?= urlencode($bilet['cod_qr_unic']) ?>" alt="QR" style="width: 100px; height: 100px;">
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                    <!-- END ZONA PDF -->

                    <!-- BUTOANE DE ACȚIUNE (Nu apar în PDF) -->
                    <div style="margin-top: auto; padding-top: 15px; display: flex; flex-direction: column; gap: 10px;">
                        
                        <?php if ($tip_bilet === 'online'): ?>
                            <a href="evenimentextins.php?id=<?= $bilet['id_eveniment'] ?>" class="btn-submit-modern" style="text-decoration: none; padding: 10px; font-size: 14px; text-align: center; margin: 0; background: var(--link-color);">▶️ Intră în Sala Virtuală</a>
                        <?php endif; ?>
                        
                        <button onclick="descarcaBiletPDF('bilet_export_<?= $bilet['cod_qr_unic'] ?>', '<?= $bilet['cod_qr_unic'] ?>')" class="btn-submit-modern" style="background: #dc3545; padding: 10px; font-size: 14px; margin: 0; border: none;">📄 Descarcă PDF</button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<script>
function descarcaBiletPDF(elementId, codUnic) {
    var element = document.getElementById(elementId);
    
    // Configurăm opțiunile pentru o calitate excelentă
    var opt = {
        margin:       10,
        filename:     'Bilet_DescoperaBraila_' + codUnic + '.pdf',
        image:        { type: 'jpeg', quality: 0.98 },
        html2canvas:  { scale: 2, useCORS: true },
        jsPDF:        { unit: 'mm', format: 'a5', orientation: 'portrait' } 
    };
    
    // Adăugăm un mic contur temporar doar pentru aspectul din interiorul PDF-ului
    element.style.border = '2px solid #ddd';
    element.style.boxShadow = 'none';

    html2pdf().set(opt).from(element).save().then(() => {
        // Restaurăm stilul după descărcare
        element.style.border = 'none';
    });
}
</script>

<?php include 'footer.php'; ?>