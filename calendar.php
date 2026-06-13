<?php 
require_once 'db_connect.php';

// 1. Verificăm ce categorie a fost cerută prin URL
$categorie = (isset($_GET['categorie']) && $_GET['categorie'] === 'sportiv') ? 'sportiv' : 'cultural';

// 2. Setăm variabilele dinamice în funcție de categorie
if ($categorie === 'sportiv') {
    $page_title = "Evenimente Sportive | Descoperă Brăila";
    $bg_image = "img/sport-bg.jpg"; 
    $titlu_pagina = "Evenimente Sportive în Brăila";
    $titlu_calendar = "Competiții și Evenimente";
    $culoare_buton = "#007bff";
    $culoare_bg_sectiune = "var(--bg-main)";
} else {
    $page_title = "Evenimente Culturale | Descoperă Brăila";
    $bg_image = "img/cultural-bg.jpg"; 
    $titlu_pagina = "Evenimente Culturale în Brăila";
    $titlu_calendar = "Spectacole și Festivaluri";
    $culoare_buton = "#28a745";
    $culoare_bg_sectiune = "var(--bg-main)";
}

include 'header.php'; 

// --- LOGICA NOUĂ DE FILTRARE ---
$filtru_luna = isset($_GET['luna']) && !empty($_GET['luna']) ? intval($_GET['luna']) : null;
$filtru_an = isset($_GET['an']) && !empty($_GET['an']) ? intval($_GET['an']) : null;

if ($filtru_luna && $filtru_an) {
    // Dacă a selectat o lună și un an, afișăm TOATE evenimentele din acea lună (inclusiv cele trecute)
    $stmt = $conn->prepare("SELECT * FROM evenimente WHERE categorie = ? AND MONTH(data_eveniment) = ? AND YEAR(data_eveniment) = ? ORDER BY data_eveniment ASC");
    $stmt->bind_param("sii", $categorie, $filtru_luna, $filtru_an);
} else {
    // Default: Afișăm doar evenimentele de acum încolo
    $stmt = $conn->prepare("SELECT * FROM evenimente WHERE categorie = ? AND data_eveniment >= NOW() ORDER BY data_eveniment ASC");
    $stmt->bind_param("s", $categorie);
}

$stmt->execute();
$rezultat = $stmt->get_result();
$evenimente = $rezultat->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Numele lunilor pentru dropdown
$luni_nume = [
    1 => 'Ianuarie', 2 => 'Februarie', 3 => 'Martie', 4 => 'Aprilie', 
    5 => 'Mai', 6 => 'Iunie', 7 => 'Iulie', 8 => 'August', 
    9 => 'Septembrie', 10 => 'Octombrie', 11 => 'Noiembrie', 12 => 'Decembrie'
];
?>

<style>
    .hero-calendar { 
        background: linear-gradient(rgba(0,0,0,0.5), rgba(0,0,0,0.7)), url('<?= $bg_image ?>') no-repeat center center/cover; 
        color: white; 
        padding: 120px 20px 80px; 
        text-align: center; 
        margin-top: 60px; 
    }
    .hero-calendar h1 { font-size: 50px; text-shadow: 0 5px 15px rgba(0,0,0,0.5); font-weight: 800; margin-bottom: 10px; }
    .hero-calendar p { font-size: 18px; opacity: 0.9; max-width: 600px; margin: 0 auto; }

    .calendar-section { background: <?= $culoare_bg_sectiune ?>; padding: 60px 20px; text-align: center; color: var(--text-main); min-height: 50vh; }
    .calendar-section h2 { color: var(--text-main); font-size: 32px; margin-bottom: 20px; font-weight: 700; }

    /* FILTRE BARĂ */
    .filter-bar {
        background: var(--card-bg);
        padding: 20px;
        border-radius: 15px;
        box-shadow: 0 5px 20px rgba(0,0,0,0.05);
        display: flex;
        gap: 15px;
        justify-content: center;
        align-items: center;
        max-width: 800px;
        margin: 0 auto 40px auto;
        flex-wrap: wrap;
        border: 1px solid var(--border-color);
    }
    
    .filter-bar select {
        padding: 10px 15px;
        border-radius: 10px;
        border: 2px solid var(--border-color);
        background: var(--bg-main);
        color: var(--text-main);
        font-family: inherit;
        font-size: 15px;
        outline: none;
        min-width: 150px;
    }

    .filter-bar button {
        background: <?= $culoare_buton ?>;
        color: white;
        border: none;
        padding: 12px 25px;
        border-radius: 10px;
        font-weight: bold;
        cursor: pointer;
        transition: 0.3s;
    }
    .filter-bar button:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
    
    .filter-reset {
        text-decoration: none;
        color: var(--text-light);
        font-size: 14px;
        font-weight: 600;
    }
    .filter-reset:hover { color: var(--accent-delete); }

    /* GRID MODERN DE CARDURI */
    .events-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
        gap: 30px;
        max-width: 1200px;
        margin: 0 auto;
        text-align: left;
    }

    .event-modern-card {
        background: var(--card-bg);
        border-radius: 20px;
        overflow: hidden;
        box-shadow: 0 10px 30px rgba(0,0,0,0.08);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        border: 1px solid var(--border-color);
        display: flex;
        flex-direction: column;
        position: relative;
    }

    .event-modern-card:hover {
        transform: translateY(-10px);
        box-shadow: 0 15px 40px rgba(0,0,0,0.15);
    }

    .event-card-img {
        width: 100%;
        height: 220px;
        object-fit: cover;
        background: #eee;
    }

    .event-date-badge {
        position: absolute;
        top: 15px;
        left: 15px;
        background: rgba(255, 255, 255, 0.95);
        color: #111;
        padding: 8px 15px;
        border-radius: 12px;
        font-weight: 800;
        text-align: center;
        box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        backdrop-filter: blur(5px);
    }
    .event-date-badge span { display: block; font-size: 24px; line-height: 1; color: <?= $culoare_buton ?>; }
    .event-date-badge small { font-size: 12px; text-transform: uppercase; font-weight: 600; }

    .event-card-body { padding: 25px; flex-grow: 1; display: flex; flex-direction: column; }
    .event-card-title { font-size: 22px; font-weight: 700; color: var(--text-main); margin: 0 0 10px 0; line-height: 1.3; }
    .event-card-info { color: var(--text-light); font-size: 14px; margin-bottom: 8px; display: flex; align-items: center; gap: 8px; }
    .event-card-footer { margin-top: auto; padding-top: 20px; border-top: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; }
    .event-price { font-weight: 800; font-size: 18px; color: var(--text-main); }
    .event-btn { background: <?= $culoare_buton ?>; color: white; padding: 10px 20px; border-radius: 10px; text-decoration: none; font-weight: 600; transition: 0.2s; font-size: 14px; }
    .event-btn:hover { filter: brightness(1.1); }
</style>

<section class="hero-calendar">
    <h1><?= $titlu_pagina ?></h1>
    <p>Descoperă cele mai tari evenimente din orașul tău, trecute și viitoare.</p>
</section>

<section class="calendar-section">
    <h2><?= $titlu_calendar ?></h2>
    
    <form method="GET" action="calendar.php" class="filter-bar fade-up-element">
        <input type="hidden" name="categorie" value="<?= htmlspecialchars($categorie) ?>">
        
        <div style="font-weight: 600; color: var(--text-main);">📅 Caută în arhiva:</div>
        
        <select name="luna">
            <option value="">-- Alege Luna --</option>
            <?php foreach($luni_nume as $numar => $nume): ?>
                <option value="<?= $numar ?>" <?= ($filtru_luna == $numar) ? 'selected' : '' ?>><?= $nume ?></option>
            <?php endforeach; ?>
        </select>

        <select name="an">
            <option value="">-- Alege Anul --</option>
            <?php 
            $anul_curent = date('Y');
            // Afișăm anul trecut, anul curent și următorii 2 ani
            for($i = $anul_curent - 1; $i <= $anul_curent + 2; $i++): ?>
                <option value="<?= $i ?>" <?= ($filtru_an == $i) ? 'selected' : '' ?>><?= $i ?></option>
            <?php endfor; ?>
        </select>

        <button type="submit">🔍 Filtrează</button>
        
        <?php if($filtru_luna || $filtru_an): ?>
            <a href="calendar.php?categorie=<?= htmlspecialchars($categorie) ?>" class="filter-reset">✖ Reset</a>
        <?php endif; ?>
    </form>

    <?php if (isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin'): ?>
    <div style="max-width: 1200px; margin: 0 auto 30px auto; text-align: right;">
        <a href="adauga_eveniment.php?categorie=<?= $categorie ?>" style="background: <?= $culoare_buton ?>; color: white; padding: 12px 25px; text-decoration: none; border-radius: 10px; font-weight: bold; display: inline-block; box-shadow: 0 4px 15px rgba(0,0,0,0.1); transition: 0.3s;">
            ➕ Adaugă Eveniment <?= ucfirst($categorie) ?>
        </a>
    </div>
    <?php endif; ?>

    <div class="events-grid fade-up-element">
        <?php if(count($evenimente) > 0): ?>
            <?php foreach($evenimente as $ev): 
                // Ne asigurăm că luna afișată pe poză e în română
                $luna_abreviata = substr($luni_nume[intval(date('m', strtotime($ev['data_eveniment'])))], 0, 3);
                $ziua = date('d', strtotime($ev['data_eveniment']));
                $ora = date('H:i', strtotime($ev['data_eveniment']));
                $pret_afisat = ($ev['pret'] > 0) ? $ev['pret'] . ' RON' : 'GRATUIT';
                $imagine = !empty($ev['imagine']) ? htmlspecialchars($ev['imagine']) : 'img/default-event.jpg';
                
                // Verificăm dacă evenimentul a trecut deja
                $eveniment_expirat = strtotime($ev['data_eveniment']) < time();
            ?>
                <div class="event-modern-card" style="<?= $eveniment_expirat ? 'opacity: 0.85;' : '' ?>">
                    <img src="<?= $imagine ?>" alt="<?= htmlspecialchars($ev['titlu']) ?>" class="event-card-img" style="<?= $eveniment_expirat ? 'filter: grayscale(80%);' : '' ?>">
                    
                    <div class="event-date-badge" style="<?= $eveniment_expirat ? 'background: #ddd; color: #555;' : '' ?>">
                        <span style="<?= $eveniment_expirat ? 'color: #555;' : '' ?>"><?= $ziua ?></span>
                        <small><?= $luna_abreviata ?></small>
                    </div>

                    <div class="event-card-body">
                        <h3 class="event-card-title"><?= htmlspecialchars($ev['titlu']) ?></h3>
                        <div class="event-card-info">📍 <?= htmlspecialchars($ev['locatie']) ?></div>
                        <div class="event-card-info">⏱️ <?= $eveniment_expirat ? 'A avut loc la ora' : 'Ora' ?> <?= $ora ?></div>
                        
                        <div class="event-card-footer">
                            <span class="event-price" style="color: <?= ($ev['pret'] > 0) ? 'var(--text-main)' : 'var(--accent-success)' ?>;">
                                <?= $pret_afisat ?>
                            </span>
                            <a href="evenimentextins.php?id=<?= $ev['id'] ?>" class="event-btn" style="<?= $eveniment_expirat ? 'background: var(--text-light);' : '' ?>">
                                <?= $eveniment_expirat ? 'Vezi Arhiva' : 'Vezi Detalii ➡️' ?>
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div style="grid-column: 1 / -1; text-align: center; padding: 50px; background: var(--card-bg); border-radius: 20px; border: 1px dashed var(--border-color);">
                <h3 style="font-size: 24px; margin-bottom: 10px;">Nu am găsit evenimente în această perioadă.</h3>
                <p style="color: var(--text-light);">Încearcă să selectezi o altă lună sau un alt an din filtru!</p>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php include 'footer.php'; ?>