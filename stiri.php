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
                if ($count >= 4) break; 
                
                // --- Logica de extragere a imaginii ---
                $imagine = '';
                $content_encoded = $item->children('content', true)->encoded;
                
                // Căutăm tag de imagine în conținut sau descriere
                if (preg_match('/<img[^>]+src=[\'"]([^\'"]+)[\'"][^>]*>/i', $content_encoded, $matches) || 
                    preg_match('/<img[^>]+src=[\'"]([^\'"]+)[\'"][^>]*>/i', $item->description, $matches)) {
                    $imagine = $matches[1];
                }
                
                // Fallback dacă nu găsim nicio poză
                if (empty($imagine)) {
                    $imagine = ($nume_sursa === 'Obiectiv Vocea Brăilei') ? 'https://via.placeholder.com/400x220/0056b3/ffffff?text=Obiectiv+Vocea+Brailei' : 'https://via.placeholder.com/400x220/dc3545/ffffff?text=deBraila.ro';
                }

                $stiri[] = [
                    'titlu' => (string)$item->title,
                    'link_extern' => (string)$item->link,
                    'sursa' => $nume_sursa,
                    'imagine' => $imagine,
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
    .blog-header { text-align: center; margin-bottom: 50px; }
    .blog-header h1 { color: #ffd700; font-size: 36px; margin-bottom: 10px; }
    .blog-header p { color: var(--text-light); }
    .blog-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 30px; max-width: 1200px; margin: auto; }
    .blog-card { background: var(--card-bg); border: 1px solid var(--border-color); border-radius: 16px; overflow: hidden; transition: 0.3s; }
    .blog-card:hover { transform: translateY(-8px); box-shadow: 0 15px 30px var(--shadow-medium); border-color: rgba(255, 215, 0, 0.3); }
    .blog-img { width: 100%; height: 220px; object-fit: cover; }
    .blog-info { padding: 25px; }
    .blog-tag { display: inline-block; padding: 5px 12px; font-size: 12px; font-weight: bold; border-radius: 20px; margin-bottom: 15px; background: rgba(220, 53, 69, 0.2); color: #ff6b81; border: 1px solid rgba(220, 53, 69, 0.3); }
    .blog-title { font-size: 18px; margin-bottom: 15px; color: var(--text-main); line-height: 1.4; }
    .blog-date { font-size: 12px; color: var(--text-light); margin-bottom: 15px; display: block; }
    .blog-btn { display: inline-block; color: #ffd700; text-decoration: none; font-weight: 600; font-size: 15px; transition: 0.2s; }
    .blog-btn:hover { color: #fff; transform: translateX(5px); }
</style>

<div class="blog-page">
    <div class="blog-header">
        <h1>📰 Știri Locale din Brăila</h1>
        <p style="color: #aaa;">Informații de ultimă oră preluate automat din presa locală.</p>
    </div>
    <div class="blog-grid">
        <?php foreach($stiri as $stire): ?>
            <div class="blog-card">
                <img src="<?= htmlspecialchars($stire['imagine']) ?>" class="blog-img" onerror="this.src='https://via.placeholder.com/400x220/333/fff?text=Fara+Imagine'">
                <div class="blog-info">
                    <span class="blog-tag">Sursa: <?= htmlspecialchars($stire['sursa']) ?></span>
                    <span class="blog-date">🕒 <?= $stire['data_afisare'] ?></span>
                    <h3 class="blog-title"><?= htmlspecialchars($stire['titlu']) ?></h3>
                    <a href="<?= htmlspecialchars($stire['link_extern']) ?>" target="_blank" class="blog-btn">Citește pe site-ul sursă ↗</a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
<?php include 'footer.php'; ?>