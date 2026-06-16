<?php
session_start();
require_once 'db_connect.php';

// Dacă utilizatorul ESTE deja logat, îl trimitem automat pe site-ul principal
if (isset($_SESSION['user_id'])) {
    header("Location: acasa.php");
    exit();
}

$eroare = '';

// Procesarea formularului de Login
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['login_submit'])) {
    $email = trim($_POST['email']);
    $parola = $_POST['parola'];

    if (!empty($email) && !empty($parola)) {
        // SQL INJECTION FIX: Folosim Prepared Statements
        $stmt = $conn->prepare("SELECT id, nume, parola, rol FROM utilizatori WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows > 0) {
            $user = $result->fetch_assoc();
            if (password_verify($parola, $user['parola'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['nume'] = $user['nume'];
                $_SESSION['rol'] = $user['rol'];
                header("Location: acasa.php?action=login_success");
                exit();
            } else {
                $eroare = "Email sau parolă incorectă!";
            }
        } else {
            $eroare = "Utilizatorul nu există!";
        }
        $stmt->close();
    } else {
        $eroare = "Te rugăm să completezi toate câmpurile!";
    }
}

$register_msg = '';
$register_status = '';
$show_register = false;

if (isset($_GET['register'])) {
    $code = $_GET['register'];
    if ($code === 'succes') {
        $register_msg = 'Contul a fost creat cu succes! Te poți autentifica.';
        $register_status = 'success';
    } else {
        $show_register = true;
        $register_status = 'error';
        switch ($code) {
            case 'eroare_parole': $register_msg = 'Parolele nu se potrivesc!'; break;
            case 'eroare_parola_scurta': $register_msg = 'Parola trebuie să aibă minim 8 caractere!'; break;
            case 'eroare_duplicat': $register_msg = 'Există deja un cont cu acest email!'; break;
            default: $register_msg = 'A apărut o eroare la înregistrare.'; break;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="<?= getCurrentLanguage() ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= t('page_title') ?> | <?= t('settings_title') ?></title>
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
    <style>
        body, html { margin: 0; padding: 0; height: 100%; overflow: hidden; font-family: 'Poppins', sans-serif; background-color: transparent !important; }
        .slideshow-container { position: fixed; width: 100%; height: 100%; top: 0; left: 0; z-index: 0; }
        .mySlides { width: 100%; height: 100%; background-size: cover; background-position: center; background-repeat: no-repeat; position: absolute; opacity: 0; transition: opacity 1.5s ease-in-out; filter: blur(6px); transform: scale(1.05); }
        .mySlides.active { opacity: 1; }
        .overlay { position: fixed; width: 100%; height: 100%; top: 0; left: 0; background: linear-gradient(135deg, rgba(0,0,0,0.7) 0%, rgba(0,86,179,0.4) 100%); z-index: 1; }
        .login-page-wrapper { position: relative; z-index: 2; height: 100%; display: flex; justify-content: center; align-items: center; padding: 20px; }
        
        .glass-box {
            background: rgba(255, 255, 255, 0.1); backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px);
            border-radius: 24px; padding: 40px; width: 100%; max-width: 400px;
            border: 1px solid rgba(255, 255, 255, 0.2); box-shadow: 0 15px 35px rgba(0, 0, 0, 0.3); color: white; text-align: center;
        }
        .glass-box h2 { margin-bottom: 5px; font-size: 28px; font-weight: 700; color: white; }
        .glass-subtitle { color: rgba(255, 255, 255, 0.8); margin-bottom: 25px; font-size: 14px; }
        
        .glass-box .form-group-modern input { background: rgba(255, 255, 255, 0.1); border: 1px solid rgba(255, 255, 255, 0.2); color: white; }
        .glass-box .form-group-modern input:focus, .glass-box .form-group-modern input:not(:placeholder-shown) { border-color: #ffd700; background: rgba(255, 255, 255, 0.15); }
        .glass-box .form-group-modern label { color: rgba(255, 255, 255, 0.7); }
        .glass-box .form-group-modern input:focus + label, .glass-box .form-group-modern input:not(:placeholder-shown) + label { color: #ffd700; background: transparent; transform: translateY(-50%) scale(0.85) translateX(-10px); }
        
        .glass-box .popup-footer-text { margin-top: 20px; color: rgba(255, 255, 255, 0.8); font-size: 14px;}
        .glass-box .popup-footer-text a { color: #ffd700; font-weight: bold; cursor: pointer; text-decoration: none;}
        
        .form-container { display: none; animation: fadeIn 0.4s; }
        .form-container.active { display: block; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        
        .alert-error-glass { background: rgba(220, 53, 69, 0.4); border: 1px solid #dc3545; color: white; padding: 10px; border-radius: 10px; margin-bottom: 20px; font-size: 14px; }
        .alert-success-glass { background: rgba(40, 167, 69, 0.4); border: 1px solid #28a745; color: white; padding: 10px; border-radius: 10px; margin-bottom: 20px; font-size: 14px; }
        
        .btn-home { position: absolute; top: 20px; left: 20px; color: white; text-decoration: none; font-size: 16px; font-weight: 600; background: rgba(255,255,255,0.2); padding: 8px 15px; border-radius: 8px; backdrop-filter: blur(10px); z-index: 10; transition: 0.3s;}
        .btn-home:hover { background: rgba(255,255,255,0.4); }

        /* Stil special pentru telefon */
        .phone-input-wrapper {
            display: flex; align-items: center; background: rgba(255, 255, 255, 0.1); 
            border: 1px solid rgba(255, 255, 255, 0.2); border-radius: 12px; margin-bottom: 10px; text-align: left; transition: 0.3s;
        }
        .phone-input-wrapper:focus-within { border-color: #ffd700; background: rgba(255, 255, 255, 0.15); }
        .phone-prefix {
            padding: 14px 15px; color: white; font-weight: 600; border-right: 1px solid rgba(255, 255, 255, 0.2); font-size: 15px;
        }
        .phone-input-wrapper input {
            flex: 1; background: transparent; border: none; color: white; padding: 14px 15px; font-size: 15px; outline: none; font-family: inherit;
        }
        .phone-input-wrapper input::placeholder { color: rgba(255,255,255,0.5); }
    </style>
</head>
<body>

    <a href="acasa.php" class="btn-home">⬅️ Înapoi la Site</a>

    <div class="slideshow-container">
        <div class="mySlides active" style="background-image: url('img/braila1.jpg');"></div>
        <div class="mySlides" style="background-image: url('img/braila2.jpg');"></div>
        <div class="mySlides" style="background-image: url('img/braila3.jpg');"></div>
    </div>
    <div class="overlay"></div>

    <div class="login-page-wrapper">
        <div class="glass-box">
            
            <div id="login-section" class="form-container <?php echo !$show_register ? 'active' : ''; ?>">
                <h2>Descoperă Brăila ⚓</h2>
                <p class="glass-subtitle"><?= getCurrentLanguage() === 'ro' ? 'Autentifică-te pentru a continua' : 'Authenticate to continue' ?></p>
                
                <?php if (!empty($eroare)): ?>
                    <div class="alert-error-glass"><?= $eroare ?></div>
                <?php endif; ?>
                <?php if ($register_status === 'success'): ?>
                    <div class="alert-success-glass"><?= $register_msg ?></div>
                <?php endif; ?>
                
                <form method="POST" action="index.php" class="modern-form">
                    <div class="form-group-modern">
                        <input type="email" name="email" id="loginEmail" required placeholder=" ">
                        <label for="loginEmail">Email</label>
                    </div>
                    <div class="form-group-modern password-group">
                        <input type="password" name="parola" id="loginParola" required placeholder=" ">
                        <label for="loginParola"><?= getCurrentLanguage() === 'ro' ? 'Parolă' : 'Password' ?></label>
                    </div>
                    <button type="submit" name="login_submit" class="btn-submit-modern" style="background: #ffd700; color: #111; margin-top: 15px;"><?= getCurrentLanguage() === 'ro' ? 'Intră în cont' : 'Sign In' ?></button>
                </form>
                <p class="popup-footer-text"><?= getCurrentLanguage() === 'ro' ? 'Nu ai cont?' : 'Don\'t have an account?' ?> <a onclick="toggleForms()"><?= getCurrentLanguage() === 'ro' ? 'Înregistrează-te aici' : 'Register here' ?></a></p>
            </div>

            <div id="register-section" class="form-container <?php echo $show_register ? 'active' : ''; ?>">
                <h2><?= getCurrentLanguage() === 'ro' ? 'Cont Nou 🚀' : 'New Account 🚀' ?></h2>
                <p class="glass-subtitle"><?= getCurrentLanguage() === 'ro' ? 'Alătură-te comunității noastre' : 'Join our community' ?></p>
                
                <button type="button" class="btn-back-register" onclick="toggleForms()" style="background: transparent; color: #ffd700; border: none; cursor: pointer; margin-bottom: 15px; font-weight: 600; text-decoration: underline;">
                    ← <?= getCurrentLanguage() === 'ro' ? 'Înapoi la login' : 'Back to login' ?>
                </button>
                
                <?php if ($register_status === 'error'): ?>
                    <div class="alert-error-glass"><?= $register_msg ?></div>
                <?php endif; ?>
                
                <form method="POST" action="register.php" id="registerForm" class="modern-form">
                    <div class="form-group-modern">
                        <input type="text" name="nume" id="regNume" required placeholder=" ">
                        <label for="regNume">Nume Complet</label>
                    </div>
                    
                    <div class="form-group-modern">
                        <input type="email" name="email" id="regEmail" required placeholder=" ">
                        <label for="regEmail">Email</label>
                    </div>
                    
                    <div class="form-group-modern password-group" style="margin-bottom: 10px;">
                        <input type="password" name="parola" id="regParola" required placeholder=" ">
                        <label for="regParola"><?= getCurrentLanguage() === 'ro' ? 'Parolă' : 'Password' ?></label>
                    </div>
                    <div style="text-align: left; font-size: 12px; margin-bottom: 15px; font-weight: 600;">
                        <span id="rule-length" style="color: #ff4d4d; transition: 0.3s;">❌ Minim 8 caractere</span>
                    </div>

                    <div class="form-group-modern password-group">
                        <input type="password" name="confirmare" id="regConfirmare" required placeholder=" ">
                        <label for="regConfirmare"><?= getCurrentLanguage() === 'ro' ? 'Confirmă Parola' : 'Confirm Password' ?></label>
                    </div>
                    
                    <div class="phone-input-wrapper">
                        <div class="phone-prefix">🇷🇴 +40</div>
                        <input type="text" name="telefon" id="regTelefon" required placeholder="07XX XXX XXX" maxlength="12">
                    </div>

                    <div class="checkbox-modern" style="margin-top: 10px; text-align: left;">
                        <input type="checkbox" id="newsletter" name="newsletter" value="1" checked>
                        <label for="newsletter" style="font-size: 12px; color: rgba(255,255,255,0.8);"><?= getCurrentLanguage() === 'ro' ? 'Doresc să primesc noutăți pe email' : 'I want to receive news by email' ?></label>
                    </div>
                    <button type="submit" class="btn-submit-modern" style="background: #ffd700; color: #111; margin-top: 15px;"><?= getCurrentLanguage() === 'ro' ? 'Creează cont' : 'Create Account' ?></button>
                </form>
            </div>

        </div>
    </div>

    <script>
        let slideIndex = 0;
        const slides = document.querySelectorAll(".mySlides");
        function showSlides() {
            slides[slideIndex].classList.remove("active");
            slideIndex++;
            if (slideIndex >= slides.length) slideIndex = 0;
            slides[slideIndex].classList.add("active");
            setTimeout(showSlides, 5000);
        }
        if (slides.length > 0) setTimeout(showSlides, 5000);

        function toggleForms() {
            const loginSec = document.getElementById('login-section');
            const regSec = document.getElementById('register-section');
            if (loginSec.classList.contains('active')) {
                loginSec.classList.remove('active');
                regSec.classList.add('active');
            } else {
                regSec.classList.remove('active');
                loginSec.classList.add('active');
            }
        }

        // --- MAGIA PENTRU VALIDARE PAROLĂ LIVE ---
        const regParola = document.getElementById('regParola');
        const ruleLength = document.getElementById('rule-length');

        if (regParola) {
            regParola.addEventListener('input', function() {
                if (this.value.length >= 8) {
                    ruleLength.innerHTML = '✅ Minim 8 caractere';
                    ruleLength.style.color = '#10b981'; // Verde de succes
                } else {
                    ruleLength.innerHTML = '❌ Minim 8 caractere';
                    ruleLength.style.color = '#ff4d4d'; // Roșu de eroare
                }
            });
        }

        // --- MAGIA PENTRU FORMATUL DE TELEFON ---
        const regTelefon = document.getElementById('regTelefon');
        if (regTelefon) {
            regTelefon.addEventListener('input', function (e) {
                // Păstrăm doar cifrele
                let val = this.value.replace(/\D/g, '');
                
                // Dacă scrie, forțăm să înceapă cu 07
                if (val.length > 0 && val[0] !== '0') val = '0' + val;
                if (val.length > 1 && val[1] !== '7') val = val.substring(0, 1) + '7' + val.substring(2);

                // Formatăm cu spații: 07XX XXX XXX
                if (val.length > 4 && val.length <= 7) {
                    val = val.substring(0, 4) + ' ' + val.substring(4);
                } else if (val.length > 7) {
                    val = val.substring(0, 4) + ' ' + val.substring(4, 7) + ' ' + val.substring(7, 10);
                }
                this.value = val;
            });
        }

        // Prevenim trimiterea formularului dacă telefonul sau parola nu sunt complete
        const regForm = document.getElementById('registerForm');
        if(regForm) {
            regForm.addEventListener('submit', function(e) {
                if (regParola.value.length < 8) {
                    e.preventDefault();
                    alert('Parola trebuie să aibă minim 8 caractere!');
                    return;
                }
                // Verificăm dacă telefonul are 12 caractere (10 cifre + 2 spații)
                if (regTelefon.value.length !== 12) {
                    e.preventDefault();
                    alert('Te rugăm să introduci un număr de telefon valid din 10 cifre!');
                    return;
                }
            });
        }
    </script>
</body>
</html>