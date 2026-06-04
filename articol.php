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
    .articol-page { padding: 120px 20px 60px; max-width: 900px; margin: 0 auto; color: var(--text-main); }
    .articol-img { width: 100%; height: 400px; object-fit: cover; border-radius: 16px; margin-bottom: 30px; border: 1px solid var(--border-color); }
    .articol-titlu { font-size: 38px; color: #ffd700; margin-bottom: 10px; }
    .articol-meta { color: var(--text-light); font-size: 14px; margin-bottom: 30px; display: block; border-bottom: 1px solid var(--border-color); padding-bottom: 15px; }
    .articol-continut { font-size: 18px; line-height: 1.8; color: var(--text-main); margin-bottom: 50px; white-space: pre-wrap; }
    
    .admin-bar { background: var(--bg-section); padding: 15px; border-radius: 10px; margin-bottom: 20px; display: flex; gap: 15px; border: 1px solid var(--border-color); align-items: center; }
    .admin-bar a { text-decoration: none; padding: 8px 15px; border-radius: 6px; font-weight: bold; color: #111; }
    
    .comentarii-sec { background: var(--card-bg); padding: 30px; border-radius: 16px; border: 1px solid var(--border-color); box-shadow: 0 5px 15px rgba(0,0,0,0.05); backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px);}
    .comentarii-sec h3 { color: var(--text-main); margin-bottom: 20px; border-bottom: 2px solid #ffd700; display: inline-block; padding-bottom: 5px; }
    .com-box { background: rgba(255,255,255,0.02); padding: 15px; border-radius: 10px; margin-bottom: 15px; border: 1px solid var(--border-color); }
    .com-nume { font-weight: bold; color: var(--link-color); margin-bottom: 5px; display: block; }
    .com-data { font-size: 12px; color: var(--text-light); }
    
    .form-comentariu textarea { width: 100%; padding: 15px; border-radius: 10px; background: var(--input-bg); border: 1px solid var(--border-color); color: var(--text-main); margin-bottom: 15px; resize: vertical; font-family: inherit; }
    .form-comentariu textarea:focus { border-color: var(--link-color); outline: none; }
    .form-comentariu button { background: var(--link-color); color: white; border: none; padding: 12px 25px; border-radius: 8px; font-weight: bold; cursor: pointer; transition: 0.3s; }
    .form-comentariu button:hover { opacity: 0.8; }

    .login-prompt { text-align: center; padding: 30px; background: rgba(0, 123, 255, 0.1); border-radius: 12px; border: 1px solid rgba(0, 123, 255, 0.3); margin-bottom: 30px; }
    .login-prompt a { color: var(--link-color); font-weight: bold; text-decoration: none; }
    .login-prompt a:hover { text-decoration: underline; }
</style>

<div class="articol-page">
    <?php if($este_admin): ?>
        <div class="admin-bar">
            <span style="color: var(--text-main); font-weight: bold;">⚙️ Unelte Admin:</span>
            <a href="admin_articol.php?edit_id=<?= $id ?>" style="background: var(--accent-edit);">✏️ Editează</a>
        </div>
    <?php endif; ?>

    <h1 class="articol-titlu"><?= htmlspecialchars($articol['titlu']) ?></h1>
    <span class="articol-meta">Publicat la <?= date('d.m.Y H:i', strtotime($articol['data_creare'])) ?> • Scris de <?= htmlspecialchars($articol['sursa']) ?></span>
    
    <img src="img/<?= htmlspecialchars($articol['imagine']) ?>" class="articol-img" onerror="this.src='https://via.placeholder.com/900x400/333/fff?text=Articol'">
    
    <div class="articol-continut"><?= htmlspecialchars($articol['continut']) ?></div>

    <div class="comentarii-sec">
        <h3>💬 Comentarii (<?= count($comentarii) ?>)</h3>
        
        <?php if (isset($_SESSION['user_id'])): ?>
            <form class="form-comentariu" method="POST" action="">
                <textarea name="mesaj" rows="3" placeholder="Scrie un comentariu..." required></textarea>
                <button type="submit" name="adauga_comentariu">Postează Comentariul</button>
            </form>
        <?php else: ?>
            <div class="login-prompt">
                <p style="margin: 0;">Trebuie să fii <a href="#" onclick="openPopup('loginPopup'); return false;">autentificat</a> pentru a lăsa un comentariu.</p>
            </div>
        <?php endif; ?>

        <div style="margin-top: 30px;">
            <?php if(empty($comentarii)): ?>
                <p style="color: var(--text-light); text-align: center;">Fii primul care lasă un comentariu!</p>
            <?php else: ?>
                <?php foreach($comentarii as $com): ?>
                    <div class="com-box">
                        <span class="com-nume"><?= htmlspecialchars($com['nume']) ?> <span class="com-data">• <?= date('d.m.Y', strtotime($com['data_adaugare'])) ?></span></span>
                        <p style="margin-top: 5px; color: var(--text-main);"><?= htmlspecialchars($com['mesaj']) ?></p>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php include 'footer.php'; ?>