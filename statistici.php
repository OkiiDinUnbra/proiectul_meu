<?php
$page_title = "Statistici Admin | Descoperă Brăila";

include 'header.php';
if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'admin') {
    header("Location: index.php");
    exit();
}

// 1. STATISTICI UTILIZATORI
$res_useri = $conn->query("SELECT COUNT(*) as total FROM utilizatori");
$total_useri = $res_useri->fetch_assoc()['total'];

$res_useri_luna = $conn->query("SELECT COUNT(*) as total FROM utilizatori WHERE MONTH(data_creare) = MONTH(CURRENT_DATE()) AND YEAR(data_creare) = YEAR(CURRENT_DATE())");
$useri_luna = $res_useri_luna->fetch_assoc()['total'];

$res_news = $conn->query("SELECT COUNT(*) as total FROM utilizatori WHERE doreste_newsletter = 1");
$total_newsletter = $res_news->fetch_assoc()['total'];

// 2. STATISTICI EVENIMENTE
$res_ev = $conn->query("SELECT COUNT(*) as total FROM evenimente");
$total_ev = $res_ev->fetch_assoc()['total'];

$res_ev_luna = $conn->query("SELECT COUNT(*) as total FROM evenimente WHERE MONTH(data_eveniment) = MONTH(CURRENT_DATE())");
$ev_luna = $res_ev_luna->fetch_assoc()['total'];

// Top 5 Cele mai populare evenimente
$top_evenimente = [];
$res_top = $conn->query("SELECT titlu, vizualizari FROM evenimente ORDER BY vizualizari DESC LIMIT 5");
while($row = $res_top->fetch_assoc()) { $top_evenimente[] = $row; }

// 3. STATISTICI TRANSPORTURI (Bilete Bus)
$res_bilete_bus = $conn->query("SELECT COUNT(*) as total FROM bilete_achizitionate WHERE tip_bilet = 'bus' OR tip_bilet IS NULL");
$total_bilete_bus = $res_bilete_bus->fetch_assoc()['total'] ?? 0;
$venituri_bus = $total_bilete_bus * 2.50;

// 4. STATISTICI BILETE EVENIMENTE
$res_bilete_ev = $conn->query("SELECT COUNT(*) as total FROM bilete_achizitionate WHERE tip_bilet = 'eveniment'");
$total_bilete_ev = $res_bilete_ev->fetch_assoc()['total'] ?? 0;

$res_venit_ev = $conn->query("SELECT SUM(e.pret) as venit FROM bilete_achizitionate b JOIN evenimente e ON b.id_eveniment = e.id WHERE b.tip_bilet = 'eveniment'");
$venituri_ev = $res_venit_ev->fetch_assoc()['venit'] ?? 0;

?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>

<style>
    .stats-dashboard {
        padding: 140px 20px 60px;
        max-width: 1100px;
        margin: auto;
        min-height: 80vh;
        color: var(--text-main);
    }
    
    .stats-header-container {
        display: flex; 
        justify-content: space-between; 
        align-items: center; 
        margin-bottom: 40px; 
        flex-wrap: wrap; 
        gap: 15px;
    }

    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
        gap: 25px;
        margin-bottom: 50px;
    }

    /* Redesign Carduri Statistici */
    .stat-card-modern {
        background: var(--card-bg);
        border: 1px solid var(--border-color);
        border-left-width: 3px; /* Linia de 3px conform mockup */
        border-radius: 12px;
        padding: 24px;
        backdrop-filter: blur(12px);
        -webkit-backdrop-filter: blur(12px);
        transition: transform 0.3s, box-shadow 0.3s;
        display: flex;
        flex-direction: column;
    }

    .stat-card-modern:hover { 
        transform: translateY(-5px); 
        box-shadow: var(--shadow-medium);
    }

    .stat-card-modern.card-blue { border-left-color: var(--card-blue); }
    .stat-card-modern.card-purple { border-left-color: var(--card-purple); }
    .stat-card-modern.card-green { border-left-color: var(--card-green); }
    .stat-card-modern.card-pink { border-left-color: var(--card-pink); }
    .stat-card-modern.card-orange { border-left-color: var(--card-orange); }

    .stat-top {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 12px;
    }

    .stat-icon { font-size: 24px; }
    .stat-value { font-size: 32px; font-weight: 700; color: var(--text-main); line-height: 1; }
    
    .card-blue .stat-value { color: var(--card-blue); }
    .card-purple .stat-value { color: var(--card-purple); }
    .card-green .stat-value { color: var(--card-green); }
    .card-pink .stat-value { color: var(--card-pink); }
    .card-orange .stat-value { color: var(--card-orange); }

    .stat-title { font-size: 14px; color: var(--text-light); font-weight: 500; margin-bottom: 4px; }
    .stat-subtitle { font-size: 12px; color: var(--text-lighter); }

    /* Tabel Top Evenimente */
    .top-events-table {
        width: 100%;
        background: var(--card-bg);
        border-radius: 12px;
        border: 1px solid var(--border-color);
        border-collapse: collapse;
        overflow: hidden;
    }

    .top-events-table th {
        background: rgba(255, 255, 255, 0.05);
        color: var(--text-light);
        padding: 16px;
        text-align: left;
        font-weight: 600;
        font-size: 14px;
        text-transform: uppercase;
        border-bottom: 1px solid var(--border-color);
    }

    .top-events-table td {
        padding: 16px;
        border-bottom: 1px solid var(--border-light);
        font-size: 15px;
    }

    .top-events-table tr:hover { background: rgba(255,255,255,0.02); }

    .badge-views {
        background: rgba(91, 176, 255, 0.1);
        color: var(--link-color);
        padding: 6px 12px;
        border-radius: 20px;
        font-weight: 600;
        font-size: 13px;
    }
</style>

<section class="stats-dashboard" id="raportPDF">
    <div class="stats-header-container">
        <h1 style="margin: 0; font-size: 32px; font-weight: 700;">Panou Statistici</h1>
        
        <button onclick="genereazaPDF()" class="btn" style="background: var(--accent-delete); color: white; display: flex; align-items: center; gap: 8px; border: none; cursor: pointer; border-radius: 8px; font-weight: 600;" id="btnDownloadPDF">
            📄 Descarcă Raport PDF
        </button>
    </div>

    <div class="stats-grid">
        <div class="stat-card-modern card-blue">
            <div class="stat-top">
                <div class="stat-icon">👥</div>
                <div class="stat-value"><?= $total_useri ?></div>
            </div>
            <div class="stat-title">Conturi Create</div>
            <div class="stat-subtitle">↗️ <?= $useri_luna ?> înregistrați luna aceasta</div>
        </div>

        <div class="stat-card-modern card-purple">
            <div class="stat-top">
                <div class="stat-icon">📅</div>
                <div class="stat-value"><?= $total_ev ?></div>
            </div>
            <div class="stat-title">Evenimente Totale</div>
            <div class="stat-subtitle">📌 <?= $ev_luna ?> evenimente luna curentă</div>
        </div>

        <div class="stat-card-modern card-green">
            <div class="stat-top">
                <div class="stat-icon">🚍</div>
                <div class="stat-value"><?= $total_bilete_bus ?></div>
            </div>
            <div class="stat-title">Bilete Transport</div>
            <div class="stat-subtitle">💰 Venituri: <?= number_format($venituri_bus, 2) ?> RON</div>
        </div>

        <div class="stat-card-modern card-pink">
            <div class="stat-top">
                <div class="stat-icon">🎟️</div>
                <div class="stat-value"><?= $total_bilete_ev ?></div>
            </div>
            <div class="stat-title">Bilete Eveniment</div>
            <div class="stat-subtitle">💰 Venituri: <?= number_format($venituri_ev, 2) ?> RON</div>
        </div>

        <div class="stat-card-modern card-orange">
            <div class="stat-top">
                <div class="stat-icon">✉️</div>
                <div class="stat-value"><?= $total_newsletter ?></div>
            </div>
            <div class="stat-title">Abonați Newsletter</div>
            <div class="stat-subtitle">Utilizatori activi pe email</div>
        </div>
    </div>

    <h2 style="margin: 40px 0 20px; font-size: 22px; font-weight: 600;">🏆 Top 5 Evenimente Vizualizate</h2>
    
    <table class="top-events-table">
        <thead>
            <tr>
                <th style="width: 50px;">#</th>
                <th>Nume Eveniment</th>
                <th style="text-align: right;">Vizualizări</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($top_evenimente)): ?>
                <tr><td colspan="3" style="text-align:center; color: var(--text-light);">Nu există date suficiente încă.</td></tr>
            <?php else: ?>
                <?php foreach ($top_evenimente as $index => $ev): ?>
                    <tr>
                        <td><strong><?= $index + 1 ?></strong></td>
                        <td style="font-weight: 500;"><?= htmlspecialchars($ev['titlu']) ?></td>
                        <td style="text-align: right;"><span class="badge-views">👁️ <?= $ev['vizualizari'] ?> vizualizări</span></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</section>

<script>
function genereazaPDF() {
    const btn = document.getElementById('btnDownloadPDF');
    btn.style.display = 'none';
    const element = document.getElementById('raportPDF');
    
    const optiuni = {
        margin:       10, 
        filename:     'Raport_Statistici_Braila.pdf', 
        image:        { type: 'jpeg', quality: 0.98 }, 
        html2canvas:  { scale: 2, useCORS: true }, 
        jsPDF:        { unit: 'mm', format: 'a4', orientation: 'landscape' } 
    };

    html2pdf().set(optiuni).from(element).save().then(() => {
        btn.style.display = 'flex';
    });
}
</script>

<?php include 'footer.php'; ?>