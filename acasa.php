<?php 
require_once 'db_connect.php';
$page_title = t('home_welcome') . " | " . t('page_title');
include 'header.php';
?>

<style>
    html, body {
        overflow: hidden !important;
        height: 100% !important;
        margin: 0 !important;
        padding: 0 !important;
    }
</style>

<section class="hero" id="acasa">
    <!-- Watermark text pe fundal -->
    <div class="hero-watermark">BR</div>
    
    <div class="hero-content">
        <!-- Badge / Tag -->
        <div class="hero-tag">⚓ Județul Brăila</div>
        
        <h2><?= t('home_welcome') ?></h2>
        <p>Evenimente, știri și ghid turistic în timp real pentru orașul tău.</p>
        
        <!-- Butoanele CTA -->
        <div class="hero-btns">
            <a href="evenimente.php" class="btn-hero-primary"><?= t('home_events_btn') ?></a>
            <a href="ghid.php" class="btn-hero-ghost"><?= t('nav_guide') ?></a>
        </div>
    </div>
    
    <!-- Animație de scroll -->
    <div class="hero-scroll-hint">
        scroll ⬇
    </div>
</section>

<?php include 'footer.php'; ?>