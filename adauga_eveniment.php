<?php
$page_title = 'Adaugă Eveniment Nou | Descoperă Brăila';
include 'header.php';
require_once 'db_connect.php';

// Măsuri de securitate: Doar adminii au voie
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    die("<div style='text-align:center; padding: 120px 20px; min-height: 60vh;'><h2 style='color: var(--text-main);'>Acces interzis!</h2><p style='color: var(--text-main);'>Trebuie să fii administrator pentru a adăuga evenimente.</p></div>");
}

$mesaj = '';
$categorie_preselectata = isset($_GET['categorie']) ? $_GET['categorie'] : 'cultural';
$categorii_valide = ['cultural', 'sportiv'];

if (!in_array($categorie_preselectata, $categorii_valide)) {
    $categorie_preselectata = 'cultural';
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $titlu          = trim($_POST['titlu']);
    $descriere      = trim($_POST['descriere']);
    $data_eveniment = $_POST['data_eveniment'];
    $locatie        = trim($_POST['locatie']);
    $categorie      = isset($_POST['categorie']) ? $_POST['categorie'] : $categorie_preselectata;
    $pret           = isset($_POST['pret']) && is_numeric($_POST['pret']) ? floatval($_POST['pret']) : 0.00;

    // Preluăm link-ul live doar dacă bifa este pusă
    $link_live      = isset($_POST['este_live']) && !empty(trim($_POST['link_live'])) ? trim($_POST['link_live']) : NULL;

    if (!in_array($categorie, $categorii_valide)) {
        $categorie = 'cultural';
    }

    // --- LOGICA PENTRU UPLOAD IMAGINE ---
    $cale_imagine = NULL;
    if (isset($_FILES['imagine']) && $_FILES['imagine']['error'] === UPLOAD_ERR_OK) {
        $extensie = strtolower(pathinfo($_FILES["imagine"]["name"], PATHINFO_EXTENSION));
        // Generăm un nume unic pentru a nu suprascrie poze cu același nume
        $nume_nou = uniqid('ev_') . '.' . $extensie; 
        $target_dir = "img/";
        
        // Creăm folderul 'img' automat dacă nu există
        if (!file_exists($target_dir)) { mkdir($target_dir, 0777, true); }
        
        $target_file = $target_dir . $nume_nou;
        
        // Verificăm dacă fișierul este cu adevărat o imagine
        if (in_array($extensie, ['jpg', 'jpeg', 'png', 'webp', 'gif'])) {
            if (move_uploaded_file($_FILES["imagine"]["tmp_name"], $target_file)) {
                $cale_imagine = $target_file; // Salvăm calea (ex: img/ev_65a4b.jpg)
            }
        } else {
            $mesaj = "<div style='color: #856404; background: #fff3cd; padding: 15px; border-radius: 8px; margin-bottom: 25px;'>⚠️ Imaginea trebuie să fie format JPG, PNG, WEBP sau GIF.</div>";
        }
    }

    // Inserăm în baza de date INCLUSIV imaginea și link-ul live
    if (empty($mesaj)) {
        $stmt = $conn->prepare('INSERT INTO evenimente (titlu, descriere, data_eveniment, locatie, categorie, pret, imagine, link_live) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
        $stmt->bind_param('sssssdss', $titlu, $descriere, $data_eveniment, $locatie, $categorie, $pret, $cale_imagine, $link_live);

        if ($stmt->execute()) {
            $mesaj = "<div style='color: #155724; background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 8px; margin-bottom: 25px;'>✅ Evenimentul a fost adăugat cu succes!</div>";
        } else {
            error_log("Eroare adaugare eveniment: " . $stmt->error);
            $mesaj = "<div style='color: #721c24; background: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; border-radius: 8px; margin-bottom: 25px;'>❌ A apărut o eroare. Încearcă din nou.</div>";
        }
        $stmt->close();
    }
}
?>

<section class="form-admin-section" style="padding: 120px 20px 60px; max-width: 800px; margin: auto; min-height: 70vh;">
    <h2 style="margin-bottom: 20px; color: var(--text-main);">➕ Adaugă Eveniment Nou</h2>

    <?= $mesaj ?>

    <form action="adauga_eveniment.php?categorie=<?= htmlspecialchars($categorie_preselectata) ?>" method="POST" enctype="multipart/form-data" class="modern-form" style="background: var(--card-bg); padding: 40px; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); border: 1px solid var(--border-color);">

        <div style="margin-bottom: 25px;">
            <label style="font-weight: 700; color: var(--text-main); display: block; margin-bottom: 12px; font-size: 16px;">Titlu Eveniment:</label>
            <input type="text" name="titlu" required style="width: 100%; padding: 12px 15px; border: 2px solid var(--border-color); border-radius: 10px; font-family: inherit; font-size: 15px; background: var(--bg-main); color: var(--text-main);">
        </div>

        <div style="display: flex; gap: 20px; margin-bottom: 25px;">
            <div style="flex: 1;">
                <label style="font-weight: 700; color: var(--text-main); display: block; margin-bottom: 12px; font-size: 16px;">Data Evenimentului:</label>
               <input type="datetime-local" name="data_eveniment" required style="width: 100%; padding: 12px 15px; border: 2px solid var(--border-color); border-radius: 10px; font-family: inherit; font-size: 15px; background: var(--bg-main); color: var(--text-main);">
            </div>

           <div style="flex: 1;">
    <label style="font-weight: 700; color: var(--text-main); display: block; margin-bottom: 12px; font-size: 16px;">Durată (minute):</label>
    <input type="number" name="durata_minute" value="<?= isset($eveniment) ? $eveniment['durata_minute'] : '90' ?>" required style="width: 100%; padding: 12px 15px; border: 2px solid var(--border-color); border-radius: 10px; background: var(--bg-main); color: var(--text-main);">
</div>
        </div>

        <div style="display: flex; gap: 20px; margin-bottom: 25px;">
            <div style="flex: 1;">
                <label style="font-weight: 700; color: var(--text-main); display: block; margin-bottom: 12px; font-size: 16px;">Categorie:</label>
                <select name="categorie" style="width: 100%; padding: 12px 15px; border: 2px solid var(--border-color); border-radius: 10px; font-family: inherit; font-size: 15px; outline: none; background: var(--bg-main); color: var(--text-main);">
                    <option value="cultural" <?= $categorie_preselectata === 'cultural' ? 'selected' : '' ?>>Cultural</option>
                    <option value="sportiv" <?= $categorie_preselectata === 'sportiv' ? 'selected' : '' ?>>Sportiv</option>
                </select>
            </div>
            
            <div style="flex: 1;">
                <label style="font-weight: 700; color: var(--text-main); display: block; margin-bottom: 12px; font-size: 16px;">Afiș / Poză Eveniment:</label>
                <input type="file" name="imagine" accept="image/*" style="width: 100%; padding: 9px 15px; border: 2px solid var(--border-color); border-radius: 10px; font-family: inherit; font-size: 14px; background: var(--bg-main); color: var(--text-main);">
            </div>
        </div>

        <div style="margin-bottom: 25px;">
            <label style="font-weight: 700; color: var(--text-main); display: block; margin-bottom: 12px; font-size: 16px;">Locație:</label>
            <input type="text" name="locatie" required style="width: 100%; padding: 12px 15px; border: 2px solid var(--border-color); border-radius: 10px; font-family: inherit; font-size: 15px; background: var(--bg-main); color: var(--text-main);">
        </div>

        <div style="margin-bottom: 25px; padding: 15px; border: 2px dashed var(--accent-delete); border-radius: 10px; background: rgba(255,0,0,0.03);">
            <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; color: var(--text-main); font-weight: 700; font-size: 16px;">
                <input type="checkbox" name="este_live" id="checkLive" onchange="toggleLiveField()" style="width: 20px; height: 20px; cursor: pointer;">
                🔴 Acest eveniment are o componentă LIVE (Bilet Online)
            </label>
            
            <div id="divLinkLive" style="display: none; margin-top: 15px;">
                <label style="font-weight: 700; color: var(--text-main); display: block; margin-bottom: 8px;">Link YouTube Live:</label>
                <input type="url" name="link_live" placeholder="Ex: https://youtube.com/watch?v=..." style="width: 100%; padding: 12px 15px; border: 2px solid var(--border-color); border-radius: 10px; font-family: inherit; font-size: 15px; background: var(--bg-main); color: var(--text-main);">
                <small style="color: var(--text-main); opacity: 0.7; display: block; margin-top: 5px;">* Vizitatorii vor putea achiziționa bilet online pentru a vedea acest link.</small>
            </div>
        </div>
        <script>
            function toggleLiveField() {
                var checkBox = document.getElementById("checkLive");
                var divLive = document.getElementById("divLinkLive");
                if (checkBox.checked == true) {
                    divLive.style.display = "block";
                } else {
                    divLive.style.display = "none";
                }
            }
        </script>

        <div style="margin-bottom: 30px;">
            <label style="font-weight: 700; color: var(--text-main); display: block; margin-bottom: 12px; font-size: 16px;">Descriere Detaliată:</label>
            <textarea name="descriere" rows="6" required style="width: 100%; padding: 15px; border: 2px solid var(--border-color); border-radius: 10px; font-family: inherit; font-size: 15px; resize: vertical; background: var(--bg-main); color: var(--text-main);"></textarea>
        </div>

        <div style="display: flex; justify-content: space-between; align-items: center; border-top: 2px solid var(--border-color); padding-top: 20px;">
            <a href="evenimente.php" style="color: var(--text-main); opacity: 0.8; text-decoration: none; font-weight: 600; transition: color 0.3s;">⬅️ Înapoi la Evenimente</a>
            <button type="submit" class="btn-submit-modern" style="background: #28a745; width: auto; padding: 12px 30px; margin-top: 0;">➕ Adaugă Evenimentul</button>
        </div>
    </form>
</section>

<?php include 'footer.php'; ?>