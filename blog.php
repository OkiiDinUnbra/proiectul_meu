<?php 
require_once 'db_connect.php';
$page_title = t('blog_title') . " | " . t('page_title');
include 'header.php';
?>

<section class="blog" style="margin-top: 120px;">
    <div class="container">
        <h2><?= t('blog_title') ?></h2>
        <div class="blog-posts">
            <div class="post">
                <h3><?= t('blog_article1_title') ?></h3>
                <p><?= t('blog_article1_desc') ?></p>
                <a href="#" onclick="showToast('<?= t('blog_coming_soon') ?>', 'info'); return false;"><?= t('blog_read_more') ?></a>
            </div>
            <div class="post">
                <h3><?= t('blog_article2_title') ?></h3>
                <p><?= t('blog_article2_desc') ?></p>
                <a href="#" onclick="showToast('<?= t('blog_coming_soon') ?>', 'info'); return false;"><?= t('blog_read_more') ?></a>
            </div>
        </div>
    </div>
</section>

<?php include 'footer.php'; ?>