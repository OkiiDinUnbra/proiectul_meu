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



<section class="stats-dashboard">
    <h1>📈 Panou de Control Administrator</h1>

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

<?php include 'footer.php'; ?>