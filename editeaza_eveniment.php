<?php
session_start();
$page_title = "Editează Eveniment";
include 'header.php';
require_once 'db_connect.php';

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    die("<div style='text-align:center; padding: 120px 20px; min-height: 60vh;'><h2 style='color: var(--text-main);'>Acces interzis!</h2></div>");
}

$mesaj = "";

// 1. PROCESARE FORMULAR DE SALVARE
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id'])) {
    $id = intval($_POST['id']);
    $titlu = trim($_POST['titlu']);
    $descriere = trim($_POST['descriere']);
    $data_eveniment = $_POST['data_eveniment'];
    $locatie = trim($_POST['locatie']);
    $categorie = isset($_POST['categorie']) ? $_POST['categorie'] : 'cultural';
    $pret = isset($_POST['pret']) && is_numeric($_POST['pret']) ? floatval($_POST['pret']) : 0; 
    
    // Preluăm link-ul live
    $link_live = isset($_POST['este_live']) && !empty(trim($_POST['link_live'])) ? trim($_POST['link_live']) : NULL;

    // --- LOGICA PENTRU UPLOAD IMAGINE ---
    $cale_imagine_noua = null;
    if (isset($_FILES['imagine']) && $_FILES['imagine']['error'] === UPLOAD_ERR_OK) {
        $extensie = strtolower(pathinfo($_FILES["imagine"]["name"], PATHINFO_EXTENSION));
        $nume_nou = uniqid('ev_') . '.' . $extensie;
        $target_dir = "img/";
        if (!file_exists($target_dir)) { mkdir($target_dir, 0777, true); }
        $target_file = $target_dir . $nume_nou;
        
        if (in_array($extensie, ['jpg', 'jpeg', 'png', 'webp', 'gif'])) {
            if (move_uploaded_file($_FILES["imagine"]["tmp_name"], $target_file)) {
                $cale_imagine_noua = $target_file;
            }
        }
    }

    // Actualizăm baza de date
    if ($cale_imagine_noua !== null) {
        // Dacă a pus poză nouă
        $stmt_update = $conn->prepare("UPDATE evenimente SET titlu = ?, descriere = ?, data_eveniment = ?, locatie = ?, pret = ?, categorie = ?, imagine = ?, link_live = ? WHERE id = ?");
        $stmt_update->bind_param("ssssdsssi", $titlu, $descriere, $data_eveniment, $locatie, $pret, $categorie, $cale_imagine_noua, $link_live, $id);
    } else {
        // Dacă nu a pus poză nouă
        $stmt_update = $conn->prepare("UPDATE evenimente SET titlu = ?, descriere = ?, data_eveniment = ?, locatie = ?, pret = ?, categorie = ?, link_live = ? WHERE id = ?");
        $stmt_update->bind_param("ssssdssi", $titlu, $descriere, $data_eveniment, $locatie, $pret, $categorie, $link_live, $id);
    }
    
    if ($stmt_update->execute()) {
        $mesaj = "<div style='color: #155724; background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 8px; margin-bottom: 25px;'>✅ Evenimentul a fost actualizat cu succes!</div>";
    } else {
        $mesaj = "<div style='color: #721c24; background: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; border-radius: 8px; margin-bottom: 25px;'>❌ A apărut o eroare. Verifică baza de date.</div>";
    }
    $stmt_update->close();
}

// 2. PRELUARE DATE CURENTE PENTRU FORMULAR
$eveniment = null;
if (isset($_GET['id']) && is_numeric($_GET['id']) || isset($id)) {
    $id_cautat = isset($id) ? $id : intval($_GET['id']);
    $stmt = $conn->prepare("SELECT * FROM evenimente WHERE id = ?");
    $stmt->bind_param("i", $id_cautat);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows == 1) {
        $eveniment = $result->fetch_assoc();
    } else {
        die("<div style='text-align:center; padding: 120px 20px; min-height: 60vh;'><h2 style='color: var(--text-main);'>Evenimentul nu a fost găsit!</h2></div>");
    }
    $stmt->close();
}
?>

<section style="padding: 120px 20px 60px; max-width: 800px; margin: auto; min-height: 70vh;">
    <h2 style="margin-bottom: 20px; color: var(--text-main);">✏️ Editează Evenimentul</h2>
    
    <?= $mesaj ?>

    <?php if ($eveniment): ?>
    <form action="editeaza_eveniment.php?id=<?= $eveniment['id'] ?>" method="POST" enctype="multipart/form-data" class="modern-form" style="background: var(--card-bg); padding: 40px; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); border: 1px solid var(--border-color);">
        
        <input type="hidden" name="id" value="<?= $eveniment['id'] ?>">

        <div style="margin-bottom: 25px;">
            <label style="font-weight: 700; color: var(--text-main); display: block; margin-bottom: 12px; font-size: 16px;">Titlu Eveniment:</label>
            <input type="text" name="titlu" value="<?= htmlspecialchars($eveniment['titlu']) ?>" required style="width: 100%; padding: 12px 15px; border: 2px solid var(--border-color); border-radius: 10px; font-family: inherit; font-size: 15px; background: var(--bg-main); color: var(--text-main);">
        </div>

        <div style="margin-bottom: 25px; background: var(--bg-section); padding: 15px; border-radius: 10px; border: 1px solid var(--border-color); display: flex; align-items: center; gap: 20px;">
            <?php if (!empty($eveniment['imagine'])): ?>
                <img src="<?= htmlspecialchars($eveniment['imagine']) ?>" alt="Imagine curentă" style="width: 80px; height: 80px; object-fit: cover; border-radius: 8px; border: 2px solid var(--border-color);">
            <?php else: ?>
                <div style="width: 80px; height: 80px; background: var(--bg-main); border-radius: 8px; display: flex; align-items: center; justify-content: center; color: var(--text-main); opacity: 0.6; font-size: 12px;">Fără poză</div>
            <?php endif; ?>
            
            <div style="flex: 1;">
                <label style="font-weight: 700; color: var(--text-main); display: block; margin-bottom: 12px; font-size: 16px;">Schimbă Imaginea (Opțional):</label>
                <input type="file" name="imagine" accept="image/*" style="width: 100%; padding: 8px; background: var(--bg-main); border: 1px solid var(--border-color); border-radius: 8px; font-family: inherit; color: var(--text-main);">
                <small style="color: var(--text-main); opacity: 0.7;">Lasă gol dacă vrei să păstrezi imaginea actuală.</small>
            </div>
        </div>

        <div style="display: flex; gap: 20px; margin-bottom: 25px;">
            <div style="flex: 1;">
                <label style="font-weight: 700; color: var(--text-main); display: block; margin-bottom: 12px; font-size: 16px;">Data Evenimentului:</label>
                <input type="datetime-local" name="data_eveniment" value="<?= htmlspecialchars(date('Y-m-d\TH:i', strtotime($eveniment['data_eveniment']))) ?>" required style="width: 100%; padding: 12px 15px; border: 2px solid var(--border-color); border-radius: 10px; font-family: inherit; font-size: 15px; background: var(--bg-main); color: var(--text-main);">
            </div>

            <div style="flex: 1;">
                <label style="font-weight: 700; color: var(--text-main); display: block; margin-bottom: 12px; font-size: 16px;">Preț Bilet (RON):</label>
                <input type="number" step="0.01" min="0" name="pret" value="<?= htmlspecialchars($eveniment['pret'] ?? '0') ?>" style="width: 100%; padding: 12px 15px; border: 2px solid var(--border-color); border-radius: 10px; font-family: inherit; font-size: 15px; background: var(--bg-main); color: var(--text-main);">
                <small style="color: var(--text-main); opacity: 0.7; display: block; margin-top: 5px;">* Lasă 0 pentru Intrare Liberă</small>
            </div>
        </div>

        <div style="display: flex; gap: 20px; margin-bottom: 25px;">
            <div style="flex: 1;">
                <label style="font-weight: 700; color: var(--text-main); display: block; margin-bottom: 12px; font-size: 16px;">Categorie:</label>
                <select name="categorie" style="width: 100%; padding: 12px 15px; border: 2px solid var(--border-color); border-radius: 10px; font-family: inherit; font-size: 15px; outline: none; background: var(--bg-main); color: var(--text-main);">
                    <option value="cultural" <?= ($eveniment['categorie'] == 'cultural') ? 'selected' : '' ?>>Cultural</option>
                    <option value="sportiv" <?= ($eveniment['categorie'] == 'sportiv') ? 'selected' : '' ?>>Sportiv</option>
                </select>
            </div>
            
            <div style="flex: 1;">
                <label style="font-weight: 700; color: var(--text-main); display: block; margin-bottom: 12px; font-size: 16px;">Locație:</label>
                <input type="text" name="locatie" value="<?= htmlspecialchars($eveniment['locatie']) ?>" required style="width: 100%; padding: 12px 15px; border: 2px solid var(--border-color); border-radius: 10px; font-family: inherit; font-size: 15px; background: var(--bg-main); color: var(--text-main);">
            </div>
        </div>

        <div style="margin-bottom: 25px; padding: 15px; border: 2px dashed var(--accent-delete); border-radius: 10px; background: rgba(255,0,0,0.03);">
            <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; color: var(--text-main); font-weight: 700; font-size: 16px;">
                <input type="checkbox" name="este_live" id="checkLiveEdit" onchange="toggleLiveFieldEdit()" <?= !empty($eveniment['link_live']) ? 'checked' : '' ?> style="width: 20px; height: 20px; cursor: pointer;">
                🔴 Acest eveniment are o componentă LIVE (Bilet Online)
            </label>
            
            <div id="divLinkLiveEdit" style="display: <?= !empty($eveniment['link_live']) ? 'block' : 'none' ?>; margin-top: 15px;">
                <label style="font-weight: 700; color: var(--text-main); display: block; margin-bottom: 8px;">Link YouTube Live:</label>
                <input type="url" name="link_live" value="<?= htmlspecialchars($eveniment['link_live'] ?? '') ?>" placeholder="Ex: https://youtube.com/watch?v=..." style="width: 100%; padding: 12px 15px; border: 2px solid var(--border-color); border-radius: 10px; font-family: inherit; font-size: 15px; background: var(--bg-main); color: var(--text-main);">
                <small style="color: var(--text-main); opacity: 0.7; display: block; margin-top: 5px;">* Vizitatorii vor putea achiziționa bilet online pentru a vedea acest link.</small>
            </div>
        </div>
        <script>
            function toggleLiveFieldEdit() {
                var checkBox = document.getElementById("checkLiveEdit");
                var divLive = document.getElementById("divLinkLiveEdit");
                if (checkBox.checked == true) {
                    divLive.style.display = "block";
                } else {
                    divLive.style.display = "none";
                }
            }
        </script>

        <div style="margin-bottom: 30px;">
            <label style="font-weight: 700; color: var(--text-main); display: block; margin-bottom: 12px; font-size: 16px;">Descriere Detaliată:</label>
            <textarea name="descriere" rows="6" required style="width: 100%; padding: 15px; border: 2px solid var(--border-color); border-radius: 10px; font-family: inherit; font-size: 15px; resize: vertical; background: var(--bg-main); color: var(--text-main);"><?= htmlspecialchars($eveniment['descriere']) ?></textarea>
        </div>

        <div style="display: flex; justify-content: space-between; align-items: center; border-top: 2px solid var(--border-color); padding-top: 20px;">
            <a href="evenimentextins.php?id=<?= $eveniment['id'] ?>" style="color: var(--text-main); opacity: 0.8; text-decoration: none; font-weight: 600; transition: color 0.3s;">⬅️ Înapoi la Eveniment</a>
            <button type="submit" class="btn-submit-modern" style="background: #28a745; width: auto; padding: 12px 30px; margin-top: 0;">💾 Salvează Modificările</button>
        </div>
    </form>
    <?php endif; ?>
</section>

<?php include 'footer.php'; ?>