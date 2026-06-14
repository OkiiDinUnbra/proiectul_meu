<?php 
require_once 'db_connect.php';
$page_title = t('home_welcome') . " | " . t('page_title');
include 'header.php';
?>

<style>
    /* MAGIC FIX: Oprește garantat scroll-ul vertical doar pe această pagină */
    html, body {
        margin: 0 !important;
        padding: 0 !important;
        width: 100%;
        height: 100%;
        overflow: hidden !important; 
    }

    .hero {
        position: relative;
        display: flex;
        align-items: center;
        justify-content: center;
        width: 100%;
        height: 100vh;
        background: var(--bg-main); 
    }

    .video-background {
        position: absolute;
        top: 50%;
        left: 50%;
        width: 100vw;
        height: 56.25vw; 
        min-height: 100vh;
        min-width: 177.77vh; 
        transform: translate(-50%, -50%) scale(1.2); 
        pointer-events: none; 
        z-index: 0; 
        border: none;
        opacity: 0;
        animation: fadeInVideo 1s ease-in 1.2s forwards; 
    }

    @keyframes fadeInVideo {
        from { opacity: 0; }
        to { opacity: 1; }
    }

    .hero-overlay {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.29); 
        z-index: 1; 
    }

    .hero-watermark, .hero-content {
        position: relative;
        z-index: 10; 
    }

    .hero-content {
        text-align: center;
        max-width: 1200px; 
        padding: 20px;
        /* Am coborât puțin conținutul ca să nu fie suprapus de noul header fix */
        margin-top: 50px; 
    }

    .hero-content h2 {
        font-size: 68px;
        color: #ffffff;
        margin-bottom: 15px;
        text-shadow: 0 4px 25px rgba(0,0,0,0.9), 0 2px 10px rgba(0,0,0,0.8);
        font-weight: 800;
        letter-spacing: 1px;
    }

    .hero-content p {
        font-size: 26px;
        color: #f8f9fa;
        margin-bottom: 50px;
        text-shadow: 0 4px 15px rgba(0,0,0,0.9);
        font-weight: 600;
    }

    .hero-btns-grid {
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 20px;
        flex-wrap: nowrap; 
        white-space: nowrap;
    }

    .btn-home-main {
        padding: 18px 35px;
        font-size: 20px;
        border-radius: 12px;
        background: rgba(0, 0, 0, 0.5); 
        color: #ffffff;
        text-decoration: none;
        font-weight: bold;
        border: 2px solid rgba(255, 255, 255, 0.6);
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
        transition: all 0.3s ease;
        box-shadow: 0 5px 15px rgba(0,0,0,0.5);
    }

    .btn-home-main:hover {
        background: #003366; 
        border-color: #003366;
        transform: translateY(-5px);
        box-shadow: 0 10px 25px rgba(0, 51, 102, 0.6);
    }
</style>

<section class="hero" id="acasa">
    
    <iframe class="video-background" src="https://www.youtube-nocookie.com/embed/DjZadBZLLWw?autoplay=1&mute=1&loop=1&playlist=DjZadBZLLWw&controls=0&showinfo=0&rel=0&playsinline=1&modestbranding=1" allow="autoplay; encrypted-media"></iframe>
    
    <div class="hero-overlay"></div>
    
    <div class="hero-content">
        <h2>Descoperă Brăila</h2>
        <p>Evenimente, știri și ghid turistic în timp real pentru orașul tău.</p>
        
        <div class="hero-btns-grid">
            <a href="evenimente.php" class="btn-home-main">🎭 Evenimente</a>
            <a href="ghid_turistic.php" class="btn-home-main">🗺️ Ghid Turistic</a>
            <a href="trafic.php" class="btn-home-main">🚦 Info Trafic</a>
            <a href="transport.php" class="btn-home-main">🚌 Transport</a>
        </div>
    </div>
</section>

<?php include 'footer.php'; ?>