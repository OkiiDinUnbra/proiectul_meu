<?php
require_once 'db_connect.php';
$page_title = "Articole Originale | Descoperă Brăila";
include 'header.php';

// Verificăm dacă userul e admin
$este_admin = (isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin');

// Preluăm doar articolele originale
$articole = [];
$sql = "SELECT * FROM blog WHERE tip_postare = 'articol' ORDER BY data_creare DESC";
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) { $articole[] = $row; }
}
?>

<style>
    .blog-page { padding: 140px 20px 60px; min-height: 100vh; color: var(--text-main); }
    .blog-header { text-align: center; margin-bottom: 40px; }
    .blog-header h1 { color: var(--text-main); font-size: 36px; margin-bottom: 10px; }
    .blog-header p { color: var(--text-main); opacity: 0.8; }
    .blog-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 30px; max-width: 1200px; margin: auto; }
    
    .blog-card { 
        background: var(--card-bg); 
        border: 1px solid var(--border-color); 
        border-radius: 16px; 
        overflow: hidden; 
        transition: transform 0.3s, box-shadow 0.3s;
    }
    .blog-card:hover { transform: translateY(-8px); box-shadow: 0 15px 30px rgba(0,0,0,0.1); border-color: #007bff; }
    .blog-img { width: 100%; height: 220px; object-fit: cover; }
    .blog-info { padding: 25px; }
    
    .blog-tag { 
        display: inline-block; 
        padding: 5px 12px; font-size: 12px; font-weight: bold; border-radius: 20px; margin-bottom: 15px; 
        background: rgba(0, 123, 255, 0.1); color: #007bff; 
        border: 1px solid rgba(0, 123, 255, 0.2); 
    }
    .blog-title { font-size: 20px; margin-bottom: 15px; color: var(--text-main); line-height: 1.4; }
    .blog-btn { display: inline-block; color: #007bff; text-decoration: none; font-weight: 600; font-size: 15px; transition: 0.2s; }
    .blog-btn:hover { color: #0056b3; transform: translateX(5px); }
</style>

<div class="blog-page">
    <div class="blog-header">
        <h1>📝 Articole Originale</h1>
        <p>Povești, istorie și cultură scrise de echipa Descoperă Brăila.</p>
        
        <?php if($este_admin): ?>
            <div style="margin-top: 25px;">
                <a href="admin_articol.php" class="btn" style="background: #28a745; color: white;">+ Adaugă Articol Nou</a>
            </div>
        <?php endif; ?>
    </div>

    <div class="blog-grid">
        <?php if(empty($articole)): ?>
            <p style="text-align:center; width:100%; color: var(--text-main); opacity: 0.7;">Nu există articole momentan.</p>
        <?php else: ?>
            <?php foreach($articole as $articol): ?>
                <div class="blog-card">
                    <img src="img/<?= htmlspecialchars($articol['imagine'] ?? 'default.jpg') ?>" class="blog-img" onerror="this.src='https://via.placeholder.com/400x220/333/fff?text=Articol'">
                    <div class="blog-info">
                        <span class="blog-tag">Ghid & Istorie</span>
                        <h3 class="blog-title"><?= htmlspecialchars($articol['titlu']) ?></h3>
                        <a href="articol.php?id=<?= $articol['id'] ?>" class="blog-btn">Citește articolul →</a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php include 'footer.php'; ?>