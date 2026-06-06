<?php
session_start();
$page_title = "Favoritele Mele | Descoperă Brăila";
include 'header.php';
require_once 'db_connect.php';

// Redirecționăm dacă nu e logat
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Preluăm evenimentele adăugate la favorite
$favorite = [];
$stmt_fav = $conn->prepare("
    SELECT e.id, e.titlu, e.data_eveniment, e.imagine, e.locatie, e.pret 
    FROM favorite f 
    JOIN evenimente e ON f.item_id = e.id 
    WHERE f.user_id = ? AND f.tip_item = 'eveniment' 
    ORDER BY f.data_adaugare DESC
");
$stmt_fav->bind_param("i", $user_id);
$stmt_fav->execute();
$res_fav = $stmt_fav->get_result();
while ($row = $res_fav->fetch_assoc()) {
    $favorite[] = $row;
}
$stmt_fav->close();
?>

<style>
    .favorite-container { padding: 120px 20px 60px; max-width: 1000px; margin: auto; min-height: 70vh; }
    .header-favorite { display: flex; align-items: center; justify-content: space-between; margin-bottom: 30px; border-bottom: 2px solid var(--border-color); padding-bottom: 20px; }
    
    .grid-favorite { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 25px; }
    
    .card-fav-wrapper {
        background: var(--card-bg); border-radius: 15px; box-shadow: 0 5px 20px rgba(0,0,0,0.08);
        position: relative; overflow: hidden; display: flex; flex-direction: column;
        transition: transform 0.2s; border: 1px solid var(--border-color);
    }
    .card-fav-wrapper:hover { transform: translateY(-5px); box-shadow: 0 8px 25px rgba(255, 77, 77, 0.2); border-color: #ff4d4d;}
    .card-fav-img { width: 100%; height: 180px; object-fit: cover; }
    .card-fav-body { padding: 20px; display: flex; flex-direction: column; flex-grow: 1; }
</style>

<section class="favorite-container">
    <div class="header-favorite">
        <div>
            <h2 style="color: var(--text-main); margin-bottom: 5px;">❤️ Favoritele Mele</h2>
            <p style="color: var(--text-light); margin: 0;">Evenimentele pe care le-ai salvat pentru mai târziu.</p>
        </div>
    </div>

    <?php if (empty($favorite)): ?>
        <div style="text-align: center; padding: 50px; background: var(--card-bg); border-radius: 15px; border: 1px solid var(--border-color);">
            <h3 style="color: var(--text-main);">Nu ai salvat niciun eveniment la favorite.</h3>
            <p style="color: var(--text-light); margin-bottom: 20px;">Apasă pe inimioară (🤍) când găsești un eveniment interesant pentru a-l salva aici!</p>
            <a href="evenimente.php" class="btn-submit-modern" style="text-decoration: none; padding: 10px 20px; width: auto; display: inline-block;">Explorează Evenimentele</a>
        </div>
    <?php else: ?>
        <div class="grid-favorite">
            <?php foreach ($favorite as $fav): ?>
                <div class="card-fav-wrapper">
                    <img src="<?= htmlspecialchars($fav['imagine'] ?? 'img/placeholder.jpg') ?>" class="card-fav-img" alt="<?= htmlspecialchars($fav['titlu']) ?>">
                    <div class="card-fav-body">
                        <h4 style="margin: 0 0 10px 0; font-size: 18px; color: var(--text-main);"><?= htmlspecialchars($fav['titlu']) ?></h4>
                        <p style="margin: 5px 0; color: var(--text-light); font-size: 14px;">📅 <strong>Dată:</strong> <?= date('d/m/Y H:i', strtotime($fav['data_eveniment'])) ?></p>
                        <p style="margin: 5px 0; color: var(--text-light); font-size: 14px;">📍 <strong>Locație:</strong> <?= htmlspecialchars($fav['locatie']) ?></p>
                        
                        <a href="evenimentextins.php?id=<?= $fav['id'] ?>" class="btn-submit-modern" style="background: var(--link-color); padding: 10px; font-size: 14px; margin-top: auto; margin-bottom: 0; text-align: center; text-decoration: none;">Vezi Detalii</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<?php include 'footer.php'; ?>