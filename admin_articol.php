<?php
require_once 'db_connect.php';
$page_title = "Admin Articol | Descoperă Brăila";
include 'header.php';

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') { header("Location: acasa.php"); exit(); }

$mesaj = ''; $tip_mesaj = '';
$edit_id = isset($_GET['edit_id']) ? (int)$_GET['edit_id'] : 0;

// Setăm un array gol pentru un articol nou (sursa va fi folosită pentru Nume Autor)
$articol_curent = ['titlu' => '', 'imagine' => '', 'continut' => '', 'sursa' => ''];

// Preia datele dacă edităm un articol existent
if ($edit_id > 0) {
    $result = $conn->query("SELECT * FROM blog WHERE id = $edit_id AND tip_postare = 'articol'");
    if ($result->num_rows > 0) { $articol_curent = $result->fetch_assoc(); }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['salveaza_articol'])) {
    $titlu = mysqli_real_escape_string($conn, $_POST['titlu']);
    $continut = mysqli_real_escape_string($conn, $_POST['continut']);
    $autor = mysqli_real_escape_string($conn, $_POST['autor']); // Noul câmp de autor
    
    // Păstrăm numele vechi al imaginii ca variantă de bază (în caz de editare fără a schimba poza)
    $nume_imagine = $edit_id > 0 ? $articol_curent['imagine'] : 'default.jpg';

    // LOGICA DE UPLOAD IMAGINE DIRECT DE PE SITE
    if (isset($_FILES['imagine_upload']) && $_FILES['imagine_upload']['error'] === UPLOAD_ERR_OK) {
        $nume_fisier_original = $_FILES['imagine_upload']['name'];
        $extensie = strtolower(pathinfo($nume_fisier_original, PATHINFO_EXTENSION));
        
        // Formate permise
        $formate_suportate = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
        
        if (in_array($extensie, $formate_suportate)) {
            // Generăm un nume unic pentru a nu suprascrie alte poze (Ex: articol_64a2b3.jpg)
            $nume_imagine = uniqid('articol_') . '.' . $extensie;
            $cale_finala = 'img/' . $nume_imagine;
            
            // Mutăm fișierul încărcat în folderul 'img'
            if (!move_uploaded_file($_FILES['imagine_upload']['tmp_name'], $cale_finala)) {
                $mesaj = "Eroare la mutarea imaginii în folderul img/.";
                $tip_mesaj = "error";
            }
        } else {
            $mesaj = "Te rugăm să încarci doar imagini (JPG, PNG, WEBP).";
            $tip_mesaj = "error";
        }
    }

    // Dacă nu au existat erori la încărcarea imaginii, salvăm în Baza de Date
    if (empty($tip_mesaj)) {
        if (!empty($titlu) && !empty($continut) && !empty($autor)) {
            if ($edit_id > 0) {
                // Actualizăm
                $sql = "UPDATE blog SET titlu='$titlu', continut='$continut', imagine='$nume_imagine', sursa='$autor' WHERE id=$edit_id";
                $mesaj = "Articol actualizat cu succes!";
            } else {
                // Inserăm un articol nou
                $sql = "INSERT INTO blog (titlu, continut, imagine, tip_postare, sursa) VALUES ('$titlu', '$continut', '$nume_imagine', 'articol', '$autor')";
                $mesaj = "Articol publicat cu succes!";
            }
            
            if ($conn->query($sql) === TRUE) {
                $tip_mesaj = "success";
                if($edit_id == 0) {
                    // Golim câmpurile după ce s-a publicat un articol nou cu succes
                    $articol_curent = ['titlu' => '', 'imagine' => '', 'continut' => '', 'sursa' => ''];
                }
            } else {
                $mesaj = "Eroare la salvare: " . $conn->error; 
                $tip_mesaj = "error";
            }
        } else {
            $mesaj = "Titlul, Autorul și Conținutul sunt obligatorii!"; 
            $tip_mesaj = "error";
        }
    }
}
?>

<style>
    .admin-page { padding: 140px 20px 60px; min-height: 100vh; color: var(--text-main); display: flex; justify-content: center; background: var(--bg-main); }
    .glass-box-admin { background: rgba(255, 255, 255, 0.05); backdrop-filter: blur(20px); border-radius: 24px; padding: 40px; width: 100%; max-width: 800px; border: 1px solid rgba(255, 255, 255, 0.1); box-shadow: 0 15px 35px rgba(0,0,0,0.3); }
    .glass-box-admin h2 { margin-bottom: 30px; font-size: 28px; color: var(--accent-primary); text-align: center; }
    
    .admin-group { display: flex; flex-direction: column; margin-bottom: 20px; }
    .admin-group label { margin-bottom: 8px; color: var(--accent-primary); font-weight: 600; font-size: 15px; }
    .admin-group input, .admin-group textarea {
        width: 100%; padding: 15px; font-size: 15px; border: 1px solid rgba(255, 255, 255, 0.2);
        border-radius: 12px; outline: none; background: rgba(0, 0, 0, 0.5); color: white;
        font-family: 'Poppins', sans-serif;
    }
    
    /* Design special pentru input-ul de fișier */
    .admin-group input[type="file"] {
        padding: 10px;
        background: rgba(255, 255, 255, 0.05);
        color: var(--text-light);
        border: 1px dashed rgba(255, 215, 0, 0.5);
        cursor: pointer;
    }
    
    .admin-group input:focus, .admin-group textarea:focus { border-color: var(--accent-primary); background: rgba(0, 0, 0, 0.8); }
    
    .alert { padding: 15px; border-radius: 10px; margin-bottom: 20px; font-weight: bold; text-align: center; }
    .alert-success { background: rgba(40, 167, 69, 0.2); color: var(--accent-success); border: 1px solid var(--accent-success); }
    .alert-error { background: rgba(220, 53, 69, 0.2); color: var(--accent-delete); border: 1px solid var(--accent-delete); }
    .btn-submit-modern { background: var(--accent-success); color: white; border: none; padding: 15px; width: 100%; border-radius: 12px; font-size: 18px; font-weight: bold; cursor: pointer; transition: 0.3s; }
    .btn-submit-modern:hover { background: var(--accent-success-dark); }
</style>

<div class="admin-page">
    <div class="glass-box-admin">
        <h2><?= $edit_id > 0 ? '✏️ Editează Articolul' : '✍️ Adaugă Articol Original' ?></h2>
        
        <?php if (!empty($mesaj)): ?>
            <div class="alert alert-<?= $tip_mesaj ?>"><?= $mesaj ?></div>
        <?php endif; ?>

        <form method="POST" action="" enctype="multipart/form-data">
            
            <div class="admin-group">
                <label>Nume Autor</label>
                <input type="text" name="autor" required placeholder="Ex: Echipa Descoperă Brăila / Numele tău" value="<?= htmlspecialchars($articol_curent['sursa']) ?>">
            </div>

            <div class="admin-group">
                <label>Titlul Articolului</label>
                <input type="text" name="titlu" required placeholder="Ex: Misterele orașului subteran..." value="<?= htmlspecialchars($articol_curent['titlu']) ?>">
            </div>

            <div class="admin-group">
                <label>Încarcă O Imagine (Opțional)</label>
                <input type="file" name="imagine_upload" accept="image/png, image/jpeg, image/jpg, image/webp">
                
                <?php if ($edit_id > 0 && !empty($articol_curent['imagine']) && $articol_curent['imagine'] !== 'default.jpg'): ?>
                    <small style="margin-top: 8px; color: var(--text-light);">Imagine curentă: <strong style="color: var(--text-main);"><?= htmlspecialchars($articol_curent['imagine']) ?></strong>. Încarcă alta doar dacă dorești să o schimbi.</small>
                <?php endif; ?>
            </div>

            <div class="admin-group">
                <label>Conținutul Articolului</label>
                <textarea name="continut" rows="10" required placeholder="Scrie aici povestea..."><?= htmlspecialchars($articol_curent['continut']) ?></textarea>
            </div>

            <button type="submit" name="salveaza_articol" class="btn-submit-modern"><?= $edit_id > 0 ? '🔄 Actualizează Articolul' : '✅ Publică Articolul' ?></button>
            <div style="text-align: center; margin-top: 15px;">
                <a href="articole.php" style="color: var(--text-light); text-decoration: none;">← Întoarce-te la lista de articole</a>
            </div>
        </form>
    </div>
</div>
<?php include 'footer.php'; ?>