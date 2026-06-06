<?php
session_start();

// Protecție: Doar adminii pot accesa această pagină
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    header("Location: index.php");
    exit();
}

require_once 'db_connect.php';
$page_title = "Panou Tichete Suport | Admin";
include 'header.php';

// Logica pentru a marca un tichet ca 'rezolvat'
if (isset($_GET['rezolva']) && is_numeric($_GET['rezolva'])) {
    $id_tichet = intval($_GET['rezolva']);
    $stmt_upd = $conn->prepare("UPDATE tichete_suport SET status = 'rezolvat' WHERE id = ?");
    $stmt_upd->bind_param("i", $id_tichet);
    $stmt_upd->execute();
    $stmt_upd->close();
    
    // Refresh rapid pentru a curăța URL-ul
    header("Location: admin_tichete.php");
    exit();
}

// Preluăm toate tichetele, ordonate astfel încât cele 'deschise' să fie primele
$tichete = [];
$res = $conn->query("SELECT * FROM tichete_suport ORDER BY CASE WHEN status = 'deschis' THEN 1 ELSE 2 END, data_creare DESC");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $tichete[] = $row;
    }
}
?>

<style>
    .admin-container { padding: 120px 20px 60px; max-width: 1000px; margin: auto; min-height: 70vh; }
    .header-admin { display: flex; align-items: center; justify-content: space-between; margin-bottom: 30px; border-bottom: 2px solid var(--border-color); padding-bottom: 20px; }
    
    .grid-tichete { display: grid; gap: 20px; }
    
    .tichet-card {
        background: var(--card-bg); border-radius: 12px; box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        padding: 20px; border-left: 5px solid #ccc; display: flex; flex-direction: column;
        border-top: 1px solid var(--border-color); border-right: 1px solid var(--border-color); border-bottom: 1px solid var(--border-color);
    }
    
    .tichet-deschis { border-left-color: #dc3545; } /* Roșu pentru cele nerezolvate */
    .tichet-rezolvat { border-left-color: #28a745; opacity: 0.7; } /* Verde pentru cele rezolvate */
    
    .tichet-header { display: flex; justify-content: space-between; align-items: center; border-bottom: 1px dashed var(--border-color); padding-bottom: 10px; margin-bottom: 15px; }
    .tichet-user { font-weight: bold; color: var(--text-main); font-size: 16px; }
    .tichet-date { color: var(--text-light); font-size: 13px; }
    
    .tichet-mesaj { font-size: 15px; color: var(--text-main); line-height: 1.6; margin-bottom: 20px; background: rgba(0,0,0,0.02); padding: 15px; border-radius: 8px; font-style: italic; }
    
    .badge-status { padding: 5px 10px; border-radius: 20px; font-size: 11px; font-weight: bold; color: white; letter-spacing: 1px; }
    .badge-deschis { background: #dc3545; }
    .badge-rezolvat { background: #28a745; }
</style>

<section class="admin-container">
    <div class="header-admin">
        <div>
            <h2 style="color: var(--text-main); margin-bottom: 5px;">🛠️ Tichete de Suport</h2>
            <p style="color: var(--text-light); margin: 0;">Gestionează mesajele primite de la utilizatori prin intermediul asistentului virtual.</p>
        </div>
    </div>

    <?php if (empty($tichete)): ?>
        <div style="text-align: center; padding: 50px; background: var(--card-bg); border-radius: 15px; border: 1px solid var(--border-color);">
            <h3 style="color: var(--text-main);">Nu există tichete înregistrate.</h3>
            <p style="color: var(--text-light);">Sistemul este la zi!</p>
        </div>
    <?php else: ?>
        <div class="grid-tichete">
            <?php foreach ($tichete as $tichet): 
                $e_deschis = ($tichet['status'] === 'deschis');
            ?>
                <div class="tichet-card <?= $e_deschis ? 'tichet-deschis' : 'tichet-rezolvat' ?>">
                    <div class="tichet-header">
                        <div class="tichet-user">
                            👤 <?= htmlspecialchars($tichet['nume_vizitator']) ?> 
                            <span style="font-size: 12px; color: var(--text-light); font-weight: normal;">(ID User: <?= $tichet['user_id'] ? $tichet['user_id'] : 'Neautentificat' ?>)</span>
                        </div>
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <span class="tichet-date">📅 <?= date('d/m/Y H:i', strtotime($tichet['data_creare'])) ?></span>
                            <span class="badge-status <?= $e_deschis ? 'badge-deschis' : 'badge-rezolvat' ?>">
                                <?= strtoupper($tichet['status']) ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="tichet-mesaj">
                        "<?= nl2br(htmlspecialchars($tichet['mesaj'])) ?>"
                    </div>
                    
                    <div style="text-align: right;">
                        <?php if ($e_deschis): ?>
                            <a href="admin_tichete.php?rezolva=<?= $tichet['id'] ?>" class="btn-submit-modern" style="background: #28a745; border: none; padding: 8px 15px; font-size: 13px; text-decoration: none; display: inline-block;">✅ Marchează ca Rezolvat</a>
                        <?php else: ?>
                            <span style="color: var(--text-light); font-size: 13px;">✔ Acest tichet a fost soluționat.</span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<?php include 'footer.php'; ?>