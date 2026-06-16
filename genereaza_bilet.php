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
$tip_bilet_vehicul = 'bus'; 
$tip_bilet_achizitie = 'fizic'; 
$id_eveniment = null;
$pret_bilet = 3.00; // 3 Lei pentru transport
$titlu_bilet = "Bilet Braicar (60 Min)";
$descriere_bilet = "Bilet pentru transport public 60 minute";
$url_inapoi = 'transport.php';
$bg_image = 'img/braila1.jpg'; // Fundalul implicit pentru Transport

// Prelucrăm parametrul `tip`
if (isset($_GET['tip']) && in_array($_GET['tip'], ['fizic', 'online'])) {
    $tip_bilet_achizitie = $_GET['tip'];
} elseif (isset($_POST['tip_bilet_achizitie'])) {
     $tip_bilet_achizitie = $_POST['tip_bilet_achizitie'];
}

if (isset($_GET['id_eveniment']) || isset($_POST['id_eveniment'])) {
    $id_eveniment = intval(isset($_GET['id_eveniment']) ? $_GET['id_eveniment'] : $_POST['id_eveniment']);
    
    $stmt = $conn->prepare("SELECT titlu, pret, categorie FROM evenimente WHERE id = ?");
    $stmt->bind_param("i", $id_eveniment);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $eveniment = $result->fetch_assoc();
        $tip_bilet_vehicul = 'eveniment';
        $pret_bilet = floatval($eveniment['pret']);
        
        // Logica pentru schimbarea fundalului în funcție de categorie
        $categorie = strtolower($eveniment['categorie']);
        if (strpos($categorie, 'sport') !== false) {
            $bg_image = 'img/sport.jpg'; 
        } elseif (strpos($categorie, 'cultur') !== false || strpos($categorie, 'teatru') !== false) {
            $bg_image = 'img/cultura.jpg'; 
        } else {
            $bg_image = 'img/eveniment_default.jpg'; 
        }
        
        if ($tip_bilet_achizitie === 'online') {
            $pret_bilet += 15;
            $titlu_bilet = htmlspecialchars($eveniment['titlu']) . " [LIVE ONLINE]";
            $descriere_bilet = "Acces Live Online: " . htmlspecialchars($eveniment['titlu']);
        } else {
            $titlu_bilet = htmlspecialchars($eveniment['titlu']) . " [ACCES SALĂ]";
            $descriere_bilet = "Acces Fizic Eveniment: " . htmlspecialchars($eveniment['titlu']);
        }

        $url_inapoi = 'evenimentextins.php?id=' . $id_eveniment;
    }
    $stmt->close();
}

$page_title = 'Cumpără ' . ($tip_bilet_vehicul === 'eveniment' ? 'Bilet Eveniment' : 'Bilet Transport') . ' | Descoperă Brăila';
include 'header.php';

$pas = 0; 
$mesaj_succes = false;
$cod_unic = '';
$data_achizitie = '';
$data_expirare = '';
$qr_image_url = '';

// 3. Gestionăm fluxul cererilor POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    if (!isset($_POST['payment_token']) || !isset($_SESSION['payment_token']) || $_POST['payment_token'] !== $_SESSION['payment_token']) {
        header("Location: " . $url_inapoi);
        exit;
    }

    if (isset($_POST['finalizeaza_plata'])) {
        if ($tip_bilet_vehicul === 'eveniment') {
            $cod_unic = "BR-EV-" . strtoupper(substr(uniqid(), -5)) . rand(10,99);
            $data_expirare = null; 
        } else {
            $cod_unic = "BR-BUS-" . strtoupper(substr(uniqid(), -5)) . rand(10,99);
            $data_expirare = date('Y-m-d H:i:s', strtotime('+60 minutes'));
        }
        
        $data_achizitie = date('Y-m-d H:i:s');

        if ($tip_bilet_vehicul === 'eveniment') {
            $stmt = $conn->prepare("INSERT INTO bilete_achizitionate (user_id, cod_qr_unic, data_achizitie, data_expirare, status, tip_bilet, id_eveniment) VALUES (?, ?, ?, NULL, 'activ', ?, ?)");
            $stmt->bind_param("isssi", $user_id, $cod_unic, $data_achizitie, $tip_bilet_achizitie, $id_eveniment);
        } else {
            $stmt = $conn->prepare("INSERT INTO bilete_achizitionate (user_id, cod_qr_unic, data_achizitie, data_expirare, status, tip_bilet) VALUES (?, ?, ?, ?, 'activ', ?)");
            $stmt->bind_param("issss", $user_id, $cod_unic, $data_achizitie, $data_expirare, $tip_bilet_vehicul);
        }
        
        if ($stmt->execute()) {
            $mesaj_succes = true;
            if ($tip_bilet_achizitie !== 'online') {
                $qr_image_url = "https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=" . urlencode($cod_unic);
            }
            $pas = 2;
            unset($_SESSION['payment_token']);
        }
        $stmt->close();

    } else {
        $_SESSION['payment_token'] = bin2hex(random_bytes(16));
        $pas = 1;
    }
} else if ($_SERVER["REQUEST_METHOD"] == "GET" && (isset($_GET['id_eveniment']) || isset($_GET['bus']))) {
    $_SESSION['payment_token'] = bin2hex(random_bytes(16));
    $pas = 1;
} else if ($_SERVER["REQUEST_METHOD"] != "POST") {
    header("Location: transport.php");
    exit;
}
?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>

<style>
    /* 1. FORȚĂM RESETAREA FUNDALURILOR GLOBALE DIN style.css */
    body, html, main, .main-content, .container, .wrapper { 
        background-color: transparent !important; 
        background: transparent !important;
    }

    /* 2. POZA DE FUNDAL PE TOT ECRANUL */
    .bg-image-fullscreen {
        position: fixed !important; top: 0 !important; left: 0 !important; 
        width: 100vw !important; height: 100vh !important; z-index: -5 !important;
        background-image: url('<?= $bg_image ?>') !important; 
        background-size: cover !important; background-position: center !important;
        filter: blur(8px); transform: scale(1.1);
    }
    
    /* 3. STRATUL ÎNTUNECAT PESTE POZĂ */
    .overlay-fullscreen { 
        position: fixed !important; top: 0 !important; left: 0 !important; 
        width: 100vw !important; height: 100vh !important; z-index: -4 !important;
        background: rgba(15, 23, 42, 0.6) !important; /* Un navy transparent fin */
    }

    /* 4. CONTAINERUL DE BAZĂ AL PAGINII DE PLATĂ */
    .plata-wrapper-absolut { 
        position: relative; z-index: 10;
        padding: 120px 20px 60px; max-width: 450px; margin: 0 auto; 
        display: flex; flex-direction: column; justify-content: center; min-height: 75vh;
    }

    /* 5. CUTIA DE STICLĂ (GLASSMORPHISM REAL) */
    .sticla-premium {
        background: rgba(30, 41, 59, 0.6) !important; /* Gri-albastru transparent */
        backdrop-filter: blur(25px) !important; -webkit-backdrop-filter: blur(25px) !important;
        border: 1px solid rgba(255, 255, 255, 0.15) !important;
        box-shadow: 0 20px 50px rgba(0, 0, 0, 0.5) !important;
        border-radius: 24px !important; padding: 40px !important; 
        color: #fff !important;
    }

    .sticla-premium h2 { margin: 0 0 5px 0; color: #fff !important; font-size: 24px; text-align: center; }
    .sticla-premium .subtitlu { text-align: center; color: rgba(255,255,255,0.7); font-size: 13px; margin-bottom: 20px; }
    .sticla-premium .pret-mare { text-align: center; font-size: 40px; font-weight: bold; color: #10b981; margin-bottom: 25px; text-shadow: 0 2px 10px rgba(16,185,129,0.3); }
    .sticla-premium .pret-mare span { font-size: 16px; color: rgba(255,255,255,0.5); font-weight: normal; }

    /* INPUTURI TRANSPARENTE */
    .input-grup { margin-bottom: 18px; }
    .input-grup label { display: block; margin-bottom: 8px; font-size: 13px; color: rgba(255, 255, 255, 0.8); }
    .input-sticla {
        width: 100%; box-sizing: border-box; padding: 14px 16px !important; border-radius: 12px !important;
        background: rgba(0, 0, 0, 0.4) !important; /* Negru transparent */
        border: 1px solid rgba(255, 255, 255, 0.1) !important;
        color: white !important; font-size: 15px !important; outline: none !important; transition: 0.3s;
        font-family: inherit;
    }
    .input-sticla:focus { border-color: #10b981 !important; background: rgba(0, 0, 0, 0.6) !important; }
    .input-sticla::placeholder { color: rgba(255,255,255,0.3) !important; }
    
    .buton-verde-sticla {
        width: 100%; padding: 16px; border-radius: 12px; background: #10b981 !important; color: white !important;
        border: none; font-size: 18px; font-weight: bold; cursor: pointer; transition: 0.3s;
        margin-top: 10px; box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3) !important; font-family: inherit;
    }
    .buton-verde-sticla:hover { background: #059669 !important; transform: translateY(-2px); }
    
    .link-inapoi { display: block; text-align: center; margin-top: 20px; color: rgba(255,255,255,0.6) !important; text-decoration: none; font-size: 13px; transition: 0.2s;}
    .link-inapoi:hover { color: #fff !important; }

    /* LOADING & BILET GENERAT */
    .loading-plata { display: none; text-align: center; margin-top: 20px; color: white;}
    .spinner { border: 5px solid rgba(255,255,255,0.1); border-top: 5px solid #10b981; border-radius: 50%; width: 50px; height: 50px; animation: spin 1s linear infinite; margin: 0 auto 20px; }
    @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }

    .bilet-print { background: #fff; border-radius: 16px; overflow: hidden; color: #111; text-align: left; margin-top: 20px;}
    .bilet-print-header { background: #10b981; color: white; padding: 20px; font-size: 20px; font-weight: bold; text-align: center;}
    .bilet-print-body { padding: 30px; text-align: center;}
    .qr-box { display: inline-block; padding: 10px; border: 3px dashed #ccc; margin: 15px 0;}
    .detalii-bilet { background: #f8f9fa; padding: 15px; border-radius: 8px; text-align: left; font-size: 14px;}
    .detalii-bilet p { margin: 0 0 8px 0; border-bottom: 1px solid #eee; padding-bottom: 5px; }
    .detalii-bilet p:last-child { border: none; margin: 0; padding: 0; }
</style>

<div class="bg-image-fullscreen"></div>
<div class="overlay-fullscreen"></div>

<div class="plata-wrapper-absolut">

    <?php if ($pas === 1): ?>
        <div class="sticla-premium">
            <h2><?= ($tip_bilet_vehicul === 'eveniment' ? 'Bilet Eveniment' : 'Bilet Transport') ?></h2>
            <div class="subtitlu">Tip Bilet: <strong><?= strtoupper($tip_bilet_achizitie) ?></strong></div>
            
            <div class="pret-mare">
                <?= number_format($pret_bilet, 2) ?> <span>RON</span>
            </div>
            
            <form method="POST" action="generare_bilet.php">
                <input type="hidden" name="payment_token" value="<?= $_SESSION['payment_token'] ?>">
                <input type="hidden" name="tip_bilet_achizitie" value="<?= $tip_bilet_achizitie ?>">
                <?php if ($tip_bilet_vehicul === 'eveniment'): ?>
                    <input type="hidden" name="id_eveniment" value="<?= $id_eveniment ?>">
                <?php endif; ?>
                
                <div class="input-grup">
                    <label>Numele de pe card</label>
                    <input type="text" name="nume_card" id="nume_card" class="input-sticla" required placeholder="Ex: ION POPESCU" autocomplete="cc-name">
                </div>
                
                <div class="input-grup">
                    <label>Numărul cardului</label>
                    <input type="text" name="numar_card" id="numar_card" class="input-sticla" required placeholder="0000 0000 0000 0000" maxlength="19" autocomplete="cc-number">
                </div>
                
                <div style="display: flex; gap: 15px;">
                    <div class="input-grup" style="flex: 1;">
                        <label>Expirare (LL/AA)</label>
                        <input type="text" name="expirare" id="expirare" class="input-sticla" required placeholder="12/26" maxlength="5" autocomplete="cc-exp">
                    </div>
                    
                    <div class="input-grup" style="flex: 1;">
                        <label>CVV</label>
                        <input type="password" name="cvv" id="cvv" class="input-sticla" required placeholder="123" maxlength="3" autocomplete="cc-csc">
                    </div>
                </div>

                <div style="text-align: center; margin: 5px 0 15px 0;">
                    <small style="color: #10b981; font-weight: 600;">🔒 Plată 100% Securizată</small>
                </div>
                
                <button type="submit" name="finalizeaza_plata" class="buton-verde-sticla">
                    Plătește <?= number_format($pret_bilet, 2) ?> RON
                </button>
                
                <a href="<?= $url_inapoi ?>" class="link-inapoi">← Anulează și întoarce-te</a>
            </form>
        </div>

    <?php elseif ($pas === 2 && $mesaj_succes): ?>
        <div id="loadingSec" class="loading-plata" style="display: block;">
            <div class="spinner"></div>
            <h3>Procesăm plata sigură...</h3>
            <p style="color: rgba(255,255,255,0.7);">Te rugăm să aștepți confirmarea băncii.</p>
        </div>

        <div id="biletSec" style="display: none;">
            <div class="sticla-premium" style="text-align: center; padding: 30px !important;">
                <h2 style="color: #10b981 !important; margin-bottom: 20px;">✅ Plată acceptată!</h2>
                <p style="color: rgba(255,255,255,0.8);">Biletul tău a fost emis cu succes.</p>
                
                <div class="bilet-print" id="bilet-de-printat">
                    <div class="bilet-print-header">
                        <?php if ($tip_bilet_vehicul === 'eveniment'): ?>
                            Bilet <?= ucfirst($tip_bilet_achizitie) ?> <br> <span style="font-size:14px; font-weight:normal;"><?= $titlu_bilet ?></span>
                        <?php else: ?>
                            Bilet Braicar (60 Min)
                        <?php endif; ?>
                    </div>
                    
                    <div class="bilet-print-body">
                        <?php if ($tip_bilet_achizitie === 'online'): ?>
                            <div style="margin-bottom: 20px; padding: 15px; background: rgba(0, 123, 255, 0.1); border: 2px dashed #007bff; border-radius: 8px;">
                                <h3 style="color: #007bff; margin: 0 0 10px 0;">Live Stream</h3>
                                <a href="<?= $url_inapoi ?>" style="display: inline-block; background: #007bff; color: white; text-decoration: none; padding: 10px 20px; border-radius: 6px; font-weight:bold;">Intră în Sala Virtuală</a>
                            </div>
                        <?php else: ?>
                            <p style="margin: 0 0 10px 0; font-size: 14px;">Arată acest cod la control:</p>
                            <div class="qr-box">
                                <img src="<?= $qr_image_url ?>" alt="Cod QR Bilet">
                            </div>
                        <?php endif; ?>
                        
                        <h3 style="margin: 0 0 15px 0; font-family: monospace; letter-spacing: 2px; color: #111;"><?= $cod_unic ?></h3>
                        
                        <?php if ($tip_bilet_vehicul === 'bus'): ?>
                            <div style="font-size: 22px; font-weight: bold; color: #dc3545; margin-bottom: 15px;">
                                Expiră în: <span id="countdown">60:00</span>
                            </div>
                        <?php endif; ?>

                        <div class="detalii-bilet">
                            <p><strong>Călător:</strong> <?= htmlspecialchars($nume_utilizator) ?></p>
                            <p><strong>Emis la:</strong> <?= date('d/m/Y H:i', strtotime($data_achizitie)) ?></p>
                            <?php if ($tip_bilet_vehicul === 'bus'): ?>
                                <p><strong>Valabil până la:</strong> <?= date('d/m/Y H:i', strtotime($data_expirare)) ?></p>
                            <?php else: ?>
                                <p><strong>Eveniment:</strong> <?= $titlu_bilet ?></p>
                            <?php endif; ?>
                            <p><strong>Preț:</strong> <?= number_format($pret_bilet, 2) ?> RON</p>
                        </div>
                    </div>
                </div>
                
                <div style="margin-top: 25px; display: flex; gap: 10px; justify-content: center; flex-wrap: wrap;">
                    <button onclick="descarcaPDF()" style="background: #dc3545; color:white; border:none; padding:12px 20px; border-radius:8px; font-weight:bold; cursor:pointer;">Descarcă PDF</button>
                    <a href="<?= $url_inapoi ?>" style="background: rgba(255,255,255,0.2); color:white; text-decoration:none; padding:12px 20px; border-radius:8px; font-weight:bold;">Înapoi</a>
                </div>
            </div>
        </div>

        <script>
            setTimeout(function() {
                document.getElementById('loadingSec').style.display = 'none';
                document.getElementById('biletSec').style.display = 'block';
                
                <?php if ($tip_bilet_vehicul === 'bus'): ?>
                    startTimer();
                <?php endif; ?>
            }, 2000);

            function descarcaPDF() {
                var element = document.getElementById('bilet-de-printat');
                var opt = {
                  margin:       10,
                  filename:     'Bilet_Brăila_<?= $cod_unic ?>.pdf',
                  image:        { type: 'jpeg', quality: 0.98 },
                  html2canvas:  { scale: 2, useCORS: true },
                  jsPDF:        { unit: 'mm', format: 'a4', orientation: 'portrait' }
                };
                
                element.style.boxShadow = 'none';
                element.style.borderRadius = '0';

                html2pdf().set(opt).from(element).save().then(() => {
                    element.style.boxShadow = 'none';
                    element.style.borderRadius = '16px';
                });
            }

            function startTimer() {
                var expireTime = new Date("<?= date('Y/m/d H:i:s', strtotime($data_expirare ?? 'now')) ?>").getTime();
                var x = setInterval(function() {
                    var now = new Date().getTime();
                    var distance = expireTime - now;

                    var minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                    var seconds = Math.floor((distance % (1000 * 60)) / 1000);

                    minutes = minutes < 10 ? "0" + minutes : minutes;
                    seconds = seconds < 10 ? "0" + seconds : seconds;

                    if(document.getElementById("countdown")) {
                        document.getElementById("countdown").innerHTML = minutes + ":" + seconds;
                    }

                    if (distance < 0) {
                        clearInterval(x);
                        if(document.getElementById("countdown")) {
                            document.getElementById("countdown").innerHTML = "EXPIRAT";
                            document.getElementById("countdown").style.color = "gray";
                        }
                    }
                }, 1000);
            }
        </script>

    <?php else: ?>
        <div class="sticla-premium" style="text-align: center;">
            <h3 style="color: white; margin-top:0;">Eroare la procesarea cererii</h3>
            <p style="color: rgba(255,255,255,0.7); margin-bottom: 20px;">Ceva nu a funcționat corect. Te rugăm să încerci din nou.</p>
            <a href="<?= $url_inapoi ?>" style="text-decoration: none; padding: 10px 20px; display:inline-block; background: #38bdf8; color: #111; border-radius: 8px; font-weight: bold;">Înapoi</a>
        </div>
    <?php endif; ?>
</div>

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

    const formPlata = document.querySelector('form');
    if (formPlata) {
        formPlata.addEventListener('submit', function(e) {
            let cardNumber = numarCardInput ? numarCardInput.value.replace(/\s/g, '') : '';
            let expirare = expirareInput ? expirareInput.value : '';
            let cvv = cvvInput ? cvvInput.value : '';

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