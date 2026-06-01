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
    .settings-header h2 { font-size: 32px; color: var(--text-main); }
    .settings-header p { color: var(--text-light); font-size: 16px; }

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
        background: var(--card-bg);
        padding: 35px 30px;
        border-radius: 20px;
        box-shadow: 0 10px 30px var(--shadow-light);
        border: 1px solid var(--border-light);
        color: var(--text-main);
    }

    .settings-card h3 {
        margin-top: 0;
        margin-bottom: 25px;
        color: var(--link-color);
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 20px;
        border-bottom: 2px solid var(--border-light);
        padding-bottom: 15px;
    }

    .theme-selector {
        display: flex;
        gap: 15px;
        flex-wrap: wrap;
        margin-top: 20px;
    }

    .theme-option {
        flex: 1;
        min-width: 120px;
        padding: 15px;
        border: 2px solid var(--border-light);
        border-radius: 12px;
        text-align: center;
        cursor: pointer;
        transition: all 0.3s ease;
        background: var(--card-bg);
        color: var(--text-main);
    }

    .theme-option:hover {
        border-color: var(--link-color);
        transform: translateY(-2px);
        box-shadow: 0 4px 12px var(--shadow-light);
    }

    .theme-option.active {
        border-color: var(--link-color);
        background: var(--link-color);
        color: white;
        box-shadow: 0 4px 15px rgba(0, 86, 179, 0.3);
    }

    .alert-msg {
        padding: 15px;
        border-radius: 10px;
        margin-bottom: 25px;
        font-weight: 500;
        text-align: center;
    }
    .alert-success { background: var(--success-bg); color: var(--success-text); border: 1px solid var(--success-border); }
    .alert-error { background: var(--error-bg); color: var(--error-text); border: 1px solid var(--error-border); }
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

    <!-- Tema / Appearance Settings - Full Width Section -->
    <div style="margin-top: 30px;">
        <div class="settings-card" style="grid-column: 1 / -1;">
            <h3>🎨 Preferințe Temă</h3>
            
            <p style="color: var(--text-light); margin-bottom: 20px;">Alege modul de afișare preferat:</p>
            
            <div class="theme-selector">
                <div class="theme-option" id="theme-light-btn" onclick="setTheme('light')">
                    <span style="font-size: 32px;">☀️</span>
                    <p style="margin: 10px 0 0 0; font-weight: bold;">Mod Luminos</p>
                    <small style="opacity: 0.7;">Fundal clar, text închis</small>
                </div>
                
                <div class="theme-option" id="theme-dark-btn" onclick="setTheme('dark')">
                    <span style="font-size: 32px;">🌙</span>
                    <p style="margin: 10px 0 0 0; font-weight: bold;">Mod Întunecat</p>
                    <small style="opacity: 0.7;">Fundal închis, text clar</small>
                </div>
            </div>

            <script>
                function setTheme(theme) {
                    document.documentElement.setAttribute('data-theme', theme);
                    localStorage.setItem('site_theme', theme);
                    updateThemeButtons(theme);
                }

                function updateThemeButtons(theme) {
                    document.getElementById('theme-light-btn').classList.remove('active');
                    document.getElementById('theme-dark-btn').classList.remove('active');
                    
                    if (theme === 'light') {
                        document.getElementById('theme-light-btn').classList.add('active');
                    } else {
                        document.getElementById('theme-dark-btn').classList.add('active');
                    }
                }

                // Inițializare la încărcarea paginii
                document.addEventListener('DOMContentLoaded', function() {
                    const currentTheme = document.documentElement.getAttribute('data-theme') || 'light';
                    updateThemeButtons(currentTheme);
                });
            </script>
        </div>
    </div>

<?php include 'footer.php'; ?>