<?php
session_start();
require_once 'db_connect.php';

// 1. Verificăm dacă utilizatorul este logat
if (!isset($_SESSION['user_id'])) {
    echo "<section style='margin-top: 150px; text-align: center; min-height: 50vh;'>
            <h2>Trebuie să fii autentificat pentru a cumpăra un bilet!</h2>
            <a href='transport.php' class='btn-submit-modern' style='display:inline-block; text-decoration:none;'>Înapoi</a>
          </section>";
    die();
}

$user_id = $_SESSION['user_id'];
$nume_utilizator = $_SESSION['nume'];

// 2. DETERMINĂM TIPUL DE BILET
$tip_bilet = 'bus'; // default
$id_eveniment = null;
$pret_bilet = 2.50;
$titlu_bilet = "Bilet Braicar (60 Min)";
$descriere_bilet = "Bilet pentru transport public 60 minute";
$url_inapoi = 'transport.php';

if (isset($_GET['id_eveniment']) || isset($_POST['id_eveniment'])) {
    $id_eveniment = intval(isset($_GET['id_eveniment']) ? $_GET['id_eveniment'] : $_POST['id_eveniment']);
    
    // Preluam detaliile evenimentului
    $stmt = $conn->prepare("SELECT titlu, pret FROM evenimente WHERE id = ?");
    $stmt->bind_param("i", $id_eveniment);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $eveniment = $result->fetch_assoc();
        $tip_bilet = 'eveniment';
        $pret_bilet = floatval($eveniment['pret']);
        $titlu_bilet = htmlspecialchars($eveniment['titlu']);
        $descriere_bilet = "Bilet pentru evenimentul: " . htmlspecialchars($eveniment['titlu']);
        $url_inapoi = 'evenimentextins.php?id=' . $id_eveniment;
    }
    $stmt->close();
}

$page_title = 'Cumpără ' . ($tip_bilet === 'eveniment' ? 'Bilet Eveniment' : 'Bilet Transport') . ' | Descoperă Brăila';
include 'header.php';

$pas = 0; // 0 = eroare/redirecționare, 1 = formular card, 2 = bilet generat
$mesaj_succes = false;
$cod_unic = '';
$data_achizitie = '';
$data_expirare = '';
$qr_image_url = '';

// 3. Gestionăm fluxul cererilor POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Verificăm mereu validitatea token-ului pentru securitate
    if (!isset($_POST['payment_token']) || !isset($_SESSION['payment_token']) || $_POST['payment_token'] !== $_SESSION['payment_token']) {
        header("Location: " . $url_inapoi);
        exit;
    }

    if (isset($_POST['finalizeaza_plata'])) {
        // PASUL 2: Utilizatorul a introdus datele cardului și a apăsat pe plată
        
        // Generăm datele reale pentru bilet
        if ($tip_bilet === 'eveniment') {
            $cod_unic = "BR-EV-" . strtoupper(substr(uniqid(), -5)) . rand(10,99);
            $data_expirare = null; // Valabil până la scanare
        } else {
            $cod_unic = "BR-BUS-" . strtoupper(substr(uniqid(), -5)) . rand(10,99);
            $data_expirare = date('Y-m-d H:i:s', strtotime('+60 minutes')); // Valabil 60 min
        }
        
        $data_achizitie = date('Y-m-d H:i:s');

        // Salvăm în baza de date - extindem tabelul bilete_achizitionate cu tipul de bilet și id_eveniment
        if ($tip_bilet === 'eveniment') {
            $stmt = $conn->prepare("INSERT INTO bilete_achizitionate (user_id, cod_qr_unic, data_achizitie, data_expirare, status, tip_bilet, id_eveniment) VALUES (?, ?, ?, NULL, 'activ', ?, ?)");
            $stmt->bind_param("isssi", $user_id, $cod_unic, $data_achizitie, $tip_bilet, $id_eveniment);
        } else {
            $stmt = $conn->prepare("INSERT INTO bilete_achizitionate (user_id, cod_qr_unic, data_achizitie, data_expirare, status, tip_bilet) VALUES (?, ?, ?, ?, 'activ', ?)");
            $stmt->bind_param("issss", $user_id, $cod_unic, $data_achizitie, $data_expirare, $tip_bilet);
        }
        
        if ($stmt->execute()) {
            $mesaj_succes = true;
            $qr_image_url = "https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=" . urlencode($cod_unic);
            $pas = 2;
            unset($_SESSION['payment_token']); // Invalidăm token-ul pentru a preveni dubla cumpărare
        } else {
            error_log("Eroare la inserare bilet: " . $stmt->error);
        }
        $stmt->close();

    } else {
        // PASUL 1: Utilizatorul vine din evenimentextins.php sau transport.php. Îi afișăm formularul de card.
        $_SESSION['payment_token'] = bin2hex(random_bytes(16));
        $pas = 1;
    }
} else if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['id_eveniment'])) {
    // Primim GET de la evenimentextins.php
    $_SESSION['payment_token'] = bin2hex(random_bytes(16));
    $pas = 1;
} else if ($_SERVER["REQUEST_METHOD"] != "POST") {
    // Dacă accesează pagina direct din URL (GET) fără parametri, îl trimitem înapoi
    header("Location: transport.php");
    exit;
}
?>

<style>
    .bilet-container { padding: 120px 20px 60px; max-width: 600px; margin: auto; min-height: 70vh; text-align: center;}
    
    /* Loading Spinner */
    .loading-plata { display: none; margin-top: 50px; }
    .spinner { border: 6px solid #f3f3f3; border-top: 6px solid #28a745; border-radius: 50%; width: 50px; height: 50px; animation: spin 1s linear infinite; margin: 0 auto 20px; }
    @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
    
    /* Design Bilet Fizic */
    .bilet-fizic { 
        background: #fff; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.15); 
        padding: 0; margin-top: 30px; overflow: hidden; border: 2px solid #ddd;
    }
    .bilet-header { background: #28a745; color: white; padding: 20px; font-size: 22px; font-weight: bold; }
    .bilet-body { padding: 30px; }
    .qr-box { margin: 20px 0; padding: 10px; border: 4px dashed #eee; display: inline-block; }
    .detalii-bilet { text-align: left; background: #f9f9f9; padding: 15px; border-radius: 8px; font-size: 15px; margin-top: 20px;}
    .detalii-bilet p { margin-bottom: 8px; border-bottom: 1px solid #eee; padding-bottom: 5px; }
    .detalii-bilet p:last-child { border-bottom: none; margin-bottom: 0; padding-bottom: 0; }
    
    .timer { font-size: 24px; font-weight: bold; color: #dc3545; margin-top: 15px; }
</style>

<section class="bilet-container">

    <?php if ($pas === 1): ?>
        <div class="modern-popup" style="background: #fff; box-shadow: 0 10px 30px rgba(0,0,0,0.1); border-radius: 20px; padding: 40px; text-align: left;">
            <h2 style="text-align: center; margin-bottom: 10px;">
                <?= ($tip_bilet === 'eveniment' ? t('payment_title_event') : t('payment_title_bus')) ?>
            </h2>
            <p class="popup-subtitle"><?= t('payment_total_price') ?> <strong><?= number_format($pret_bilet, 2) ?> RON</strong>.</p>
            
            <form method="POST" action="genereaza_bilet.php" class="modern-form">
                <input type="hidden" name="payment_token" value="<?= $_SESSION['payment_token'] ?>">
                <?php if ($tip_bilet === 'eveniment'): ?>
                    <input type="hidden" name="id_eveniment" value="<?= $id_eveniment ?>">
                <?php endif; ?>
                
                <div class="form-group-modern">
                    <input type="text" name="nume_card" id="nume_card" required placeholder=" " autocomplete="cc-name">
                    <label for="nume_card"><?= t('payment_card_name') ?></label>
                </div>
                
                <div class="form-group-modern">
                    <input type="text" name="numar_card" id="numar_card" required placeholder=" " maxlength="19" autocomplete="cc-number">
                    <label for="numar_card"><?= t('payment_card_number') ?></label>
                </div>
                
                <div style="display: flex; gap: 15px;">
                    <div class="form-group-modern" style="flex: 1;">
                        <input type="text" name="expirare" id="expirare" required placeholder=" " maxlength="5" autocomplete="cc-exp">
                        <label for="expirare"><?= t('payment_expiry') ?></label>
                    </div>
                    
                    <div class="form-group-modern" style="flex: 1;">
                        <input type="text" name="cvv" id="cvv" required placeholder=" " maxlength="3" autocomplete="cc-csc">
                        <label for="cvv"><?= t('payment_cvv') ?></label>
                    </div>
                </div>

                <div class="form-options" style="justify-content: center; margin-top: 10px;">
                    <small style="color: #28a745; font-weight: 600;"><?= t('payment_secure') ?></small>
                </div>
                
                <button type="submit" name="finalizeaza_plata" class="btn-submit-modern" style="background: #28a745; margin-top: 20px;">
                    <?= t('payment_pay_button') ?> <?= number_format($pret_bilet, 2) ?> RON
                </button>
            </form>
        </div>

    <?php elseif ($pas === 2 && $mesaj_succes): ?>
        <div id="loadingSec" class="loading-plata" style="display: block;">
            <div class="spinner"></div>
            <h3>Procesăm plata sigură...</h3>
            <p style="color: #666;">Te rugăm să aștepți confirmarea băncii.</p>
        </div>

        <div id="biletSec" style="display: none;">
            <h2 style="color: #28a745;">✅ Plată acceptată! Biletul tău a fost emis.</h2>
            
            <div class="bilet-fizic">
                <div class="bilet-header">
                    <?php if ($tip_bilet === 'eveniment'): ?>
                        🎫 Bilet Eveniment: <?= $titlu_bilet ?>
                    <?php else: ?>
                        🚌 Bilet Braicar (60 Min)
                    <?php endif; ?>
                </div>
                <div class="bilet-body">
                    <p style="font-size: 16px; color: #555;">Arată acest cod:</p>
                    
                    <div class="qr-box">
                        <img src="<?= $qr_image_url ?>" alt="Cod QR Bilet">
                    </div>
                    
                    <h3 style="margin-bottom: 10px; font-family: monospace; color: #333; letter-spacing: 2px;"><?= $cod_unic ?></h3>
                    
                    <?php if ($tip_bilet === 'bus'): ?>
                        <div class="timer">
                            Expiră în: <span id="countdown">60:00</span>
                        </div>
                    <?php endif; ?>

                    <div class="detalii-bilet">
                        <p><strong>Călător/Cumpărător:</strong> <?= htmlspecialchars($nume_utilizator) ?></p>
                        <p><strong>Emis la:</strong> <?= date('d/m/Y H:i', strtotime($data_achizitie)) ?></p>
                        <?php if ($tip_bilet === 'bus'): ?>
                            <p><strong>Valabil până la:</strong> <?= date('d/m/Y H:i', strtotime($data_expirare)) ?></p>
                        <?php else: ?>
                            <p><strong>Eveniment:</strong> <?= $titlu_bilet ?></p>
                        <?php endif; ?>
                        <p><strong>Preț:</strong> <?= number_format($pret_bilet, 2) ?> RON (Achitat Card)</p>
                    </div>
                </div>
            </div>
            
            <a href="<?= $url_inapoi ?>" class="btn-submit-modern" style="margin-top: 30px; display: inline-block; text-decoration: none; background: #6c757d;">Înapoi</a>
        </div>

        <script>
            // Simulăm timpul de procesare bancară de 2 secunde
            setTimeout(function() {
                document.getElementById('loadingSec').style.display = 'none';
                document.getElementById('biletSec').style.display = 'block';
                <?php if ($tip_bilet === 'bus'): ?>
                    startTimer();
                <?php endif; ?>
            }, 2000);

            function startTimer() {
                var expireTime = new Date("<?= date('Y/m/d H:i:s', strtotime($data_expirare)) ?>").getTime();
                
                var x = setInterval(function() {
                    var now = new Date().getTime();
                    var distance = expireTime - now;

                    var minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                    var seconds = Math.floor((distance % (1000 * 60)) / 1000);

                    minutes = minutes < 10 ? "0" + minutes : minutes;
                    seconds = seconds < 10 ? "0" + seconds : seconds;

                    document.getElementById("countdown").innerHTML = minutes + ":" + seconds;

                    if (distance < 0) {
                        clearInterval(x);
                        document.getElementById("countdown").innerHTML = "EXPIRAT";
                        document.getElementById("countdown").style.color = "gray";
                    }
                }, 1000);
            }
        </script>

    <?php else: ?>
        <div style="text-align: center; padding: 50px; background: #f9f9f9; border-radius: 15px;">
            <h3>Eroare la procesarea cererii</h3>
            <p style="color: #666; margin-bottom: 20px;">Ceva nu a funcționat corect. Te rugăm să încerci din nou.</p>
            <a href="<?= $url_inapoi ?>" class="btn-submit-modern" style="text-decoration: none; padding: 10px 20px;">Înapoi</a>
        </div>
    <?php endif; ?>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    
    const expirareInput = document.getElementById('expirare');
    if (expirareInput) {
        expirareInput.addEventListener('input', function (e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length > 2) {
                value = value.substring(0, 2) + '/' + value.substring(2, 4);
            }
            e.target.value = value;
        });
    }

    const numarCardInput = document.getElementById('numar_card');
    if (numarCardInput) {
        numarCardInput.addEventListener('input', function (e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length > 0) {
                value = value.match(/.{1,4}/g).join(' ');
            }
            e.target.value = value;
        });
    }
    
    const cvvInput = document.getElementById('cvv');
    if (cvvInput) {
        cvvInput.addEventListener('input', function (e) {
            e.target.value = e.target.value.replace(/\D/g, '');
        });
    }

    // NOU: Validare la trimiterea formularului
    const formPlata = document.querySelector('.modern-form');
    if (formPlata) {
        formPlata.addEventListener('submit', function(e) {
            let cardNumber = numarCardInput.value.replace(/\s/g, '');
            let expirare = expirareInput.value;
            let cvv = cvvInput.value;

            if (cardNumber.length !== 16) {
                e.preventDefault();
                alert('Te rugăm să introduci un număr de card valid de 16 cifre!');
                return;
            }

            if (expirare.length !== 5 || !expirare.includes('/')) {
                e.preventDefault();
                alert('Data de expirare trebuie să fie în formatul LL/AA (ex: 12/25)!');
                return;
            }

            let luna = parseInt(expirare.split('/')[0]);
            if (luna < 1 || luna > 12) {
                e.preventDefault();
                alert('Luna de expirare trebuie să fie între 01 și 12!');
                return;
            }

            if (cvv.length !== 3) {
                e.preventDefault();
                alert('Codul CVV trebuie să aibă exact 3 cifre!');
                return;
            }
        });
    }
});
</script>

<?php include 'footer.php'; ?>