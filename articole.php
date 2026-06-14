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
    /* === SLIDESHOW BACKGROUND === */
    html, body {
        margin: 0 !important;
        padding: 0 !important;
        background: #0A192F; 
    }

    .slideshow-container { 
        position: fixed; width: 100%; height: 100%; top: 0; left: 0; z-index: 0; 
    }
    
    .mySlides { 
        width: 100%; height: 100%; background-size: cover; background-position: center; 
        background-repeat: no-repeat; position: absolute; opacity: 0; 
        transition: opacity 1.5s ease-in-out; filter: blur(4px); transform: scale(1.05); 
    }
    
    .mySlides.active { opacity: 1; }
    
    .overlay { 
        position: fixed; width: 100%; height: 100%; top: 0; left: 0; 
        background: rgba(10, 25, 47, 0.75); z-index: 1; 
    }

    /* === CONTINUT PAGINA === */
    .blog-page { 
        position: relative; z-index: 2;
        padding: 140px 20px 60px; min-height: 100vh; color: #fff; 
    }
    
    .blog-header { text-align: center; margin-bottom: 40px; }
    .blog-header h1 { color: #ffffff; font-size: 42px; margin-bottom: 10px; font-weight: 800; text-shadow: 0 4px 10px rgba(0,0,0,0.8); }
    .blog-header p { color: #e2e8f0; font-size: 18px; opacity: 0.9; text-shadow: 0 2px 5px rgba(0,0,0,0.8); }
    
    .blog-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 30px; max-width: 1200px; margin: auto; }
    
    .blog-card { 
        background: rgba(10, 25, 47, 0.6);
        border: 1px solid rgba(255, 255, 255, 0.15); 
        border-radius: 16px; 
        overflow: hidden; 
        transition: transform 0.3s, box-shadow 0.3s;
        backdrop-filter: blur(15px);
        -webkit-backdrop-filter: blur(15px);
        box-shadow: 0 10px 25px rgba(0,0,0,0.3);
    }
    .blog-card:hover { transform: translateY(-8px); box-shadow: 0 15px 35px rgba(0,0,0,0.6); border-color: rgba(56, 189, 248, 0.6); }
    
    .blog-img { width: 100%; height: 220px; object-fit: cover; transition: transform 0.5s; }
    .blog-card:hover .blog-img { transform: scale(1.05); }
    
    .blog-info { padding: 25px; }
    
    .blog-tag { 
        display: inline-block; padding: 5px 12px; font-size: 12px; font-weight: bold; border-radius: 20px; margin-bottom: 15px; 
        background: rgba(56, 189, 248, 0.15); color: #38bdf8; border: 1px solid rgba(56, 189, 248, 0.3); 
    }
    .blog-title { font-size: 20px; margin-bottom: 15px; color: #ffffff; line-height: 1.4; font-weight: 700;}
    .blog-btn { display: inline-flex; align-items: center; gap: 5px; color: #38bdf8; text-decoration: none; font-weight: 700; font-size: 15px; transition: 0.2s; }
    .blog-card:hover .blog-btn { gap: 8px; color: #fff; }

    /* Stil NOU pentru butonul de ADMIN - Roșu Premium */
    .btn-admin-red {
        background: #dc3545; color: white; padding: 12px 28px; border-radius: 10px; text-decoration: none;
        font-weight: bold; display: inline-block; transition: all 0.3s ease; box-shadow: 0 4px 15px rgba(220, 53, 69, 0.3);
    }
    .btn-admin-red:hover { background: #c82333; transform: translateY(-3px); color: white; box-shadow: 0 6px 20px rgba(220, 53, 69, 0.4); }
</style>

<div class="slideshow-container">
    <div class="mySlides active" style="background-image: url('img/braila1.jpg');"></div>
    <div class="mySlides" style="background-image: url('img/braila2.jpg');"></div>
    <div class="mySlides" style="background-image: url('img/braila3.jpg');"></div>
    <div class="mySlides" style="background-image: url('img/braila4.jpg');"></div>
</div>
<div class="overlay"></div>

<div class="blog-page fade-up-element">
    <div class="blog-header">
        <h1>📝 Articole Originale</h1>
        <p>Povești, istorie și cultură scrise de echipa Descoperă Brăila.</p>
        
        <?php if($este_admin): ?>
            <div style="margin-top: 25px;">
                <a href="admin_articol.php" class="btn-admin-red">+ Adaugă Articol Nou</a>
            </div>
        <?php endif; ?>
    </div>

    <div class="blog-grid">
        <?php if(empty($articole)): ?>
            <p style="text-align:center; width:100%; color: #8892b0; font-size: 18px;">Nu există articole momentan.</p>
        <?php else: ?>
            <?php foreach($articole as $articol): ?>
                <a href="articol.php?id=<?= $articol['id'] ?>" class="blog-card" style="text-decoration: none;">
                    <div style="overflow: hidden;">
                        <img src="img/<?= htmlspecialchars($articol['imagine'] ?? 'default.jpg') ?>" class="blog-img" onerror="this.src='https://via.placeholder.com/400x220/112240/fff?text=Articol'">
                    </div>
                    <div class="blog-info">
                        <span class="blog-tag">Ghid & Istorie</span>
                        <h3 class="blog-title"><?= htmlspecialchars($articol['titlu']) ?></h3>
                        <div class="blog-btn">Citește articolul <span>→</span></div>
                    </div>
                </a>
            <?php endforeach; ?>
        <?php endif; ?>
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