<?php
require_once 'db_connect.php';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$este_admin = (isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin');

$sql = "SELECT * FROM blog WHERE id = $id AND tip_postare = 'articol'";
$result = $conn->query($sql);

if (!$result || $result->num_rows == 0) { header("Location: articole.php"); exit(); }
$articol = $result->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['adauga_comentariu'])) {
    if (isset($_SESSION['user_id'])) {
        $nume = !empty($_SESSION['nume']) ? $_SESSION['nume'] : 'Anonim';
        $mesaj = mysqli_real_escape_string($conn, $_POST['mesaj']);
        if(!empty($mesaj)) {
            $conn->query("INSERT INTO comentarii (articol_id, nume, mesaj) VALUES ($id, '$nume', '$mesaj')");
            header("Location: articol.php?id=$id"); exit();
        }
    }
}

$comentarii = [];
$res_coms = $conn->query("SELECT * FROM comentarii WHERE articol_id = $id ORDER BY data_adaugare DESC");
if ($res_coms) { while($r = $res_coms->fetch_assoc()) { $comentarii[] = $r; } }

$page_title = $articol['titlu'] . " | Descoperă Brăila";
include 'header.php';
?>

<style>
    /* === SLIDESHOW BACKGROUND === */
    html, body { margin: 0 !important; padding: 0 !important; background: #0A192F; }
    .slideshow-container { position: fixed; width: 100%; height: 100%; top: 0; left: 0; z-index: 0; }
    .mySlides { width: 100%; height: 100%; background-size: cover; background-position: center; background-repeat: no-repeat; position: absolute; opacity: 0; transition: opacity 1.5s ease-in-out; filter: blur(5px); transform: scale(1.05); }
    .mySlides.active { opacity: 1; }
    .overlay { position: fixed; width: 100%; height: 100%; top: 0; left: 0; background: rgba(10, 25, 47, 0.85); z-index: 1; } /* Foarte închis pt a citi textul ușor */

    /* === CONTINUT === */
    .articol-page { position: relative; z-index: 2; padding: 120px 20px 60px; max-width: 900px; margin: 0 auto; color: #fff; }
    
    .articol-img { width: 100%; height: 450px; object-fit: cover; border-radius: 16px; margin-bottom: 30px; border: 1px solid rgba(255,255,255,0.1); box-shadow: 0 10px 30px rgba(0,0,0,0.5); }
    .articol-titlu { font-size: 42px; font-weight: 800; color: #ffffff; margin-bottom: 10px; text-shadow: 0 4px 10px rgba(0,0,0,0.8); }
    .articol-meta { color: #8892b0; font-size: 15px; margin-bottom: 30px; display: block; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 15px; }
    .articol-continut { font-size: 18px; line-height: 1.8; color: #f8fafc; margin-bottom: 60px; white-space: pre-wrap; text-shadow: 0 1px 2px rgba(0,0,0,0.8); }
    
    .admin-bar { background: rgba(220, 53, 69, 0.1); padding: 15px 20px; border-radius: 10px; margin-bottom: 30px; display: flex; gap: 15px; border: 1px solid rgba(220, 53, 69, 0.3); align-items: center; backdrop-filter: blur(10px); }
    .admin-bar a { text-decoration: none; background: #dc3545; padding: 8px 15px; border-radius: 6px; font-weight: bold; color: #fff; transition: 0.2s; }
    .admin-bar a:hover { background: #c82333; }
    
    /* === COMENTARII === */
    .comentarii-sec { background: rgba(10, 25, 47, 0.6); padding: 35px; border-radius: 16px; border: 1px solid rgba(255, 255, 255, 0.15); box-shadow: 0 10px 30px rgba(0,0,0,0.4); backdrop-filter: blur(15px); -webkit-backdrop-filter: blur(15px);}
    .comentarii-sec h3 { color: #ffffff; margin-bottom: 25px; border-bottom: 2px solid #38bdf8; display: inline-block; padding-bottom: 8px; font-size: 24px; }
    
    .com-box { background: rgba(255,255,255,0.03); padding: 20px; border-radius: 12px; margin-bottom: 15px; border: 1px solid rgba(255,255,255,0.1); }
    .com-nume { font-weight: 800; color: #38bdf8; margin-bottom: 5px; display: block; font-size: 16px;}
    .com-data { font-size: 12px; color: #8892b0; font-weight: 500;}
    .com-text { margin-top: 8px; color: #e2e8f0; line-height: 1.5; }
    
    .form-comentariu textarea { width: 100%; padding: 15px; border-radius: 10px; background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.2); color: #fff; margin-bottom: 15px; resize: vertical; font-family: inherit; font-size: 16px;}
    .form-comentariu textarea:focus { border-color: #38bdf8; outline: none; background: rgba(0,0,0,0.5);}
    .form-comentariu button { background: #38bdf8; color: #0f172a; border: none; padding: 14px 25px; border-radius: 8px; font-weight: 800; cursor: pointer; transition: 0.3s; font-size: 16px;}
    .form-comentariu button:hover { background: #0ea5e9; transform: translateY(-2px); box-shadow: 0 4px 15px rgba(56,189,248,0.4); }

    .login-prompt { text-align: center; padding: 30px; background: rgba(56, 189, 248, 0.1); border-radius: 12px; border: 1px solid rgba(56, 189, 248, 0.3); margin-bottom: 30px; }
    .login-prompt p { color: #e2e8f0; font-size: 16px; }
    .login-prompt a { color: #38bdf8; font-weight: 800; text-decoration: none; }
    .login-prompt a:hover { text-decoration: underline; }
</style>

<div class="slideshow-container">
    <div class="mySlides active" style="background-image: url('img/braila1.jpg');"></div>
    <div class="mySlides" style="background-image: url('img/braila2.jpg');"></div>
    <div class="mySlides" style="background-image: url('img/braila3.jpg');"></div>
    <div class="mySlides" style="background-image: url('img/braila4.jpg');"></div>
</div>
<div class="overlay"></div>

<div class="articol-page fade-up-element">
    
    <?php if($este_admin): ?>
        <div class="admin-bar">
            <span style="color: #fff; font-weight: bold;">⚙️ Unelte Admin:</span>
            <a href="admin_articol.php?edit_id=<?= $id ?>">✏️ Editează Articolul</a>
        </div>
    <?php endif; ?>

    <h1 class="articol-titlu"><?= htmlspecialchars($articol['titlu']) ?></h1>
    <span class="articol-meta">Publicat la <?= date('d.m.Y H:i', strtotime($articol['data_creare'])) ?> • Scris de <?= htmlspecialchars($articol['sursa']) ?></span>
    
    <img src="img/<?= htmlspecialchars($articol['imagine']) ?>" class="articol-img" onerror="this.src='https://via.placeholder.com/900x400/112240/fff?text=Articol'">
    
    <div class="articol-continut"><?= htmlspecialchars($articol['continut']) ?></div>

    <div class="comentarii-sec">
        <h3>💬 Comentarii (<?= count($comentarii) ?>)</h3>
        
        <?php if (isset($_SESSION['user_id'])): ?>
            <form class="form-comentariu" method="POST" action="">
                <textarea name="mesaj" rows="4" placeholder="Scrie un comentariu public..." required></textarea>
                <button type="submit" name="adauga_comentariu">Postează Comentariul</button>
            </form>
        <?php else: ?>
            <div class="login-prompt">
                <p style="margin: 0;">Trebuie să fii <a href="#" onclick="openPopup('loginPopup'); return false;">autentificat</a> pentru a lăsa un comentariu.</p>
            </div>
        <?php endif; ?>

        <div style="margin-top: 40px;">
            <?php if(empty($comentarii)): ?>
                <p style="color: #8892b0; text-align: center; font-size: 16px;">Fii primul care lasă un comentariu!</p>
            <?php else: ?>
                <?php foreach($comentarii as $com): ?>
                    <div class="com-box">
                        <span class="com-nume"><?= htmlspecialchars($com['nume']) ?> <span class="com-data">• <?= date('d.m.Y', strtotime($com['data_adaugare'])) ?></span></span>
                        <p class="com-text"><?= htmlspecialchars($com['mesaj']) ?></p>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    let slideIndex = 0;
    const slides = document.querySelectorAll(".mySlides");
    function nextSlide() {
        if(slides.length === 0) return;
        slides[slideIndex].classList.remove("active");
        slideIndex = (slideIndex + 1) % slides.length;
        slides[slideIndex].classList.add("active");
    }
    if (slides.length > 0) { setInterval(nextSlide, 5000); }
</script>

<?php include 'footer.php'; ?>