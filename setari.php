<?php
session_start();
require_once 'db_connect.php';
$page_title = t('settings_title') . " | " . t('page_title');
include 'header.php';

// Verificăm dacă utilizatorul este logat
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$mesaj = '';
$tip_mesaj = ''; // 'success' sau 'error'

// PROCESARE FORMULARE
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // 1. Actualizare Profil
    if (isset($_POST['update_profile'])) {
        $nume_nou = trim($_POST['nume']);
        $email_nou = trim($_POST['email']);
        $telefon_nou = trim($_POST['telefon']);

        // Verificăm dacă email-ul nu este deja folosit de altcineva
        $check = $conn->prepare("SELECT id FROM utilizatori WHERE email = ? AND id != ?");
        $check->bind_param("si", $email_nou, $user_id);
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            $mesaj = t('msg_email_taken');
            $tip_mesaj = "error";
        } else {
            $stmt = $conn->prepare("UPDATE utilizatori SET nume = ?, email = ?, telefon = ? WHERE id = ?");
            $stmt->bind_param("sssi", $nume_nou, $email_nou, $telefon_nou, $user_id);
            if ($stmt->execute()) {
                // Actualizăm sesiunea cu noul nume pentru a se schimba direct în meniu!
                $_SESSION['nume'] = $nume_nou; 
                $mesaj = t('msg_profile_updated');
                $tip_mesaj = "success";
            } else {
                $mesaj = t('msg_update_error');
                $tip_mesaj = "error";
            }
            $stmt->close();
        }
        $check->close();
    }

    // 2. Actualizare Parolă
    if (isset($_POST['update_password'])) {
        $parola_veche = $_POST['parola_veche'];
        $parola_noua = $_POST['parola_noua'];
        $confirmare = $_POST['confirmare'];

        if ($parola_noua !== $confirmare) {
            $mesaj = t('msg_password_mismatch');
            $tip_mesaj = "error";
        } elseif (strlen($parola_noua) < 8) {
            $mesaj = t('msg_password_short');
            $tip_mesaj = "error";
        } else {
            // Preluăm parola veche din baza de date pentru a o verifica
            $stmt = $conn->prepare("SELECT parola FROM utilizatori WHERE id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($row = $result->fetch_assoc()) {
                // Presupunând că ai folosit password_hash() la înregistrare
                if (password_verify($parola_veche, $row['parola'])) {
                    $parola_hash = password_hash($parola_noua, PASSWORD_DEFAULT);
                    $update_pass = $conn->prepare("UPDATE utilizatori SET parola = ? WHERE id = ?");
                    $update_pass->bind_param("si", $parola_hash, $user_id);
                    if ($update_pass->execute()) {
                        $mesaj = t('msg_password_changed');
                        $tip_mesaj = "success";
                    }
                    $update_pass->close();
                } else {
                    $mesaj = t('msg_password_incorrect');
                    $tip_mesaj = "error";
                }
            }
            $stmt->close();
        }
    }
}

// PRELUARE DATE ACTUALE PENTRU FORMULAR
$stmt = $conn->prepare("SELECT nume, email, telefon FROM utilizatori WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_data = $stmt->get_result()->fetch_assoc();
$stmt->close();
?>

<style>
    .settings-container {
        padding: 120px 20px 60px;
        max-width: 1000px;
        margin: auto;
        min-height: 70vh;
    }
    .settings-header {
        margin-bottom: 40px;
        text-align: center;
    }
    .settings-header h2 { font-size: 32px; color: #333; }
    .settings-header p { color: #666; font-size: 16px; }

    .settings-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 30px;
    }

    /* Adaptabilitate pentru telefoane mobile */
    @media (max-width: 768px) {
        .settings-grid {
            grid-template-columns: 1fr;
        }
    }

    .settings-card {
        background: #fff;
        padding: 35px 30px;
        border-radius: 20px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.08);
        border: 1px solid #f0f0f0;
    }

    .settings-card h3 {
        margin-top: 0;
        margin-bottom: 25px;
        color: #0056b3;
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 20px;
        border-bottom: 2px solid #f8fafd;
        padding-bottom: 15px;
    }

    .alert-msg {
        padding: 15px;
        border-radius: 10px;
        margin-bottom: 25px;
        font-weight: 500;
        text-align: center;
    }
    .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
    .alert-error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
</style>

<section class="settings-container">
    <div class="settings-header">
        <h2>⚙️ <?= t('settings_title') ?></h2>
        <p><?= t('settings_profile') ?> - <?= t('settings_password') ?> - <?= t('settings_language') ?></p>
    </div>

    <?php if ($mesaj != ''): ?>
        <div class="alert-msg <?= $tip_mesaj == 'success' ? 'alert-success' : 'alert-error' ?>">
            <?= htmlspecialchars($mesaj) ?>
        </div>
    <?php endif; ?>

    <div class="settings-grid">
        
        <div class="settings-card">
            <h3>📝 <?= t('settings_profile') ?></h3>
            <form method="POST" action="setari.php" class="modern-form">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                
                <div class="form-group-modern">
                    <input type="text" name="nume" id="setNume" required placeholder=" " value="<?= htmlspecialchars($user_data['nume'] ?? '') ?>">
                    <label for="setNume"><?= t('settings_name') ?></label>
                </div>
                
                <div class="form-group-modern">
                    <input type="email" name="email" id="setEmail" required placeholder=" " value="<?= htmlspecialchars($user_data['email'] ?? '') ?>">
                    <label for="setEmail"><?= t('settings_email') ?></label>
                </div>

                <div class="form-group-modern">
                    <input type="text" name="telefon" id="setTelefon" required placeholder=" " value="<?= htmlspecialchars($user_data['telefon'] ?? '') ?>">
                    <label for="setTelefon"><?= t('settings_phone') ?></label>
                </div>
                
                <button type="submit" name="update_profile" class="btn-submit-modern" style="margin-top: 15px;"><?= t('settings_update') ?></button>
            </form>
        </div>

        <div class="settings-card">
            <h3>🔒 <?= t('settings_password') ?></h3>
            <form method="POST" action="setari.php" class="modern-form">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                
                <div class="form-group-modern">
                    <input type="password" name="parola_veche" id="setParolaVeche" required placeholder=" ">
                    <label for="setParolaVeche"><?= t('settings_old_password') ?></label>
                </div>
                
                <div class="form-group-modern">
                    <input type="password" name="parola_noua" id="setParolaNoua" required placeholder=" ">
                    <label for="setParolaNoua"><?= t('settings_new_password') ?> (min. 8)</label>
                </div>

                <div class="form-group-modern">
                    <input type="password" name="confirmare" id="setConfirmare" required placeholder=" ">
                    <label for="setConfirmare"><?= t('settings_confirm_password') ?></label>
                </div>
                
                <button type="submit" name="update_password" class="btn-submit-modern" style="background: #343a40; margin-top: 15px;"><?= t('settings_change_password') ?></button>
            </form>
        </div>

    </div>
</section>

<?php include 'footer.php'; ?>