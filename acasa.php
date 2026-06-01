<?php 
require_once 'db_connect.php';
$page_title = t('home_welcome') . " | " . t('page_title');
include 'header.php';
?>

<style>
    /* Eliminăm complet scroll-ul de pe pagină */
    html, body {
        overflow: hidden !important;
        height: 100% !important;
        margin: 0 !important;
        padding: 0 !important;
    }
    
    /* Agățăm secțiunea exact de tavanul ecranului (sub header) pe înălțimea fixă de 100% */
    .hero {
        position: absolute !important;
        top: 0 !important;
        left: 0 !important;
        width: 100% !important;
        height: 100vh !important;
        margin: 0 !important;
        box-sizing: border-box !important;
    }

    /* Ridicăm textul de jos ca să nu mai iasă din ecran sau să fie acoperit de taskbar */
    .brand-bottom {
        position: absolute;
        bottom: 40px; 
        left: 40px; 
        color: rgba(255, 255, 255, 0.9); 
        font-size: 18px; 
        font-weight: 600; 
        z-index: 10; 
        text-shadow: 1px 1px 5px rgba(0,0,0,0.9);
    }
</style>

<section class="hero" id="acasa">
    <div class="hero-content">
        <h2><?= t('home_welcome') ?></h2>
        <p><?= t('home_subtitle') ?></p>
        <div style="margin-top: 20px;">
            <a href="evenimente.php" class="btn" style="margin-right: 15px;"><?= t('home_events_btn') ?></a>
            <a href="transport.php" class="btn" style="background-color: #28a745; color: white;"><?= t('home_transport_btn') ?></a>
        </div>
    </div>
    
    <!-- Textul de jos a fost ridicat și mărit o idee -->
    <div class="brand-bottom">
        <?= t('home_brand') ?>
    </div>
</section>