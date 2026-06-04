<?php 
require_once 'db_connect.php';
$page_title = t('home_welcome') . " | " . t('page_title');
include 'header.php';
?>

<style>
    html, body {
        margin: 0 !important;
        padding: 0 !important;
        width: 100%;
        height: 100%;
    }

    .hero {
        position: relative;
        display: flex;
        align-items: center;
        justify-content: center;
        width: 100%;
        height: 100vh;
        overflow: hidden; 
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
        transform: translate(-50%, -50%);
        pointer-events: none; 
        z-index: 0; /* Videoclipul stă în spate de tot */
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
        background: rgba(13, 13, 18, 0.75); 
        z-index: 1; /* Filtrul închis stă peste video */
    }

    .hero-watermark, .hero-content {
        position: relative;
        z-index: 10; /* Textul stă peste filtru */
    }
</style>

<section class="hero" id="acasa">
    
    <iframe class="video-background" src="https://www.youtube-nocookie.com/embed/DjZadBZLLWw?autoplay=1&mute=1&loop=1&playlist=DjZadBZLLWw&controls=0&showinfo=0&rel=0&playsinline=1&modestbranding=1" allow="autoplay; encrypted-media"></iframe>
    
    <div class="hero-overlay"></div>

    <div class="hero-watermark">BR</div>
    
    <div class="hero-content">
        <div class="hero-tag">⚓ Județul Brăila</div>
        <h2><?= t('home_welcome') ?></h2>
        <p>Evenimente, știri și ghid turistic în timp real pentru orașul tău.</p>
        
        <div class="hero-btns">
            <a href="calendar.php?categorie=cultural" class="btn-hero-primary">Vezi Calendarul</a>
            <a href="ghid.php" class="btn-hero-ghost">Ghid Turistic</a>
        </div>
    </div>
</section>

<?php include 'footer.php'; ?>