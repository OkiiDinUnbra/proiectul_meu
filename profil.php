<?php
session_start();
$page_title = "Profilul Meu | Descoperă Brăila";
include 'header.php';

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
    // Dacă este bilet de eveniment, preluam detalii suplimentare
    if ($row['tip_bilet'] === 'eveniment' && $row['id_eveniment']) {
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

<style>
    .profil-container { padding: 120px 20px 60px; max-width: 900px; margin: auto; min-height: 70vh; }
    .header-profil { display: flex; align-items: center; justify-content: space-between; margin-bottom: 30px; border-bottom: 2px solid #eee; padding-bottom: 20px;}
    
    .grid-bilete { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; }
    
    .card-bilet {
        background: #fff;
        border-radius: 12px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.08);
        padding: 20px;
        position: relative;
        overflow: hidden;
        border-left: 5px solid #ccc;
        transition: transform 0.2s;
    }
    .card-bilet:hover { transform: translateY(-5px); }
    
    .bilet-activ { border-left-color: #28a745; }
    .bilet-expirat { border-left-color: #dc3545; opacity: 0.8;}
    
    .badge-status {
        position: absolute; top: 15px; right: 15px;
        padding: 5px 10px; border-radius: 20px;
        font-size: 12px; font-weight: bold; color: white;
    }
    .badge-activ { background: #28a745; }
    .badge-expirat { background: #dc3545; }
    
    .bilet-cod { font-family: monospace; font-size: 16px; color: #333; font-weight: bold; margin: 15px 0;}
    .bilet-detalii p { margin: 5px 0; font-size: 14px; color: #666; }
</style>

<section class="profil-container">
    <div class="header-profil">
        <h2>👋 Salut, <?= htmlspecialchars($_SESSION['nume']) ?>!</h2>
        <p>Aici este istoricul biletelor tale</p>
    </div>

    <?php if (empty($bilete)): ?>
        <div style="text-align: center; padding: 50px; background: #f9f9f9; border-radius: 15px;">
            <h3>Nu ai niciun bilet achiziționat momentan.</h3>
            <p style="color: #666; margin-bottom: 20px;">Cumpără bilete pentru transport și evenimente din Brăila!</p>
            <div style="display: flex; gap: 15px; justify-content: center;">
                <a href="transport.php" class="btn-submit-modern" style="text-decoration: none; padding: 10px 20px;">Transport</a>
                <a href="evenimente.php" class="btn-submit-modern" style="text-decoration: none; padding: 10px 20px;">Evenimente</a>
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
                <div class="card-bilet <?= $este_activ ? 'bilet-activ' : 'bilet-expirat' ?>">
                    <span class="badge-status <?= $este_activ ? 'badge-activ' : 'badge-expirat' ?>">
                        <?= $este_activ ? 'ACTIV' : 'EXPIRAT' ?>
                    </span>
                    
                    <?php if ($tip_bilet === 'eveniment'): ?>
                        <h4 style="margin: 0; color: #0056b3;">🎫 <?= isset($bilet['eveniment_titlu']) ? htmlspecialchars($bilet['eveniment_titlu']) : 'Bilet Eveniment' ?></h4>
                    <?php else: ?>
                        <h4 style="margin: 0; color: #0056b3;">🚌 Bilet Braicar (60 Min)</h4>
                    <?php endif; ?>
                    <div class="bilet-cod"><?= $bilet['cod_qr_unic'] ?></div>
                    
                    <div class="bilet-detalii">
                        <p><strong>Achiziționat:</strong> <?= date('d/m/Y H:i', strtotime($bilet['data_achizitie'])) ?></p>
                        <?php if ($tip_bilet === 'bus'): ?>
                            <p><strong>Expiră la:</strong> <?= date('d/m/Y H:i', $data_expirare) ?></p>
                        <?php else: ?>
                            <p><strong>Valabil:</strong> <?= $bilet['data_expirare'] ? 'până la ' . date('d/m/Y', $data_expirare) : 'Nelimitat (până la validare)' ?></p>
                        <?php endif; ?>
                    </div>

                    <?php if ($este_activ): ?>
                        <div style="margin-top: 15px; border-top: 1px dashed #eee; padding-top: 10px; text-align: center;">
                            <img src="https://api.qrserver.com/v1/create-qr-code/?size=100x100&data=<?= urlencode($bilet['cod_qr_unic']) ?>" alt="QR" style="width: 80px; height: 80px;">
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<?php include 'footer.php'; ?>