<?php
require_once 'db_connect.php';
$page_title = "Știri Locale | Descoperă Brăila";
include 'header.php';

// Setări pentru a putea descărca feed-urile RSS
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
                if ($count >= 10) break; // 10 știri/sursă => 20 de știri în total pe ecran
                
                // === EXTRAGERE DESCRIERE SCURTĂ ===
                $raw_desc = (string)$item->description;
                $clean_desc = strip_tags($raw_desc); // Eliminăm codul HTML din text
                $short_desc = mb_substr($clean_desc, 0, 130) . '...';

                // === VÂNĂTOAREA DE IMAGINI (SUPER-PARSER) ===
                $image_url = 'https://images.unsplash.com/photo-1585829365295-ab7cd400c167?w=600&q=80'; // Imagine default dacă chiar nu are poză
                
                if (isset($item->enclosure) && isset($item->enclosure['url'])) {
                    $image_url = (string)$item->enclosure['url'];
                } 
                else {
                    $media = $item->children('http://search.yahoo.com/mrss/');
                    if ($media && isset($media->content) && isset($media->content->attributes()->url)) {
                        $image_url = (string)$media->content->attributes()->url;
                    } 
                    elseif ($media && isset($media->thumbnail) && isset($media->thumbnail->attributes()->url)) {
                        $image_url = (string)$media->thumbnail->attributes()->url;
                    } 
                    else {
                        if (preg_match('/<img[^>]+src=["\']([^"\']+)["\']/i', $raw_desc, $matches)) {
                            $image_url = $matches[1];
                        } 
                        else {
                            $content = $item->children('http://purl.org/rss/1.0/modules/content/');
                            if ($content && isset($content->encoded)) {
                                if (preg_match('/<img[^>]+src=["\']([^"\']+)["\']/i', (string)$content->encoded, $matches)) {
                                    $image_url = $matches[1];
                                }
                            }
                        }
                    }
                }

                $stiri[] = [
                    'titlu' => (string)$item->title,
                    'descriere' => $short_desc,
                    'imagine' => $image_url,
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

// Sortăm cele 20 de știri cronologic (cele mai noi primele)
usort($stiri, function($a, $b) { return $b['data_timestamp'] - $a['data_timestamp']; });
?>

<style>
    /* === SLIDESHOW BACKGROUND === */
    html, body {
        margin: 0 !important;
        padding: 0 !important;
        background: #0A192F; 
    }

    .slideshow-container { 
        position: fixed;
        width: 100%; 
        height: 100%; 
        top: 0; 
        left: 0; 
        z-index: 0; 
    }
    
    .mySlides { 
        width: 100%;
        height: 100%; 
        background-size: cover;
        background-position: center; 
        background-repeat: no-repeat;
        position: absolute; 
        opacity: 0; 
        transition: opacity 1.5s ease-in-out;
        filter: blur(4px); 
        transform: scale(1.05); 
    }
    
    .mySlides.active { opacity: 1; }
    
    .overlay { 
        position: fixed;
        width: 100%; 
        height: 100%; 
        top: 0; 
        left: 0; 
        /* Overlay mai închis pentru ca textul știrilor să se citească perfect */
        background: rgba(10, 25, 47, 0.75); 
        z-index: 1; 
    }

    /* === CONTINUT PAGINA === */
    .blog-page { 
        position: relative;
        z-index: 2;
        padding: 140px 20px 60px; 
        min-height: 100vh; 
        color: #fff;
    }
    
    .blog-header { text-align: center; margin-bottom: 50px; }
    .blog-header h1 { color: #ffffff; font-size: 42px; margin-bottom: 10px; font-weight: 800; text-shadow: 0 4px 10px rgba(0,0,0,0.8); }
    .blog-header p { color: #e2e8f0; font-size: 18px; max-width: 600px; margin: 0 auto; text-shadow: 0 2px 5px rgba(0,0,0,0.8); }
    
    .blog-grid { 
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
        gap: 30px;
        max-width: 1400px;
        margin: auto;
    }
    
    .news-card {
        background: rgba(10, 25, 47, 0.6);
        border: 1px solid rgba(255, 255, 255, 0.15);
        border-radius: 16px;
        overflow: hidden;
        text-decoration: none;
        display: flex;
        flex-direction: column;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        backdrop-filter: blur(15px);
        -webkit-backdrop-filter: blur(15px);
        box-shadow: 0 10px 25px rgba(0,0,0,0.3);
    }

    .news-card:hover {
        transform: translateY(-8px);
        box-shadow: 0 15px 35px rgba(0,0,0,0.6);
        border-color: rgba(56, 189, 248, 0.6);
    }
    
    .news-img-container {
        width: 100%;
        height: 200px;
        overflow: hidden;
        position: relative;
        background: #112240; 
    }

    .news-img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.5s ease;
    }

    .news-card:hover .news-img {
        transform: scale(1.08); 
    }

    .news-content {
        padding: 20px;
        display: flex;
        flex-direction: column;
        flex: 1;
    }
    
    .news-meta {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 12px;
    }

    .news-source {
        font-size: 11px;
        font-weight: 800;
        background: rgba(56, 189, 248, 0.2);
        color: #38bdf8;
        padding: 4px 10px;
        border-radius: 20px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .news-time {
        font-size: 12px;
        color: #e2e8f0;
        font-weight: 600;
    }

    .news-title {
        font-size: 18px;
        font-weight: 700;
        color: #ffffff;
        line-height: 1.4;
        margin: 0 0 12px 0;
    }

    .news-desc {
        font-size: 14px;
        color: #a8b2d1;
        line-height: 1.6;
        margin: 0 0 20px 0;
        flex: 1; 
    }

    .news-read-more {
        font-size: 14px;
        font-weight: 700;
        color: #38bdf8;
        display: flex;
        align-items: center;
        gap: 5px;
        transition: 0.2s;
    }

    .news-card:hover .news-read-more {
        gap: 8px; 
    }
</style>

<!-- SLIDESHOW BACKGROUND -->
<div class="slideshow-container">
    <div class="mySlides active" style="background-image: url('img/braila1.jpg');"></div>
    <div class="mySlides" style="background-image: url('img/braila2.jpg');"></div>
    <div class="mySlides" style="background-image: url('img/braila3.jpg');"></div>
    <div class="mySlides" style="background-image: url('img/braila4.jpg');"></div>
</div>
<div class="overlay"></div>

<!-- CONTINUT PAGINA -->
<div class="blog-page fade-up-element">
    <div class="blog-header">
        <h1>Știri Locale din Brăila</h1>
        <p>Informații de ultimă oră preluate automat din presa locală. Păstrează-te conectat la pulsul orașului!</p>
    </div>
    
    <div class="blog-grid">
        <?php if (empty($stiri)): ?>
            <p style="text-align:center; grid-column: 1 / -1; color: #8892b0; font-size: 18px;">Nu s-au putut prelua știrile în acest moment. Vă rugăm reveniți.</p>
        <?php else: ?>
            <?php foreach($stiri as $stire): ?>
                <a href="<?= htmlspecialchars($stire['link_extern']) ?>" target="_blank" class="news-card">
                    
                    <div class="news-img-container">
                        <img src="<?= htmlspecialchars($stire['imagine']) ?>" alt="Imagine stire" class="news-img" loading="lazy">
                    </div>
                    
                    <div class="news-content">
                        <div class="news-meta">
                            <span class="news-source"><?= htmlspecialchars($stire['sursa']) ?></span>
                            <span class="news-time">⌚ <?= $stire['data_afisare'] ?></span>
                        </div>
                        
                        <h3 class="news-title"><?= htmlspecialchars($stire['titlu']) ?></h3>
                        <p class="news-desc"><?= htmlspecialchars($stire['descriere']) ?></p>
                        
                        <div class="news-read-more">
                            Citește articolul <span style="font-size:18px;">→</span>
                        </div>
                    </div>

                </a>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<script>
    // --- SCRIPT PENTRU SLIDESHOW ---
    let slideIndex = 0;
    const slides = document.querySelectorAll(".mySlides");
    
    function nextSlide() {
        if(slides.length === 0) return;
        slides[slideIndex].classList.remove("active");
        slideIndex = (slideIndex + 1) % slides.length;
        slides[slideIndex].classList.add("active");
    }
    
    if (slides.length > 0) {
        setInterval(nextSlide, 5000); // Se schimbă la 5 secunde
    }
</script>

<?php include 'footer.php'; ?>