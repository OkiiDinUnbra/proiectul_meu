<?php
require_once 'db_connect.php';
$page_title = "Știri Locale | Descoperă Brăila";
include 'header.php';

$context = stream_context_create(['http' => ['header' => "User-Agent: Mozilla/5.0\r\n"]]);
$surse_rss = [
    'Obiectiv Vocea Brăilei' => 'https://obiectivbr.ro/feed/',
    'deBrăila.ro' => 'https://debraila.ro/feed/'
];
$stiri = [];

foreach ($surse_rss as $nume_sursa => $url) {
    $rss_content = @file_get_contents($url, false, $context);
    if ($rss_content !== false) {
        $rss = @simplexml_load_string($rss_content);
        if ($rss && isset($rss->channel->item)) {
            $count = 0;
            foreach ($rss->channel->item as $item) {
                if ($count >= 6) break; // Putem mări numărul de știri acum că sunt mai compacte
                
                $stiri[] = [
                    'titlu' => (string)$item->title,
                    'link_extern' => (string)$item->link,
                    'sursa' => $nume_sursa,
                    'data_timestamp' => strtotime((string)$item->pubDate),
                    'data_afisare' => date('d.m.Y H:i', strtotime((string)$item->pubDate))
                ];
                $count++;
            }
        }
    }
}

usort($stiri, function($a, $b) { return $b['data_timestamp'] - $a['data_timestamp']; });
?>

<style>
    .blog-page { padding: 140px 20px 60px; min-height: 100vh; color: var(--text-main); }
    .blog-header { text-align: center; margin-bottom: 40px; }
    .blog-header h1 { color: var(--text-main); font-size: 34px; margin-bottom: 8px; }
    .blog-header p { color: var(--text-light); font-size: 15px; }
    
    .blog-grid { max-width: 800px; margin: auto; }
    
    /* Layout Orizontal Compact FĂRĂ Imagini */
    .news-horizontal-card {
        display: flex;
        flex-direction: column;
        justify-content: center;
        background: var(--card-bg);
        border: 1px solid var(--border-color);
        border-radius: 12px;
        margin-bottom: 16px;
        padding: 20px 25px;
        text-decoration: none;
        color: var(--text-main);
        transition: 0.3s;
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
    }
    .news-horizontal-card:hover {
        transform: translateY(-3px);
        box-shadow: var(--shadow-medium);
        border-color: rgba(91, 176, 255, 0.4);
    }
    
    .news-horizontal-source {
        font-size: 12px;
        font-weight: 700;
        color: var(--link-color);
        margin-bottom: 8px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .news-horizontal-title {
        font-size: 18px;
        font-weight: 600;
        color: var(--text-main);
        line-height: 1.4;
    }
    
    .news-horizontal-time {
        font-size: 11px;
        color: var(--text-lighter);
        font-weight: 500;
    }
</style>

<div class="blog-page">
    <div class="blog-header">
        <h1>Știri Locale din Brăila</h1>
        <p>Informații de ultimă oră preluate automat din presa locală.</p>
    </div>
    
    <div class="blog-grid">
        <?php if (empty($stiri)): ?>
            <p style="text-align:center; width:100%; color: var(--text-light);">Nu s-au putut prelua știrile în acest moment. Vă rugăm reveniți.</p>
        <?php else: ?>
            <?php foreach($stiri as $stire): ?>
                <a href="<?= htmlspecialchars($stire['link_extern']) ?>" target="_blank" class="news-horizontal-card">
                    <div class="news-horizontal-source">
                        <span><?= htmlspecialchars($stire['sursa']) ?></span>
                        <span class="news-horizontal-time"><?= $stire['data_afisare'] ?></span>
                    </div>
                    <div class="news-horizontal-title"><?= htmlspecialchars($stire['titlu']) ?></div>
                </a>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
<?php include 'footer.php'; ?>