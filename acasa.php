<?php 
require_once 'db_connect.php';
$page_title = t('home_welcome') . " | " . t('page_title');
include 'header.php'; 
?>

<section class="hero" id="acasa" style="position: relative;">
    <div class="hero-content">
        <h2><?= t('home_welcome') ?></h2>
        <p><?= t('home_subtitle') ?></p>
        <div style="margin-top: 20px;">
            <a href="evenimente.php" class="btn" style="margin-right: 15px;"><?= t('home_events_btn') ?></a>
            <a href="transport.php" class="btn" style="background-color: #28a745; color: white;"><?= t('home_transport_btn') ?></a>
        </div>
    </div>
    <div style="position: absolute; bottom: 20px; left: 20px; color: rgba(255, 255, 255, 0.8); font-size: 16px; font-weight: 500; z-index: 10; text-shadow: 1px 1px 3px rgba(0,0,0,0.8);">
        <?= t('home_brand') ?>
    </div>
</section>

<?php include 'footer.php'; ?>