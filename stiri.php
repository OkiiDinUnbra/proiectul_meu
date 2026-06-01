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
                
                $imagine = '';
                $content_encoded = isset($item->children('content', true)->encoded) ? (string)$item->children('content', true)->encoded : '';
                $description = isset($item->description) ? (string)$item->description : '';
                
                if (preg_match('/<img[^>]+src=[\'"]([^\'"]+)[\'"][^>]*>/i', $content_encoded, $matches) || 
                    preg_match('/<img[^>]+src=[\'"]([^\'"]+)[\'"][^>]*>/i', $description, $matches)) {
                    $imagine = $matches[1];
                }
                
                if (empty($imagine)) {
                    $imagine = ($nume_sursa === 'Obiectiv Vocea Brăilei') ?
                        'https://via.placeholder.com/400x220/1a1a24/ffffff?text=Obiectiv+Vocea+Brailei' : 
                        'https://via.placeholder.com/400x220/222233/ffffff?text=deBraila.ro';
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
    .blog-header { text-align: center; margin-bottom: 40px; }
    .blog-header h1 { color: var(--text-main); font-size: 34px; margin-bottom: 8px; }
    .blog-header p { color: var(--text-light); font-size: 15px; }
    
    .blog-grid { max-width: 860px; margin: auto; }
    
    /* Layout Orizontal pentru Carduri Știri */
    .news-horizontal-card {
        display: flex;
        align-items: center;
        background: var(--card-bg);
        border: 1px solid var(--border-color);
        border-radius: 12px;
        margin-bottom: 16px;
        text-decoration: none;
        color: var(--text-main);
        overflow: hidden;
        transition: 0.3s;
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
    }
    .news-horizontal-card:hover {
        transform: translateY(-3px);
        box-shadow: var(--shadow-medium);
        border-color: rgba(91, 176, 255, 0.4);
    }
    
    .news-horizontal-thumb {
        width: 160px;
        height: 120px;
        object-fit: cover;
        flex-shrink: 0;
    }
    
    .news-horizontal-thumb-placeholder {
        width: 160px;
        height: 120px;
        background: var(--bg-section);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 32px;
        flex-shrink: 0;
    }
    
    .news-horizontal-body {
        padding: 15px 25px;
        flex: 1;
        display: flex;
        flex-direction: column;
        justify-content: center;
    }
    
    .news-horizontal-source {
        font-size: 12px;
        font-weight: 700;
        color: var(--link-color);
        margin-bottom: 6px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .news-horizontal-title {
        font-size: 18px;
        font-weight: 600;
        color: var(--text-main);
        line-height: 1.4;
        margin-bottom: 8px;
    }
    
    .news-horizontal-time {
        font-size: 12px;
        color: var(--text-lighter);
    }

    @media (max-width: 600px) {
        .news-horizontal-card { flex-direction: column; align-items: stretch; }
        .news-horizontal-thumb, .news-horizontal-thumb-placeholder { width: 100%; height: 180px; }
        .news-horizontal-body { padding: 20px; }
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
                    <img src="<?= htmlspecialchars($stire['imagine']) ?>" class="news-horizontal-thumb"
                         onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                    <div class="news-horizontal-thumb-placeholder" style="display:none;">IMG</div>
                    
                    <div class="news-horizontal-body">
                        <div>
                            <div class="news-horizontal-source"><?= htmlspecialchars($stire['sursa']) ?></div>
                            <div class="news-horizontal-title"><?= htmlspecialchars($stire['titlu']) ?></div>
                        </div>
                        <div class="news-horizontal-time"><?= $stire['data_afisare'] ?></div>
                    </div>
                </a>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
<?php include 'footer.php'; ?>