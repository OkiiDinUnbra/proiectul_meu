<?php
$page_title = "Statistici Admin | Descoperă Brăila";

include 'header.php';
if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'admin') {
    header("Location: index.php");
    exit();
}

// Verificăm dacă utilizatorul este ADMIN
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    echo "<section style='margin-top: 150px; text-align: center; min-height: 50vh;'>
            <h2>Acces Interzis!</h2><p>Doar administratorii au acces la această pagină.</p>
            <a href='index.php' class='btn'>Înapoi acasă</a>
          </section>";
    include 'footer.php';
    exit;
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
$venituri_bus = $total_bilete_bus * 2.50; // Prețul unui bilet autobuz

// 4. STATISTICI BILETE EVENIMENTE
$res_bilete_ev = $conn->query("SELECT COUNT(*) as total FROM bilete_achizitionate WHERE tip_bilet = 'eveniment'");
$total_bilete_ev = $res_bilete_ev->fetch_assoc()['total'] ?? 0;

$res_venit_ev = $conn->query("SELECT SUM(e.pret) as venit FROM bilete_achizitionate b JOIN evenimente e ON b.id_eveniment = e.id WHERE b.tip_bilet = 'eveniment'");
$venituri_ev = $res_venit_ev->fetch_assoc()['venit'] ?? 0;

?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>

<section class="stats-dashboard" id="raportPDF">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; flex-wrap: wrap; gap: 15px;">
        <h1 style="margin: 0; border-bottom: 3px solid #0056b3; padding-bottom: 10px;">📈 Panou de Control Administrator</h1>
        
        <button onclick="genereazaPDF()" class="btn" style="background-color: #dc3545; color: white; display: flex; align-items: center; gap: 10px; border: none; cursor: pointer;" id="btnDownloadPDF">
            <svg width="20" height="20" fill="currentColor" viewBox="0 0 16 16"><path d="M14 14V4.5L9.5 0H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2zM9.5 3A1.5 1.5 0 0 0 11 4.5h2V14a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1h5.5v2z"/><path d="M4.603 14.087a.81.81 0 0 1-.438-.42c-.195-.388-.13-.776.08-1.102.198-.307.526-.568.897-.787a7.68 7.68 0 0 1 1.482-.645 19.697 19.697 0 0 0 1.062-2.227 7.269 7.269 0 0 1-.43-1.295c-.086-.4-.119-.796-.046-1.136.075-.354.274-.672.65-.823.192-.077.4-.12.602-.077a.7.7 0 0 1 .477.365c.088.164.12.356.127.538.007.188-.012.396-.047.614-.084.51-.27 1.134-.52 1.794a10.954 10.954 0 0 0 .98 1.686 5.753 5.753 0 0 1 1.334.05c.364.066.734.195.96.465.12.144.193.32.2.518.007.192-.047.382-.138.563a1.04 1.04 0 0 1-.354.416.856.856 0 0 1-.51.138c-.331-.014-.654-.196-.933-.417a5.712 5.712 0 0 1-.911-.95 11.651 11.651 0 0 0-1.997.406 11.307 11.307 0 0 1-1.02 1.51c-.292.35-.609.656-.927.787a.793.793 0 0 1-.58.029zm1.379-1.901c-.166.076-.32.156-.459.238-.328.194-.541.383-.647.547-.094.145-.096.25-.04.361.01.022.02.036.026.044a.266.266 0 0 0 .035-.012c.137-.056.355-.235.635-.572a8.18 8.18 0 0 0 .45-.606zm1.64-1.33a12.71 12.71 0 0 1 1.01-.193 11.744 11.744 0 0 1-.51-.858 20.801 20.801 0 0 1-.5 1.05zm2.446.45c.15.163.296.3.435.41.24.19.407.253.498.256a.107.107 0 0 0 .07-.015.307.307 0 0 0 .094-.125.436.436 0 0 0 .059-.2.095.095 0 0 0-.026-.063c-.052-.062-.2-.152-.518-.209a3.876 3.876 0 0 0-.612-.053zM8.078 7.8a6.7 6.7 0 0 0 .2-.828c.031-.188.043-.343.038-.465a.613.613 0 0 0-.032-.198.517.517 0 0 0-.145.04c-.087.035-.158.106-.196.283-.04.192-.03.469.046.822.024.111.054.227.09.346z"/></svg>
            Descarcă Raport PDF
        </button>
    </div>

    <div class="stats-grid">
        <div class="stat-card card-blue">
            <div class="stat-icon">👥</div>
            <div class="stat-value"><?= $total_useri ?></div>
            <div class="stat-title">Conturi Create</div>
            <div class="stat-subtitle">↗️ <?= $useri_luna ?> înregistrați luna aceasta</div>
        </div>

        <div class="stat-card card-purple">
            <div class="stat-icon">📅</div>
            <div class="stat-value"><?= $total_ev ?></div>
            <div class="stat-title">Evenimente Totale</div>
            <div class="stat-subtitle">📌 <?= $ev_luna ?> evenimente în luna curentă</div>
        </div>

        <div class="stat-card card-green">
            <div class="stat-icon">🎫</div>
            <div class="stat-value"><?= $total_bilete_bus ?></div>
            <div class="stat-title">Bilete Transport</div>
            <div class="stat-subtitle">💰 Venituri: <?= number_format($venituri_bus, 2) ?> RON</div>
        </div>

        <div class="stat-card card-pink">
            <div class="stat-icon">🎟️</div>
            <div class="stat-value"><?= $total_bilete_ev ?></div>
            <div class="stat-title">Bilete Eveniment</div>
            <div class="stat-subtitle">💰 Venituri: <?= number_format($venituri_ev, 2) ?> RON</div>
        </div>

        <div class="stat-card card-orange">
            <div class="stat-icon">✉️</div>
            <div class="stat-value"><?= $total_newsletter ?></div>
            <div class="stat-title">Abonați Newsletter</div>
            <div class="stat-subtitle">Oameni interesați de noutăți</div>
        </div>
    </div>

    <h2 style="margin: 40px 0 20px; font-size: 24px;">🏆 Top 5 Cele mai vizualizate evenimente</h2>
    
    <table class="top-events-table">
        <thead>
            <tr>
                <th style="width: 50px;">#</th>
                <th>Nume Eveniment</th>
                <th style="text-align: right;">Vizualizări (Click-uri)</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($top_evenimente)): ?>
                <tr><td colspan="3" style="text-align:center;">Nu există date suficiente încă. Dă click pe câteva evenimente!</td></tr>
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
    // Ascundem butonul pentru a nu apărea în PDF
    const btn = document.getElementById('btnDownloadPDF');
    btn.style.display = 'none';

    // Setăm zona din pagină care va fi convertită
    const element = document.getElementById('raportPDF');
    
    // Setăm opțiunile de formatare pentru PDF
    const optiuni = {
        margin:       10, 
        filename:     'Raport_Statistici_Braila.pdf', 
        image:        { type: 'jpeg', quality: 0.98 }, 
        html2canvas:  { scale: 2, useCORS: true }, 
        jsPDF:        { unit: 'mm', format: 'a4', orientation: 'landscape' } 
    };

    // Apelăm funcția din librărie
    html2pdf().set(optiuni).from(element).save().then(() => {
        // Rearătăm butonul imediat după ce salvarea s-a terminat
        btn.style.display = 'flex';
    });
}
</script>

<?php include 'footer.php'; ?>