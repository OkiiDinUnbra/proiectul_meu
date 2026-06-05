<?php
session_start();
require_once 'db_connect.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: evenimente.php");
    exit();
}

$id_eveniment = intval($_GET['id']);

$stmt = $conn->prepare("SELECT * FROM evenimente WHERE id = ?");
$stmt->bind_param("i", $id_eveniment);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("<div style='text-align:center; padding: 100px;'><h2>Evenimentul nu a fost găsit!</h2><a href='evenimente.php'>Înapoi</a></div>");
}
$eveniment = $result->fetch_assoc();
$stmt->close();

$show_free_ticket_popup = false;
$are_bilet = false;
$tip_bilet_cumparat = null;

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    
    // Verificăm dacă a cumpărat deja un bilet
    $stmt_check = $conn->prepare("SELECT id, tip_bilet FROM bilete_achizitionate WHERE user_id = ? AND id_eveniment = ?");
    $stmt_check->bind_param("ii", $user_id, $id_eveniment);
    $stmt_check->execute();
    $res_check = $stmt_check->get_result();
    
    if ($res_check->num_rows > 0) {
        $are_bilet = true;
        $row_bilet = $res_check->fetch_assoc();
        $tip_bilet_cumparat = $row_bilet['tip_bilet'];
    } else {
        // Logica pentru bilete gratuite
        if (isset($eveniment['bilete_gratuite_total']) && isset($eveniment['bilete_gratuite_date']) && 
            $eveniment['bilete_gratuite_total'] > $eveniment['bilete_gratuite_date']) {
            
            $cod_unic = "BR-EV-FREE-" . strtoupper(substr(uniqid(), -4)) . rand(10,99);
            $data_achizitie = date('Y-m-d H:i:s');
            $tip = 'fizic'; // Am actualizat la fizic
            
            $stmt_ins = $conn->prepare("INSERT INTO bilete_achizitionate (user_id, cod_qr_unic, data_achizitie, data_expirare, status, tip_bilet, id_eveniment) VALUES (?, ?, ?, NULL, 'activ', ?, ?)");
            $stmt_ins->bind_param("isssi", $user_id, $cod_unic, $data_achizitie, $tip, $id_eveniment);
            
            if ($stmt_ins->execute()) {
                $conn->query("UPDATE evenimente SET bilete_gratuite_date = bilete_gratuite_date + 1 WHERE id = " . $id_eveniment);
                $show_free_ticket_popup = true;
                $are_bilet = true;
                $tip_bilet_cumparat = 'fizic';
            }
            $stmt_ins->close();
        }
    }
    $stmt_check->close();
}

// Calcule pentru sistemul Live
$timp_curent = time();
$timp_eveniment = strtotime($eveniment['data_eveniment']);
$minute_ramase = floor(($timp_eveniment - $timp_curent) / 60);

$pret_online = $eveniment['pret'] + 15;
$are_link_live = !empty($eveniment['link_live']);

// Logica Recenzii
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['adauga_recenzie'])) {
    if (isset($_SESSION['user_id'])) {
        $user_id = $_SESSION['user_id'];
        $rating = intval($_POST['rating']);
        $comentariu = trim($_POST['comentariu']);

        if ($rating >= 1 && $rating <= 5 && !empty($comentariu)) {
            $stmt_insert = $conn->prepare("INSERT INTO recenzii_evenimente (id_eveniment, user_id, rating, comentariu) VALUES (?, ?, ?, ?)");
            $stmt_insert->bind_param("iiis", $id_eveniment, $user_id, $rating, $comentariu);
            $stmt_insert->execute();
            $stmt_insert->close();
            header("Location: evenimentextins.php?id=" . $id_eveniment);
            exit();
        }
    }
}

$recenzii = [];
$medie_rating = 0;
$total_recenzii = 0;

$stmt_recenzii = $conn->prepare("SELECT r.rating, r.comentariu, r.data_adaugare, u.nume FROM recenzii_evenimente r JOIN utilizatori u ON r.user_id = u.id WHERE r.id_eveniment = ? ORDER BY r.data_adaugare DESC");
$stmt_recenzii->bind_param("i", $id_eveniment);
$stmt_recenzii->execute();
$res_recenzii = $stmt_recenzii->get_result();

while ($row = $res_recenzii->fetch_assoc()) {
    $recenzii[] = $row;
    $medie_rating += $row['rating'];
}
$total_recenzii = count($recenzii);
if ($total_recenzii > 0) {
    $medie_rating = round($medie_rating / $total_recenzii, 1);
}
$stmt_recenzii->close();

$page_title = $eveniment['titlu'] . " | Descoperă Brăila";
include 'header.php';
?>

<style>
    .event-detail-container { max-width: 1000px; margin: 120px auto 60px auto; padding: 0 20px; }
    
    .event-hero { display: flex; flex-wrap: wrap; gap: 30px; background: var(--card-bg); border-radius: 20px; padding: 30px; box-shadow: 0 10px 30px rgba(0,0,0,0.08); margin-bottom: 40px; border: 1px solid var(--border-color); backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px);}
    .event-image { flex: 1; min-width: 300px; border-radius: 15px; overflow: hidden; }
    .event-image img { width: 100%; height: 100%; object-fit: cover; }
    .event-info { flex: 1.5; min-width: 300px; display: flex; flex-direction: column; justify-content: center;}
    
    .event-title { font-size: 32px; color: var(--text-main); margin-bottom: 15px; margin-top: 0;}
    .event-meta { font-size: 16px; color: var(--text-light); margin-bottom: 20px; line-height: 1.6;}
    .event-meta strong { color: var(--link-color); }
    .event-desc { font-size: 16px; color: var(--text-main); line-height: 1.8; margin-bottom: 30px; }
    
    .btn-cumpara { display: inline-block; background: var(--accent-success); color: #000; padding: 15px 30px; border-radius: 12px; font-weight: bold; text-decoration: none; font-size: 16px; text-align: center; transition: all 0.3s; border: none; cursor: pointer;}
    .btn-cumpara:hover { transform: translateY(-3px); box-shadow: 0 8px 20px rgba(40, 167, 69, 0.3);}
    
    .reviews-section { background: var(--card-bg); border-radius: 20px; padding: 40px; box-shadow: 0 10px 30px rgba(0,0,0,0.08); border: 1px solid var(--border-color); backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px); color: var(--text-main);}
    .reviews-header { display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid var(--border-color); padding-bottom: 20px; margin-bottom: 30px; }
    .reviews-header h3 { margin: 0; font-size: 24px; color: var(--text-main); }
    .average-rating { font-size: 20px; font-weight: bold; color: #ffd700; }
    
    .add-review-box { background: rgba(255,255,255,0.05); padding: 25px; border-radius: 15px; margin-bottom: 30px; border: 1px solid var(--border-color);}
    .star-rating { display: flex; flex-direction: row-reverse; justify-content: flex-end; gap: 5px; font-size: 24px; cursor: pointer; }
    .star-rating input { display: none; }
    .star-rating label { color: var(--border-color); transition: color 0.2s; }
    .star-rating input:checked ~ label, .star-rating label:hover, .star-rating label:hover ~ label { color: #ffd700; }
    
    .review-textarea { width: 100%; padding: 15px; border: 1px solid var(--border-color); border-radius: 12px; font-family: inherit; font-size: 15px; margin-top: 15px; resize: vertical; min-height: 100px; outline: none; transition: border-color 0.3s; background: var(--input-bg); color: var(--text-main);}
    .review-textarea:focus { border-color: var(--link-color); }
    
    .review-card { border-bottom: 1px solid var(--border-color); padding-bottom: 20px; margin-bottom: 20px; }
    .review-card:last-child { border-bottom: none; margin-bottom: 0; padding-bottom: 0; }
    .review-user { font-weight: bold; color: var(--text-main); font-size: 16px; margin-bottom: 5px; display: flex; justify-content: space-between;}
    .review-date { font-size: 12px; color: var(--text-light); font-weight: normal;}
    .review-stars { color: #ffd700; font-size: 14px; margin-bottom: 10px; }
    .review-text { color: var(--text-light); font-size: 15px; line-height: 1.6; margin: 0; }

    .login-prompt { text-align: center; padding: 30px; background: rgba(0, 123, 255, 0.1); border-radius: 12px; border: 1px solid rgba(0, 123, 255, 0.3); margin-bottom: 30px; }
    .login-prompt a { color: var(--link-color); font-weight: bold; text-decoration: none; }
    .login-prompt a:hover { text-decoration: underline; }
</style>

<div class="event-detail-container">
    <div class="event-hero">
        <div class="event-image">
            <img src="<?= htmlspecialchars($eveniment['imagine'] ?? 'img/placeholder.jpg') ?>" alt="<?= htmlspecialchars($eveniment['titlu']) ?>">
        </div>
        
        <div class="event-info">
            <h1 class="event-title"><?= htmlspecialchars($eveniment['titlu']) ?></h1>
            
            <div class="event-meta">
                <p>📅 <strong>Dată:</strong> <?= date('d/m/Y H:i', strtotime($eveniment['data_eveniment'])) ?></p>
                <p>📍 <strong>Locație:</strong> <?= htmlspecialchars($eveniment['locatie']) ?></p>
                
                <?php if(isset($eveniment['pret']) && $eveniment['pret'] > 0): ?>
                    <p>🎫 <strong>Preț bilet:</strong> <?= htmlspecialchars($eveniment['pret']) ?> RON</p>
                <?php else: ?>
                    <p>🎟️ <strong>Intrare liberă</strong></p>
                <?php endif; ?>
            </div>
            
            <div class="event-desc">
                <?= nl2br(htmlspecialchars($eveniment['descriere'])) ?>
            </div>
            
            <!-- SECȚIUNEA NOUĂ DE BILETE HIBRID -->
            <div class="bilete-container" style="display: flex; gap: 15px; margin-top: 10px; flex-wrap: wrap;">
                <?php if(isset($_SESSION['user_id'])): ?>
                    
                    <?php if(!$are_bilet): ?>
                        <!-- Nu are bilet achiziționat -->
                        <?php if($eveniment['pret'] > 0): ?>
                            <a href="genereaza_bilet.php?id_eveniment=<?= $eveniment['id'] ?>&tip=fizic" class="btn-cumpara" style="background: var(--accent-success);">🎫 Bilet Fizic (<?= htmlspecialchars($eveniment['pret']) ?> RON)</a>
                            
                            <?php if($are_link_live): ?>
                                <?php if($minute_ramase > 5): ?>
                                    <a href="genereaza_bilet.php?id_eveniment=<?= $eveniment['id'] ?>&tip=online" class="btn-cumpara" style="background: var(--link-color); color: white;">💻 Bilet Online (<?= htmlspecialchars($pret_online) ?> RON)</a>
                                <?php else: ?>
                                    <div style="padding: 10px 15px; background: rgba(255,0,0,0.1); border: 1px solid var(--accent-delete); border-radius: 12px; color: var(--text-main); font-weight: 600;">
                                        ⏳ Vânzarea biletelor online s-a închis.
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>
                            
                        <?php else: ?>
                            <a href="genereaza_bilet.php?id_eveniment=<?= $eveniment['id'] ?>&tip=fizic" class="btn-cumpara">🎟️ Rezervă Loc Gratuit</a>
                        <?php endif; ?>
                        
                    <?php else: ?>
                        <!-- Are deja bilet achiziționat -->
                        <div style="padding: 20px; background: rgba(40,167,69,0.1); border: 1px solid var(--accent-success); border-radius: 12px; width: 100%;">
                            <h4 style="color: var(--accent-success); margin-top: 0; margin-bottom: 5px;">✅ Ai achiziționat un bilet pentru acest eveniment!</h4>
                            <p style="margin-bottom: 0; color: var(--text-main);">Tip bilet: <strong><?= strtoupper($tip_bilet_cumparat) ?></strong></p>
                            
                            <?php if($tip_bilet_cumparat === 'online' && $are_link_live): ?>
                                <?php if($minute_ramase <= 15): ?>
                                    <div style="margin-top: 20px;">
                                        <h3 style="color: var(--accent-delete); margin-bottom: 10px;">🔴 LIVE ACUM</h3>
                                        <div style="position: relative; padding-bottom: 56.25%; height: 0; overflow: hidden; border-radius: 15px; box-shadow: 0 5px 15px rgba(0,0,0,0.3);">
                                            <?php 
                                                // Extragem ID-ul YouTube din link
                                                $url = $eveniment['link_live'];
                                                preg_match('/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/i', $url, $matches);
                                                $yt_id = isset($matches[1]) ? $matches[1] : '';
                                            ?>
                                            <?php if($yt_id): ?>
                                                <iframe style="position: absolute; top: 0; left: 0; width: 100%; height: 100%;" src="https://www.youtube.com/embed/<?= $yt_id ?>?autoplay=1" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
                                            <?php else: ?>
                                                <a href="<?= htmlspecialchars($url) ?>" target="_blank" class="btn-cumpara" style="background: red; color: white; display: block; text-align: center;">▶️ Deschide Live-ul Extern</a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <p style="color: var(--text-light); margin-top: 15px; font-weight: bold;">⏳ Transmisiunea live va deveni disponibilă aici cu 15 minute înainte de începerea spectacolului.</p>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    
                <?php else: ?>
                    <!-- Utilizator Nelogat -->
                    <a href="#" onclick="openPopup('loginPopup'); return false;" class="btn-cumpara" style="background: var(--link-color); color: #fff;">Autentifică-te pentru a cumpăra bilet</a>
                <?php endif; ?>
            </div>

            <?php if (isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin'): ?>
                <div class="admin-controls" style="margin-top: 20px; display: flex; gap: 10px;">
                    <a href="editeaza_eveniment.php?id=<?= $eveniment['id'] ?>" class="btn-cumpara" style="background: var(--accent-edit); padding: 10px 20px; font-size: 14px;">✏️ Editează</a>
                    <a href="sterge_eveniment.php?id=<?= $eveniment['id'] ?>" class="btn-cumpara" style="background: var(--accent-delete); color: #fff; padding: 10px 20px; font-size: 14px;" onclick="return confirm('Ștergi definitiv acest eveniment?');">🗑️ Șterge</a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Restul paginii: Recenziile -->
    <div class="reviews-section">
        <div class="reviews-header">
            <h3>⭐ Recenzii & Evaluări</h3>
            <div class="average-rating">
                <?php if ($total_recenzii > 0): ?>
                    <?= $medie_rating ?>/5 (<?= $total_recenzii ?> evaluări)
                <?php else: ?>
                    Nicio evaluare încă
                <?php endif; ?>
            </div>
        </div>

        <?php if (isset($_SESSION['user_id'])): ?>
            <div class="add-review-box">
                <h4 style="margin-top: 0; margin-bottom: 15px; color: var(--text-main);">Lasă o recenzie:</h4>
                <form method="POST" action="evenimentextins.php?id=<?= $id_eveniment ?>">
                    <div style="margin-bottom: 15px;">
                        <label style="font-weight: 600; color: var(--text-light); display: block; margin-bottom: 8px;">Evaluare (1-5 stele):</label>
                        <div class="star-rating">
                            <input type="radio" id="star5" name="rating" value="5" required>
                            <label for="star5">★</label>
                            <input type="radio" id="star4" name="rating" value="4">
                            <label for="star4">★</label>
                            <input type="radio" id="star3" name="rating" value="3">
                            <label for="star3">★</label>
                            <input type="radio" id="star2" name="rating" value="2">
                            <label for="star2">★</label>
                            <input type="radio" id="star1" name="rating" value="1">
                            <label for="star1">★</label>
                        </div>
                    </div>
                    <textarea name="comentariu" class="review-textarea" placeholder="Spune-ne cum ți s-a părut evenimentul..." required></textarea>
                    <button type="submit" name="adauga_recenzie" class="btn-cumpara" style="margin-top: 15px; display: inline-block; font-size: 15px; padding: 12px 25px;">Postează Recenzia</button>
                </form>
            </div>
        <?php else: ?>
            <div class="login-prompt">
                <p style="margin: 0;">Trebuie să fii <a href="#" onclick="openPopup('loginPopup'); return false;">autentificat</a> pentru a lăsa o recenzie.</p>
            </div>
        <?php endif; ?>

        <?php if (!empty($recenzii)): ?>
            <div style="margin-top: 30px;">
                <?php foreach ($recenzii as $recenzie): ?>
                    <div class="review-card">
                        <div class="review-user">
                            <span><?= htmlspecialchars($recenzie['nume']) ?></span>
                            <span class="review-date"><?= date('d/m/Y', strtotime($recenzie['data_adaugare'])) ?></span>
                        </div>
                        <div class="review-stars">
                            <?php for ($i = 0; $i < $recenzie['rating']; $i++): ?>★<?php endfor; ?>
                        </div>
                        <p class="review-text"><?= htmlspecialchars($recenzie['comentariu']) ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p style="text-align: center; color: var(--text-light); padding: 30px 0;">Nicio recenzie încă. Fii primul care evaluează!</p>
        <?php endif; ?>
    </div>
</div>

<?php if ($show_free_ticket_popup): ?>
<div id="freeTicketModal" style="position: fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.85); z-index:9999; display:flex; justify-content:center; align-items:center; backdrop-filter: blur(5px);">
    <div style="background: var(--card-bg); padding: 40px; border-radius: 20px; text-align: center; max-width: 500px; box-shadow: 0 10px 40px rgba(255, 215, 0, 0.5); border: 2px solid #ffd700; animation: popIn 0.6s cubic-bezier(0.68, -0.55, 0.265, 1.55); color: var(--text-main);">
        <h1 style="font-size: 60px; margin:0;">🎉</h1>
        <h2 style="color: var(--accent-success); margin-top: 10px; font-weight: 800; font-size: 30px;">FELICITĂRI!</h2>
        <p style="font-size: 18px;">Ai fost printre primii vizitatori și ai câștigat un bilet <strong style="color: var(--accent-delete);">GRATUIT</strong> la evenimentul:<br>
        <span style="font-size: 24px; color: var(--link-color); font-weight: bold; margin-top: 15px; display: block;"><?= htmlspecialchars($eveniment['titlu']) ?></span></p>
        <div style="background: rgba(40,167,69,0.1); border-left: 4px solid var(--accent-success); padding: 10px; margin-top: 20px; font-size: 14px; text-align: left;">
            Biletul tău a fost generat și adăugat automat în contul tău la secțiunea <strong>"Biletele Mele"</strong>.
        </div>
        <button onclick="document.getElementById('freeTicketModal').style.display='none'" class="btn-cumpara" style="margin-top: 25px; background: #ffd700; color: #111; width: 100%;">Super! Mergi mai departe</button>
    </div>
</div>
<style>
    @keyframes popIn { 0% { transform: scale(0.5); opacity: 0; } 100% { transform: scale(1); opacity: 1; } }
</style>
<?php endif; ?>

<?php include 'footer.php'; ?>