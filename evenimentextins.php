<?php
session_start();
require_once 'db_connect.php';

// Verificăm dacă am primit un ID valid în URL
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: evenimente.php");
    exit();
}

$id_eveniment = intval($_GET['id']);

// 1. PRELUĂM DETALIILE EVENIMENTULUI
$stmt = $conn->prepare("SELECT * FROM evenimente WHERE id = ?");
$stmt->bind_param("i", $id_eveniment);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("<div style='text-align:center; padding: 100px;'><h2>Evenimentul nu a fost găsit!</h2><a href='evenimente.php'>Înapoi</a></div>");
}
$eveniment = $result->fetch_assoc();
$stmt->close();

// 2. ADĂUGARE RECENZIE
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

// 3. PRELUARE RECENZII
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
    
    .event-hero { display: flex; flex-wrap: wrap; gap: 30px; background: #fff; border-radius: 20px; padding: 30px; box-shadow: 0 10px 30px rgba(0,0,0,0.08); margin-bottom: 40px;}
    .event-image { flex: 1; min-width: 300px; border-radius: 15px; overflow: hidden; }
    .event-image img { width: 100%; height: 100%; object-fit: cover; }
    .event-info { flex: 1.5; min-width: 300px; display: flex; flex-direction: column; justify-content: center;}
    
    .event-title { font-size: 32px; color: #111; margin-bottom: 15px; margin-top: 0;}
    .event-meta { font-size: 16px; color: #555; margin-bottom: 20px; line-height: 1.6;}
    .event-meta strong { color: #0056b3; }
    .event-desc { font-size: 16px; color: #444; line-height: 1.8; margin-bottom: 30px; }
    
    .btn-cumpara { display: inline-block; background: #28a745; color: white; padding: 15px 30px; border-radius: 12px; font-weight: bold; text-decoration: none; font-size: 18px; text-align: center; transition: all 0.3s; box-shadow: 0 5px 15px rgba(40, 167, 69, 0.3);}
    .btn-cumpara:hover { background: #218838; transform: translateY(-3px); box-shadow: 0 8px 20px rgba(40, 167, 69, 0.4);}
    
    /* Sectiune Recenzii */
    .reviews-section { background: #fff; border-radius: 20px; padding: 40px; box-shadow: 0 10px 30px rgba(0,0,0,0.08); }
    .reviews-header { display: flex; align-items: center; justify-content: space-between; border-bottom: 2px solid #f0f0f0; padding-bottom: 20px; margin-bottom: 30px; }
    .reviews-header h3 { margin: 0; font-size: 24px; color: #333; }
    .average-rating { font-size: 20px; font-weight: bold; color: #f39c12; }
    
    .add-review-box { background: #f8fafd; padding: 25px; border-radius: 15px; margin-bottom: 30px; border: 1px solid #e1e5eb;}
    .star-rating { display: flex; flex-direction: row-reverse; justify-content: flex-end; gap: 5px; font-size: 24px; cursor: pointer; }
    .star-rating input { display: none; }
    .star-rating label { color: #ccc; transition: color 0.2s; }
    .star-rating input:checked ~ label, .star-rating label:hover, .star-rating label:hover ~ label { color: #f39c12; }
    
    .review-textarea { width: 100%; padding: 15px; border: 2px solid #e1e5eb; border-radius: 12px; font-family: inherit; font-size: 15px; margin-top: 15px; resize: vertical; min-height: 100px; outline: none; transition: border-color 0.3s; }
    .review-textarea:focus { border-color: #0056b3; }
    
    .review-card { border-bottom: 1px solid #eee; padding-bottom: 20px; margin-bottom: 20px; }
    .review-card:last-child { border-bottom: none; margin-bottom: 0; padding-bottom: 0; }
    .review-user { font-weight: bold; color: #111; font-size: 16px; margin-bottom: 5px; display: flex; justify-content: space-between;}
    .review-date { font-size: 12px; color: #999; font-weight: normal;}
    .review-stars { color: #f39c12; font-size: 14px; margin-bottom: 10px; }
    .review-text { color: #555; font-size: 15px; line-height: 1.6; margin: 0; }
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
            
            <?php if(isset($eveniment['pret']) && $eveniment['pret'] > 0): ?>
                <a href="genereaza_bilet.php?id_eveniment=<?= $eveniment['id'] ?>" class="btn-cumpara">💳 Cumpără Bilet Acum</a>
            <?php endif; ?>

            <?php if (isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin'): ?>
                <div class="admin-controls">
                    <a href="editeaza_eveniment.php?id=<?= $eveniment['id'] ?>" class="admin-btn btn-edit">✏️ Editează</a>
                    <a href="sterge_eveniment.php?id=<?= $eveniment['id'] ?>" class="admin-btn btn-delete" onclick="return confirm('Ștergi definitiv acest eveniment?');">🗑️ Șterge</a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Reviews Section -->
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

        <!-- Add Review Form -->
        <?php if (isset($_SESSION['user_id'])): ?>
            <div class="add-review-box">
                <h4 style="margin-top: 0; margin-bottom: 15px;">Lasă o recenzie:</h4>
                <form method="POST" action="evenimentextins.php?id=<?= $id_eveniment ?>">
                    <div style="margin-bottom: 15px;">
                        <label style="font-weight: 600; color: #555; display: block; margin-bottom: 8px;">Evaluare (1-5 stele):</label>
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
                    <button type="submit" name="adauga_recenzie" class="btn-cumpara" style="margin-top: 15px; display: inline-block;">Postează Recenzia</button>
                </form>
            </div>
        <?php else: ?>
            <div style="text-align: center; padding: 20px; background: #f9f9f9; border-radius: 12px; margin-bottom: 30px;">
                <p style="color: #666; margin: 0;">Trebuie să fii <a href="#" onclick="openPopup('loginPopup'); return false;" style="color: #0056b3; font-weight: 600;">autentificat</a> pentru a lăsa o recenzie.</p>
            </div>
        <?php endif; ?>

        <!-- Reviews Display -->
        <?php if (!empty($recenzii)): ?>
            <div style="margin-top: 30px;">O
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
            <p style="text-align: center; color: #999; padding: 30px 0;">Nicio recenzie încă. Fii primul care evaluează!</p>
        <?php endif; ?>
    </div>
</div>

<?php include 'footer.php'; ?>