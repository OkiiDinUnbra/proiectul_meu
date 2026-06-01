<?php 
require_once 'db_connect.php';
$page_title = t('events_title') . " | " . t('page_title');
include 'header.php'; 
?>

<style>
    .evenimente-home {
        padding: 140px 20px 60px;
        text-align: center;
        min-height: 100vh;
        color: var(--text-main);
    }
    .evenimente-home h1 { 
        font-size: 38px; 
        margin-bottom: 10px; 
        color: var(--text-main);
    }
    .page-subtitle {
        color: var(--text-light);
        font-size: 16px;
        margin-bottom: 30px;
    }
    
    .evenimente-grid { 
        display: flex; 
        justify-content: center; 
        gap: 30px; 
        flex-wrap: wrap; 
        margin-top: 40px; 
        max-width: 1200px;
        margin-left: auto;
        margin-right: auto;
    }
    
    /* Stiluri Carduri Evenimente Modernizate */
    .eveniment-card { 
        position: relative;
        width: 340px; 
        border-radius: 16px; 
        overflow: hidden; 
        background: var(--card-bg); 
        box-shadow: var(--shadow-medium); 
        border: 1px solid var(--border-color);
        backdrop-filter: blur(12px);
        -webkit-backdrop-filter: blur(12px);
        transition: 0.3s; 
        text-decoration: none; 
        color: var(--text-main);
        display: flex;
        flex-direction: column;
        text-align: left;
    }
    .eveniment-card:hover { 
        transform: translateY(-8px); 
        border-color: rgba(255, 215, 0, 0.3); 
        box-shadow: var(--shadow-heavy);
    }
    .eveniment-card img { 
        width: 100%; 
        height: 200px; 
        object-fit: cover; 
    }
    
    .eveniment-card-content {
        padding: 20px;
        display: flex;
        flex-direction: column;
        flex-grow: 1;
    }
    
    .eveniment-card h3 { 
        font-size: 20px; 
        margin-bottom: 10px; 
        color: var(--text-main); 
        line-height: 1.3;
    }
    
    .eveniment-card-meta { 
        font-size: 14px; 
        color: var(--text-light); 
        margin-bottom: 20px; 
    }
    
    /* Badge Suprapus */
    .eveniment-card-badge {
        position: absolute;
        top: 15px;
        right: 15px;
        padding: 6px 14px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 700;
        backdrop-filter: blur(8px);
        z-index: 2;
    }
    .badge-cultural { background: rgba(111, 66, 193, 0.85); color: #fff; border: 1px solid #6f42c1; }
    .badge-sport { background: rgba(0, 123, 255, 0.85); color: #fff; border: 1px solid #007bff; }
    
    /* Indicator Preț */
    .eveniment-card-price {
        display: inline-block;
        padding: 6px 14px;
        background: rgba(255, 215, 0, 0.1);
        color: var(--accent-primary);
        border: 1px solid rgba(255, 215, 0, 0.3);
        border-radius: 8px;
        font-weight: 700;
        font-size: 14px;
        width: fit-content;
        margin-top: auto;
    }
</style>

<section class="evenimente-home">
    <h1><?= t('events_choose') ?></h1>
    <p class="page-subtitle"><?= t('events_choose') === 'Alege categoria' ? 'Selectează o categorie și descoperă evenimentele din Brăila' : 'Select a category and discover events in Brăila' ?></p>

    <?php if (isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin'): ?>
        <div style="margin-bottom: 24px;">
            <a href="adauga_eveniment.php" class="btn" style="background: var(--accent-success); color: #000; border: none; font-weight: 600;">+ <?= t('events_add_new') ?></a>
        </div>
    <?php endif; ?>

    <div class="evenimente-grid">
        <a href="calendar.php?categorie=cultural" class="eveniment-card">
            <img src="img/cultural-bg.jpg" alt="<?= t('events_cultural') ?>">
            <span class="eveniment-card-badge badge-cultural">Cultural</span>
            <div class="eveniment-card-content">
                <h3><?= t('events_cultural') ?></h3>
                <div class="eveniment-card-meta">Teatru, muzee, expoziții și concerte din oraș.</div>
                <div class="eveniment-card-price">Vezi Calendarul</div>
            </div>
        </a>
        
        <a href="calendar.php?categorie=sportiv" class="eveniment-card">
            <img src="img/sport-bg.jpg" alt="<?= t('events_sports') ?>">
            <span class="eveniment-card-badge badge-sport">Sport</span>
            <div class="eveniment-card-content">
                <h3><?= t('events_sports') ?></h3>
                <div class="eveniment-card-meta">Meciuri, maratoane și competiții locale.</div>
                <div class="eveniment-card-price">Vezi Calendarul</div>
            </div>
        </a>
    </div>
</section>

<?php include 'footer.php'; ?>