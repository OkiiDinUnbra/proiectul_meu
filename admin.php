<?php
session_start();
require_once 'db_connect.php';

// Securitate: Doar ADMINUL are acces
if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'admin') {
    header("Location: acasa.php");
    exit();
}

// =========================================================================
// 1. LOGICA PENTRU EXPORT EXCEL (Utilizatori)
// =========================================================================
if (isset($_GET['export']) && $_GET['export'] == 'excel') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=Raport_Utilizatori_Braila.csv');
    
    $output = fopen('php://output', 'w');
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM pentru Excel (diacritice)
    
    // Capul de tabel
    fputcsv($output, array('ID', 'Nume', 'Email', 'Telefon', 'Parolă (Hash)', 'Abonat Newsletter', 'Data Înregistrării', 'Rol'));
    
    $rows = $conn->query("SELECT id, nume, email, telefon, parola, doreste_newsletter, data_inregistrare, rol FROM utilizatori");
    
    while ($row = $rows->fetch_assoc()) {
        $newsletter = $row['doreste_newsletter'] ? 'Da' : 'Nu';
        $telefon = $row['telefon'] ? $row['telefon'] : 'Nespecificat';
        
        $rand_excel = [
            $row['id'],
            $row['nume'],
            $row['email'],
            $telefon,
            $row['parola'],
            $newsletter,
            $row['data_inregistrare'],
            $row['rol']
        ];
        
        fputcsv($output, $rand_excel);
    }
    fclose($output);
    exit();
}

// =========================================================================
// 2. PRELUARE DATE PENTRU STATISTICI ȘI GRAFICE
// =========================================================================

// Stat. Utilizatori
$total_useri = $conn->query("SELECT COUNT(*) as cnt FROM utilizatori")->fetch_assoc()['cnt'];
$total_newsletter = $conn->query("SELECT COUNT(*) as cnt FROM utilizatori WHERE doreste_newsletter = 1")->fetch_assoc()['cnt'];
$useri_cu_bilete = $conn->query("SELECT COUNT(DISTINCT user_id) as cnt FROM bilete_achizitionate")->fetch_assoc()['cnt'];

// Stat. Evenimente și Categorii
$total_ev = $conn->query("SELECT COUNT(*) as cnt FROM evenimente")->fetch_assoc()['cnt'];
$ev_culturale = $conn->query("SELECT COUNT(*) as cnt FROM evenimente WHERE categorie = 'cultural'")->fetch_assoc()['cnt'];
$ev_sportive = $conn->query("SELECT COUNT(*) as cnt FROM evenimente WHERE categorie = 'sportiv'")->fetch_assoc()['cnt'];

// Stat. Bilete & Venituri
$total_bilete_ev = $conn->query("SELECT COUNT(*) as cnt FROM bilete_achizitionate WHERE tip_bilet = 'eveniment'")->fetch_assoc()['cnt'];
$total_bilete_bus = $conn->query("SELECT COUNT(*) as cnt FROM bilete_achizitionate WHERE tip_bilet = 'bus' OR tip_bilet IS NULL")->fetch_assoc()['cnt'];
$venituri_ev = $conn->query("SELECT SUM(e.pret) as venit FROM bilete_achizitionate b JOIN evenimente e ON b.id_eveniment = e.id WHERE b.tip_bilet = 'eveniment'")->fetch_assoc()['venit'] ?? 0;
$venituri_bus = $total_bilete_bus * 2.50; // Preț fictiv 2.50 RON pt bus
$venit_total = $venituri_ev + $venituri_bus;

// Stat. Interacțiuni (Favorite, Recenzii, Trafic, Tichete)
$total_favorite = $conn->query("SELECT COUNT(*) as cnt FROM favorite")->fetch_assoc()['cnt'];
$recenzii_avg = round($conn->query("SELECT AVG(rating) as avg FROM recenzii_evenimente")->fetch_assoc()['avg'] ?? 0, 1);
$rapoarte_trafic = $conn->query("SELECT COUNT(*) as cnt FROM rapoarte_trafic")->fetch_assoc()['cnt'];
$rapoarte_active = $conn->query("SELECT COUNT(*) as cnt FROM rapoarte_trafic WHERE status='activ'")->fetch_assoc()['cnt'];
$tichete_total = $conn->query("SELECT COUNT(*) as cnt FROM tichete_suport")->fetch_assoc()['cnt'];
$tichete_deschise = $conn->query("SELECT COUNT(*) as cnt FROM tichete_suport WHERE status='deschis'")->fetch_assoc()['cnt'];

// Top 5 Evenimente
$top_evenimente = [];
$res_top = $conn->query("SELECT titlu, vizualizari FROM evenimente ORDER BY vizualizari DESC LIMIT 5");
while($row = $res_top->fetch_assoc()) { $top_evenimente[] = $row; }

$page_title = "Admin Tools | Descoperă Brăila";
include 'header.php';
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>

<style>
    body { background-color: #0A192F; color: #fff; overflow-x: hidden; }
    
    .admin-dashboard {
        padding: 120px 20px 60px;
        max-width: 1400px;
        margin: auto;
    }

    .admin-header {
        display: flex; justify-content: space-between; align-items: center;
        margin-bottom: 40px; flex-wrap: wrap; gap: 20px;
        border-bottom: 2px solid rgba(220, 53, 69, 0.3); padding-bottom: 20px;
    }

    .admin-header h1 { font-size: 36px; font-weight: 800; margin: 0; display: flex; align-items: center; gap: 15px; }
    .admin-actions { display: flex; gap: 15px; flex-wrap: wrap; }

    .btn-admin {
        background: #dc3545; color: white; padding: 12px 24px; border-radius: 8px; text-decoration: none;
        font-weight: 700; display: flex; align-items: center; gap: 8px; border: none; cursor: pointer;
        transition: 0.3s; box-shadow: 0 4px 15px rgba(220, 53, 69, 0.3);
    }
    .btn-admin:hover { transform: translateY(-2px); filter: brightness(1.1); }
    
    /* Culori specifice pentru butoane */
    .btn-excel { background: #10b981; box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3); }
    .btn-tichete { background: #007bff; box-shadow: 0 4px 15px rgba(0, 123, 255, 0.3); }

    /* CARDURI STATISTICI */
    .section-title { font-size: 22px; color: #38bdf8; margin-bottom: 20px; border-bottom: 1px dashed rgba(56,189,248,0.3); padding-bottom: 10px; }
    
    .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 25px; margin-bottom: 40px; }

    .stat-card-red {
        background: rgba(220, 53, 69, 0.05); border: 1px solid rgba(220, 53, 69, 0.2); border-left: 4px solid #dc3545;
        border-radius: 12px; padding: 24px; display: flex; flex-direction: column; transition: 0.3s;
    }
    .stat-card-red:hover { transform: translateY(-5px); background: rgba(220, 53, 69, 0.1); }
    
    .stat-card-blue {
        background: rgba(56, 189, 248, 0.05); border: 1px solid rgba(56, 189, 248, 0.2); border-left: 4px solid #38bdf8;
        border-radius: 12px; padding: 24px; display: flex; flex-direction: column; transition: 0.3s;
    }
    .stat-card-blue:hover { transform: translateY(-5px); background: rgba(56, 189, 248, 0.1); }

    .stat-icon { font-size: 28px; margin-bottom: 10px; }
    .stat-value { font-size: 34px; font-weight: 800; color: #fff; line-height: 1; }
    .stat-title { font-size: 15px; color: #e2e8f0; margin-top: 5px; font-weight: 600; }
    .stat-subtitle { font-size: 13px; color: #8892b0; margin-top: 10px; }

    /* ZONA GRAFICE */
    .charts-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 30px; margin-bottom: 40px; }
    .chart-container { background: rgba(255, 255, 255, 0.03); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 16px; padding: 20px; }
    .chart-container h3 { margin: 0 0 20px 0; color: #fff; font-size: 18px; text-align: center; }
    
    .canvas-wrapper { position: relative; height: 280px; width: 100%; }

    /* TABEL TOP EVENIMENTE */
    .table-container { background: rgba(255, 255, 255, 0.03); border-radius: 16px; border: 1px solid rgba(255, 255, 255, 0.1); padding: 20px; overflow-x: auto; }
    .admin-table { width: 100%; border-collapse: collapse; }
    .admin-table th { background: rgba(220, 53, 69, 0.1); color: #ff4d4d; padding: 15px; text-align: left; font-weight: 700; text-transform: uppercase; font-size: 13px;}
    .admin-table td { padding: 15px; border-bottom: 1px solid rgba(255,255,255,0.05); color: #e2e8f0; font-size: 15px; }
    .admin-table tr:hover { background: rgba(255,255,255,0.02); }
</style>

<div class="admin-dashboard fade-up-element" id="pdf-content">
    
    <div class="admin-header">
        <h1><span style="background: #dc3545; padding: 8px 12px; border-radius: 8px; color: white;">⚙️</span> Admin Tools & Analytics</h1>
        
        <div class="admin-actions" id="action-buttons">
    <!-- Butoanele NOI pentru hărți -->
    <a href="mapare_statii.php" class="btn-admin" style="background: #6f42c1; box-shadow: 0 4px 15px rgba(111, 66, 193, 0.3);">📍 Mapare Stații</a>
    <a href="mapare_rute.php" class="btn-admin" style="background: #fd7e14; box-shadow: 0 4px 15px rgba(253, 126, 20, 0.3);">🗺️ Mapare Rute</a>
    
    <!-- Butoanele vechi care existau deja -->
    <a href="admin_tichete.php" class="btn-admin btn-tichete">🛠️ Panou Tichete Suport</a>
    <a href="admin.php?export=excel" class="btn-admin btn-excel">📊 Exportă Excel Utilizatori</a>
    <button onclick="genereazaPDF()" class="btn-admin">📄 Salvează Raport PDF</button>
</div>
    </div>

    <h2 class="section-title">Financiar & Audiență</h2>
    <div class="stats-grid">
        <div class="stat-card-red">
            <div class="stat-icon">👥</div>
            <div class="stat-value"><?= $total_useri ?></div>
            <div class="stat-title">Conturi Înregistrate</div>
            <div class="stat-subtitle">Total utilizatori platformă</div>
        </div>
        <div class="stat-card-red">
            <div class="stat-icon">💳</div>
            <div class="stat-value"><?= $useri_cu_bilete ?></div>
            <div class="stat-title">Clienți Activi</div>
            <div class="stat-subtitle">Au achiziționat minim 1 bilet</div>
        </div>
        <div class="stat-card-red">
            <div class="stat-icon">✉️</div>
            <div class="stat-value"><?= $total_newsletter ?></div>
            <div class="stat-title">Abonați Newsletter</div>
            <div class="stat-subtitle">Utilizatori pentru Email Marketing</div>
        </div>
        <div class="stat-card-red">
            <div class="stat-icon">💰</div>
            <div class="stat-value"><?= number_format($venit_total, 2) ?> <small style="font-size:16px;">RON</small></div>
            <div class="stat-title">Venituri Totale</div>
            <div class="stat-subtitle">Încasări din Transport și Evenimente</div>
        </div>
    </div>

    <h2 class="section-title">Interacțiuni Comunitate & Suport</h2>
    <div class="stats-grid">
        <div class="stat-card-blue">
            <div class="stat-icon">❤️</div>
            <div class="stat-value"><?= $total_favorite ?></div>
            <div class="stat-title">Salvări la Favorite</div>
            <div class="stat-subtitle">Interes pentru evenimente</div>
        </div>
        <div class="stat-card-blue">
            <div class="stat-icon">⭐</div>
            <div class="stat-value"><?= $recenzii_avg ?> / 5</div>
            <div class="stat-title">Media Recenziilor</div>
            <div class="stat-subtitle">Calificativul general al orașului</div>
        </div>
        <div class="stat-card-blue">
            <div class="stat-icon">🚦</div>
            <div class="stat-value"><?= $rapoarte_active ?> <small style="font-size:16px;">/ <?= $rapoarte_trafic ?></small></div>
            <div class="stat-title">Rapoarte Trafic</div>
            <div class="stat-subtitle">Alerte active vs. Total istoric</div>
        </div>
        <div class="stat-card-blue">
            <div class="stat-icon">🛠️</div>
            <div class="stat-value"><?= $tichete_deschise ?> <small style="font-size:16px;">/ <?= $tichete_total ?></small></div>
            <div class="stat-title">Tichete Suport</div>
            <div class="stat-subtitle">Probleme deschise în așteptare</div>
        </div>
    </div>

    <h2 class="section-title">Analiză Vizuală (Grafice)</h2>
    <div class="charts-grid">
        <div class="chart-container">
            <h3>📈 Vânzări Bilete (Transport vs. Evenimente)</h3>
            <div class="canvas-wrapper"><canvas id="barChart"></canvas></div>
        </div>
        <div class="chart-container">
            <h3>🎭 Categorii Evenimente (Cultural vs. Sportiv)</h3>
            <div class="canvas-wrapper"><canvas id="pieEvenimente"></canvas></div>
        </div>
        <div class="chart-container">
            <h3>📧 Distribuție Abonați Newsletter</h3>
            <div class="canvas-wrapper"><canvas id="pieNewsletter"></canvas></div>
        </div>
        <div class="chart-container">
            <h3>📊 Stare Tichete Suport</h3>
            <div class="canvas-wrapper"><canvas id="pieTichete"></canvas></div>
        </div>
    </div>

    <div class="table-container">
        <h3 style="margin: 0 0 20px 0; color: #fff;">🏆 Top 5 Cele Mai Vizualizate Evenimente</h3>
        <table class="admin-table">
            <thead>
                <tr>
                    <th style="width: 50px;">Loc</th>
                    <th>Nume Eveniment</th>
                    <th style="text-align: right;">Total Vizualizări</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($top_evenimente)): ?>
                    <tr><td colspan="3" style="text-align:center;">Nu există date încă.</td></tr>
                <?php else: ?>
                    <?php foreach ($top_evenimente as $index => $ev): ?>
                        <tr>
                            <td><strong>#<?= $index + 1 ?></strong></td>
                            <td style="font-weight: 600; color: #38bdf8;"><?= htmlspecialchars($ev['titlu']) ?></td>
                            <td style="text-align: right;">👁️ <?= $ev['vizualizari'] ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</div>

<script>
    const chartOptions = {
        responsive: true,
        maintainAspectRatio: false, 
        plugins: {
            legend: { position: 'bottom', labels: { color: '#e2e8f0', padding: 20 } }
        }
    };

    // 1. GRAFIC BARE (Vânzări)
    new Chart(document.getElementById('barChart').getContext('2d'), {
        type: 'bar',
        data: {
            labels: ['Bilete Transport', 'Bilete Evenimente'],
            datasets: [{
                label: 'Bilete Vândute',
                data: [<?= $total_bilete_bus ?>, <?= $total_bilete_ev ?>],
                backgroundColor: ['rgba(56, 189, 248, 0.8)', 'rgba(220, 53, 69, 0.8)'],
                borderWidth: 0, borderRadius: 6
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, grid: { color: 'rgba(255,255,255,0.05)' }, ticks: { color: '#e2e8f0' } },
                x: { grid: { display: false }, ticks: { color: '#e2e8f0' } }
            }
        }
    });

    // 2. GRAFIC PLĂCINTĂ (Categorii Evenimente)
    new Chart(document.getElementById('pieEvenimente').getContext('2d'), {
        type: 'doughnut',
        data: {
            labels: ['Culturale', 'Sportive'],
            datasets: [{
                data: [<?= $ev_culturale ?>, <?= $ev_sportive ?>],
                backgroundColor: ['#6f42c1', '#007bff'],
                borderWidth: 0, hoverOffset: 10
            }]
        },
        options: chartOptions
    });

    // 3. GRAFIC PLĂCINTĂ (Newsletter)
    new Chart(document.getElementById('pieNewsletter').getContext('2d'), {
        type: 'doughnut',
        data: {
            labels: ['Abonați', 'Neabonați'],
            datasets: [{
                data: [<?= $total_newsletter ?>, <?= $total_useri - $total_newsletter ?>],
                backgroundColor: ['#10b981', '#475569'],
                borderWidth: 0, hoverOffset: 10
            }]
        },
        options: chartOptions
    });

    // 4. GRAFIC PLĂCINTĂ (Tichete Suport)
    new Chart(document.getElementById('pieTichete').getContext('2d'), {
        type: 'pie',
        data: {
            labels: ['Deschise', 'Rezolvate'],
            datasets: [{
                data: [<?= $tichete_deschise ?>, <?= $tichete_total - $tichete_deschise ?>],
                backgroundColor: ['#dc3545', '#10b981'],
                borderWidth: 0, hoverOffset: 10
            }]
        },
        options: chartOptions
    });

    // 5. FUNCȚIE EXPORT PDF DASHBOARD
    function genereazaPDF() {
        const btnContainer = document.getElementById('action-buttons');
        btnContainer.style.display = 'none'; 
        
        const element = document.getElementById('pdf-content');
        const optiuni = {
            margin:       10, 
            filename:     'Raport_Complet_DescoperaBraila.pdf', 
            image:        { type: 'jpeg', quality: 0.98 }, 
            html2canvas:  { scale: 2, useCORS: true, backgroundColor: '#0A192F' }, 
            jsPDF:        { unit: 'mm', format: 'a4', orientation: 'landscape' } 
        };
        
        html2pdf().set(optiuni).from(element).save().then(() => {
            btnContainer.style.display = 'flex'; 
            if(typeof arataNotificare === 'function') arataNotificare('Raportul PDF a fost descărcat cu succes!', 'success');
        });
    }
</script>

<?php include 'footer.php'; ?>