<?php
require_once 'db_connect.php';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$este_admin = (isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin');

// Preia articolul
$sql = "SELECT * FROM blog WHERE id = $id AND tip_postare = 'articol'";
$result = $conn->query($sql);
if (!$result || $result->num_rows == 0) { header("Location: articole.php"); exit(); }
$articol = $result->fetch_assoc();

// Adăugare Comentariu
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['adauga_comentariu'])) {
    $nume = !empty($_SESSION['nume']) ? $_SESSION['nume'] : 'Anonim';
    $mesaj = mysqli_real_escape_string($conn, $_POST['mesaj']);
    if(!empty($mesaj)) {
        $conn->query("INSERT INTO comentarii (articol_id, nume, mesaj) VALUES ($id, '$nume', '$mesaj')");
        header("Location: articol.php?id=$id"); exit();
    }
}

// Preia comentariile
$comentarii = [];
$res_coms = $conn->query("SELECT * FROM comentarii WHERE articol_id = $id ORDER BY data_adaugare DESC");
if ($res_coms) { while($r = $res_coms->fetch_assoc()) { $comentarii[] = $r; } }

$page_title = $articol['titlu'] . " | Descoperă Brăila";
include 'header.php';
?>

<style>
    .articol-page { padding: 120px 20px 60px; max-width: 900px; margin: 0 auto; color: #fff; }
    .articol-img { width: 100%; height: 400px; object-fit: cover; border-radius: 16px; margin-bottom: 30px; border: 1px solid rgba(255,255,255,0.1); }
    .articol-titlu { font-size: 38px; color: #ffd700; margin-bottom: 10px; }
    .articol-meta { color: #888; font-size: 14px; margin-bottom: 30px; display: block; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 15px; }
    .articol-continut { font-size: 18px; line-height: 1.8; color: #ddd; margin-bottom: 50px; white-space: pre-wrap; }
    
    .admin-bar { background: rgba(255, 255, 255, 0.1); padding: 15px; border-radius: 10px; margin-bottom: 20px; display: flex; gap: 15px; }
    .admin-bar a { text-decoration: none; padding: 8px 15px; border-radius: 6px; font-weight: bold; color: #fff; }
    
    .comentarii-sec { background: rgba(0,0,0,0.3); padding: 30px; border-radius: 16px; border: 1px solid rgba(255,255,255,0.05); }
    .comentarii-sec h3 { color: #ffd700; margin-bottom: 20px; }
    .com-box { background: rgba(255,255,255,0.05); padding: 15px; border-radius: 10px; margin-bottom: 15px; }
    .com-nume { font-weight: bold; color: #66b2ff; margin-bottom: 5px; display: block; }
    .com-data { font-size: 12px; color: #888; }
    
    .form-comentariu textarea { width: 100%; padding: 15px; border-radius: 10px; background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2); color: white; margin-bottom: 15px; resize: vertical; }
    .form-comentariu button { background: #0056b3; color: white; border: none; padding: 10px 25px; border-radius: 8px; font-weight: bold; cursor: pointer; }
</style>

<div class="articol-page">
    <?php if($este_admin): ?>
        <div class="admin-bar">
            <span style="color: #ffd700; font-weight: bold;">Unelte Admin:</span>
            <a href="admin_articol.php?edit_id=<?= $id ?>" style="background: #ffc107; color: #000;">✏️ Editează</a>
        </div>
    <?php endif; ?>

    <h1 class="articol-titlu"><?= htmlspecialchars($articol['titlu']) ?></h1>
    <span class="articol-meta">Publicat la <?= date('d.m.Y H:i', strtotime($articol['data_creare'])) ?> • Scris de <?= htmlspecialchars($articol['sursa']) ?></span>
    
    <img src="img/<?= htmlspecialchars($articol['imagine']) ?>" class="articol-img" onerror="this.src='https://via.placeholder.com/900x400/333/fff?text=Articol'">
    
    <div class="articol-continut"><?= htmlspecialchars($articol['continut']) ?></div>

    <!-- Secțiune Comentarii -->
    <div class="comentarii-sec">
        <h3>💬 Comentarii (<?= count($comentarii) ?>)</h3>
        
        <form class="form-comentariu" method="POST" action="">
            <textarea name="mesaj" rows="3" placeholder="Scrie un comentariu..." required></textarea>
            <button type="submit" name="adauga_comentariu">Postează</button>
        </form>

        <div style="margin-top: 30px;">
            <?php if(empty($comentarii)): ?>
                <p style="color: #aaa;">Fii primul care lasă un comentariu!</p>
            <?php else: ?>
                <?php foreach($comentarii as $com): ?>
                    <div class="com-box">
                        <span class="com-nume"><?= htmlspecialchars($com['nume']) ?> <span class="com-data">• <?= date('d.m.Y', strtotime($com['data_adaugare'])) ?></span></span>
                        <p style="margin-top: 5px;"><?= htmlspecialchars($com['mesaj']) ?></p>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php include 'footer.php'; ?>