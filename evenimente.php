<?php 
require_once 'db_connect.php';
$page_title = t('events_title') . " | " . t('page_title');
include 'header.php'; 
?>

<style>
    .evenimente-home {
        padding: 100px 20px;
        background: linear-gradient(to right, #f5f7fa, #c3cfe2);
        text-align: center;
        margin-top: 80px; 
        min-height: 80vh;
    }
    .evenimente-home h1 { font-size: 42px; margin-bottom: 20px; }
    .evenimente-grid { display: flex; justify-content: center; gap: 40px; flex-wrap: wrap; margin-top: 30px; }
    .eveniment-card { width: 320px; border-radius: 16px; overflow: hidden; background: #fff; box-shadow: 0 4px 12px rgba(0,0,0,0.1); transition: transform 0.3s; text-decoration: none; color: inherit; }
    .eveniment-card:hover { transform: translateY(-8px); }
    .eveniment-card img { width: 100%; height: 200px; object-fit: cover; }
    .eveniment-card h3 { font-size: 24px; padding: 20px; }
</style>

<section class="evenimente-home">
    <h1><?= t('events_choose') ?></h1>

    <?php if (isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin'): ?>
        <div style="margin-bottom: 20px;">
            <a href="adauga_eveniment.php" class="btn" style="background-color: #333; color: #fff; padding: 10px 20px; border-radius: 8px;"><?= t('events_add_new') ?></a>
        </div>
    <?php endif; ?>

    <div class="evenimente-grid">
        <a href="calendar.php?categorie=cultural" class="eveniment-card">
            <img src="img/cultural-bg.jpg" alt="<?= t('events_cultural') ?>">
            <h3><?= t('events_cultural') ?></h3>
        </a>
        <a href="calendar.php?categorie=sportiv" class="eveniment-card">
            <img src="img/sport-bg.jpg" alt="<?= t('events_sports') ?>">
            <h3><?= t('events_sports') ?></h3>
        </a>
    </div>
</section>

<?php include 'footer.php'; ?>