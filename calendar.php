<?php 
require_once 'db_connect.php';

// 1. Verificăm ce categorie a fost cerută prin URL
$categorie = (isset($_GET['categorie']) && $_GET['categorie'] === 'sportiv') ? 'sportiv' : 'cultural';

// 2. Setăm variabilele dinamice în funcție de categorie
if ($categorie === 'sportiv') {
    $page_title = "Evenimente Sportive | Descoperă Brăila";
    $bg_image = "img/sport-bg.jpg"; 
    $titlu_pagina = "Evenimente Sportive în Brăila";
} else {
    $page_title = "Evenimente Culturale | Descoperă Brăila";
    $bg_image = "img/cultural-bg.jpg"; 
    $titlu_pagina = "Evenimente Culturale în Brăila";
}

$culoare_buton = "#007bff"; 
$culoare_admin = "#dc3545"; 

include 'header.php'; 

// --- LOGICA DE FILTRARE ---
$luna_curenta = intval(date('n'));
$anul_curent = intval(date('Y'));

$filtru_luna = isset($_GET['luna']) && !empty($_GET['luna']) ? intval($_GET['luna']) : $luna_curenta;
$filtru_an = isset($_GET['an']) && !empty($_GET['an']) ? intval($_GET['an']) : $anul_curent;

// Punctul 15: Verificăm dacă utilizatorul vrea doar evenimente GRATUITE
$filtru_gratuit = (isset($_GET['gratuit']) && $_GET['gratuit'] == '1') ? 1 : 0;

// Calculăm luna anterioară și următoare pentru săgeți
$prev_luna = $filtru_luna - 1;
$prev_an = $filtru_an;
if ($prev_luna < 1) { $prev_luna = 12; $prev_an--; }

$next_luna = $filtru_luna + 1;
$next_an = $filtru_an;
if ($next_luna > 12) { $next_luna = 1; $next_an++; }

// Păstrăm filtrul de gratuitate și când se apasă pe săgeți
$url_gratuit_param = $filtru_gratuit ? "&gratuit=1" : "";
$url_prev = "?categorie=" . htmlspecialchars($categorie) . "&luna=" . $prev_luna . "&an=" . $prev_an . $url_gratuit_param . "#calendar-view";
$url_next = "?categorie=" . htmlspecialchars($categorie) . "&luna=" . $next_luna . "&an=" . $next_an . $url_gratuit_param . "#calendar-view";

// Construim interogarea SQL (adăugăm condiția pret = 0 dacă este bifat filtrul)
if ($filtru_gratuit) {
    $stmt = $conn->prepare("SELECT * FROM evenimente WHERE categorie = ? AND MONTH(data_eveniment) = ? AND YEAR(data_eveniment) = ? AND (pret = 0 OR pret IS NULL) ORDER BY data_eveniment ASC");
} else {
    $stmt = $conn->prepare("SELECT * FROM evenimente WHERE categorie = ? AND MONTH(data_eveniment) = ? AND YEAR(data_eveniment) = ? ORDER BY data_eveniment ASC");
}

$stmt->bind_param("sii", $categorie, $filtru_luna, $filtru_an);
$stmt->execute();
$rezultat = $stmt->get_result();
$evenimente = $rezultat->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$luni_nume = [
    1 => 'Ianuarie', 2 => 'Februarie', 3 => 'Martie', 4 => 'Aprilie', 
    5 => 'Mai', 6 => 'Iunie', 7 => 'Iulie', 8 => 'August', 
    9 => 'Septembrie', 10 => 'Octombrie', 11 => 'Noiembrie', 12 => 'Decembrie'
];
?>

<style>
    /* FUNDAL INTEGRAT PENTRU TOATĂ PAGINA */
    body {
        background: linear-gradient(rgba(10, 25, 47, 0.9), rgba(10, 25, 47, 0.9)), url('<?= $bg_image ?>') no-repeat center center fixed !important;
        background-size: cover !important;
        color: #fff;
    }

    .calendar-container {
        padding: 120px 20px 80px; 
        max-width: 1300px;
        margin: 0 auto;
        min-height: 100vh;
        text-align: center;
        scroll-margin-top: 100px; 
    }

    .calendar-container h1 { 
        font-size: 48px; 
        font-weight: 800; 
        margin-bottom: 5px; 
        text-shadow: 0 4px 15px rgba(0,0,0,0.5);
    }
    
    .calendar-subtitle {
        font-size: 20px;
        color: #8892b0;
        margin-bottom: 40px;
        font-weight: 500;
    }

    /* FILTRE GLASSMORPHISM */
    .filter-wrapper {
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 20px;
        margin-bottom: 40px;
        flex-wrap: wrap;
    }
    
    .nav-arrow {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 50px;
        height: 50px;
        background: rgba(255, 255, 255, 0.05);
        color: #fff;
        border-radius: 50%;
        text-decoration: none;
        font-size: 24px;
        transition: all 0.3s ease;
        border: 1px solid rgba(255, 255, 255, 0.1);
        backdrop-filter: blur(5px);
    }
    .nav-arrow:hover {
        background: #007bff;
        border-color: #007bff;
        transform: scale(1.1);
        box-shadow: 0 5px 15px rgba(0, 123, 255, 0.4);
    }

    .filter-bar {
        background: rgba(255, 255, 255, 0.05);
        padding: 15px 30px;
        border-radius: 15px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        display: flex;
        gap: 15px;
        justify-content: center;
        align-items: center;
        border: 1px solid rgba(255, 255, 255, 0.1);
        backdrop-filter: blur(15px);
        margin: 0; 
    }
    
    .filter-bar select {
        padding: 12px 20px;
        border-radius: 10px;
        border: 1px solid rgba(255, 255, 255, 0.2);
        background: rgba(0, 0, 0, 0.3);
        color: #fff;
        font-family: inherit;
        font-size: 16px;
        font-weight: 600;
        outline: none;
        min-width: 160px;
        cursor: pointer;
    }
    .filter-bar select option { background: #0A192F; color: #fff; }

    /* Stilizare Checkbox "Gratuit" */
    .filter-checkbox {
        display: flex;
        align-items: center;
        gap: 8px;
        color: #fff;
        font-weight: 600;
        font-size: 16px;
        cursor: pointer;
        padding: 10px;
        border-radius: 8px;
        transition: 0.3s;
    }
    .filter-checkbox:hover { background: rgba(255,255,255,0.1); }
    .filter-checkbox input[type="checkbox"] { width: 18px; height: 18px; cursor: pointer; accent-color: #10b981; }

    .filter-bar button {
        background: <?= $culoare_buton ?>;
        color: white;
        border: none;
        padding: 13px 28px;
        border-radius: 10px;
        font-weight: bold;
        font-size: 16px;
        cursor: pointer;
        transition: 0.3s;
    }
    .filter-bar button:hover { 
        background: #38bdf8; 
        transform: translateY(-2px); 
        box-shadow: 0 5px 15px rgba(56, 189, 248, 0.3);
    }

    /* CARDURI EVENIMENTE SOLIDE */
    .events-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
        gap: 30px;
        text-align: left;
    }

    .event-modern-card {
        background: #0f172a; 
        border-radius: 20px;
        overflow: hidden;
        box-shadow: 0 15px 35px rgba(0,0,0,0.5); 
        transition: transform 0.3s ease, border-color 0.3s ease, box-shadow 0.3s ease;
        border: 1px solid rgba(255, 255, 255, 0.1);
        display: flex;
        flex-direction: column;
        position: relative;
    }

    .event-modern-card:hover {
        transform: translateY(-10px);
        box-shadow: 0 20px 45px rgba(0,0,0,0.7);
        border-color: #38bdf8;
    }

    .event-card-img {
        width: 100%;
        height: 220px; 
        object-fit: cover;
    }

    .event-date-badge {
        position: absolute;
        top: 20px;
        left: 20px;
        background: #0A192F;
        color: #fff;
        padding: 10px 18px;
        border-radius: 12px;
        font-weight: 800;
        text-align: center;
        box-shadow: 0 5px 15px rgba(0,0,0,0.5);
        border: 1px solid rgba(255,255,255,0.1);
    }
    .event-date-badge span { display: block; font-size: 26px; line-height: 1; color: #38bdf8; }
    .event-date-badge small { font-size: 13px; text-transform: uppercase; font-weight: 700; color: #e2e8f0; }

    .event-card-body { padding: 30px; flex-grow: 1; display: flex; flex-direction: column; }
    .event-card-title { font-size: 22px; font-weight: 800; color: #ffffff; margin: 0 0 15px 0; line-height: 1.3; }
    .event-card-info { color: #8892b0; font-size: 15px; margin-bottom: 10px; display: flex; align-items: center; gap: 8px; font-weight: 500;}
    
    .event-card-footer { 
        margin-top: auto; 
        padding-top: 25px; 
        border-top: 1px solid rgba(255,255,255,0.05); 
        display: flex; 
        justify-content: space-between; 
        align-items: center; 
    }
    .event-price { font-weight: 800; font-size: 20px; color: #ffffff; }
    
    .event-btn { 
        background: <?= $culoare_buton ?>; 
        color: white; 
        padding: 10px 20px; 
        border-radius: 8px; 
        text-decoration: none;
        font-weight: 700; 
        transition: 0.3s; 
        font-size: 15px; 
    }
    .event-btn:hover { background: #38bdf8; transform: translateY(-2px); box-shadow: 0 4px 15px rgba(56, 189, 248, 0.4); }

    .btn-admin-red {
        background: <?= $culoare_admin ?>; 
        color: white;
        padding: 12px 28px;
        border-radius: 10px;
        text-decoration: none;
        font-weight: 800;
        font-size: 15px;
        display: inline-block;
        transition: all 0.3s ease;
        box-shadow: 0 4px 15px rgba(220, 53, 69, 0.3);
        border: none;
    }
    .btn-admin-red:hover {
        background: #c82333;
        transform: translateY(-2px);
    }
</style>

<div class="calendar-container fade-up-element" id="calendar-view">
    
    <h1><?= $titlu_pagina ?></h1>
    <p class="calendar-subtitle">Evenimentele din luna <?= $luni_nume[$filtru_luna] ?> <?= $filtru_an ?></p>
    
    <div class="filter-wrapper">
        <a href="<?= $url_prev ?>" class="nav-arrow" title="Luna Anterioară">⬅️</a>
        
        <form method="GET" action="calendar.php#calendar-view" class="filter-bar">
            <input type="hidden" name="categorie" value="<?= htmlspecialchars($categorie) ?>">
            
            <div style="font-weight: 700; font-size: 16px; color: #fff; margin-right: 5px;">📅 Caută:</div>
            
            <select name="luna">
                <?php foreach($luni_nume as $numar => $nume): ?>
                    <option value="<?= $numar ?>" <?= ($filtru_luna == $numar) ? 'selected' : '' ?>><?= $nume ?></option>
                <?php endforeach; ?>
            </select>

            <select name="an">
                <?php for($i = $anul_curent - 1; $i <= $anul_curent + 2; $i++): ?>
                    <option value="<?= $i ?>" <?= ($filtru_an == $i) ? 'selected' : '' ?>><?= $i ?></option>
                <?php endfor; ?>
            </select>

            <!-- Bifa NOUĂ pentru evenimente Gratuite (Punct 15) -->
            <label class="filter-checkbox">
                <input type="checkbox" name="gratuit" value="1" <?= $filtru_gratuit ? 'checked' : '' ?>>
                Doar Gratuite
            </label>

            <button type="submit">Filtrează</button>
        </form>

        <a href="<?= $url_next ?>" class="nav-arrow" title="Luna Următoare">➡️</a>
    </div>

    <?php if (isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin'): ?>
    <div style="margin-bottom: 40px; text-align: right;">
        <a href="adauga_eveniment.php?categorie=<?= $categorie ?>" class="btn-admin-red">
            ➕ Adaugă Eveniment <?= ucfirst($categorie) ?>
        </a>
    </div>
    <?php endif; ?>

    <div class="events-grid">
        <?php if(count($evenimente) > 0): ?>
            <?php foreach($evenimente as $ev): 
                $luna_abreviata = substr($luni_nume[intval(date('m', strtotime($ev['data_eveniment'])))], 0, 3);
                $ziua = date('d', strtotime($ev['data_eveniment']));
                $ora = date('H:i', strtotime($ev['data_eveniment']));
                $pret_afisat = ($ev['pret'] > 0) ? $ev['pret'] . ' RON' : 'GRATUIT';
                $imagine = !empty($ev['imagine']) ? htmlspecialchars($ev['imagine']) : 'img/default-event.jpg';
                
                $eveniment_expirat = strtotime($ev['data_eveniment']) < time();
            ?>
                <div class="event-modern-card" style="<?= $eveniment_expirat ? 'opacity: 0.7;' : '' ?>">
                    <img src="<?= $imagine ?>" alt="<?= htmlspecialchars($ev['titlu']) ?>" class="event-card-img" style="<?= $eveniment_expirat ? 'filter: grayscale(100%);' : '' ?>">
                    
                    <div class="event-date-badge" style="<?= $eveniment_expirat ? 'background: #111;' : '' ?>">
                        <span style="<?= $eveniment_expirat ? 'color: #8892b0;' : '' ?>"><?= $ziua ?></span>
                        <small style="<?= $eveniment_expirat ? 'color: #8892b0;' : '' ?>"><?= $luna_abreviata ?></small>
                    </div>

                    <div class="event-card-body">
                        <h3 class="event-card-title"><?= htmlspecialchars($ev['titlu']) ?></h3>
                        <div class="event-card-info">📍 <?= htmlspecialchars($ev['locatie']) ?></div>
                        <div class="event-card-info">⏱️ <?= $eveniment_expirat ? 'A avut loc la' : 'Ora' ?> <?= $ora ?></div>
                        
                        <div class="event-card-footer">
                            <span class="event-price" style="color: <?= ($ev['pret'] == 0) ? '#10b981' : '#ffffff' ?>;">
                                <?= $pret_afisat ?>
                            </span>
                            <a href="evenimentextins.php?id=<?= $ev['id'] ?>" class="event-btn" style="<?= $eveniment_expirat ? 'background: #475569;' : '' ?>">
                                <?= $eveniment_expirat ? 'Arhivă' : 'Detalii ➡️' ?>
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div style="grid-column: 1 / -1; text-align: center; padding: 80px 20px; background: rgba(15, 23, 42, 0.8); border-radius: 20px; border: 1px dashed rgba(255,255,255,0.2); box-shadow: 0 10px 30px rgba(0,0,0,0.3);">
                <h3 style="font-size: 28px; margin-bottom: 15px; color: #ffffff;">Nu am găsit evenimente <?= $filtru_gratuit ? 'gratuite ' : '' ?>pentru <?= $luni_nume[$filtru_luna] ?> <?= $filtru_an ?>.</h3>
                <p style="color: #8892b0; font-size: 18px;">Încearcă să schimbi criteriile de căutare!</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'footer.php'; ?>