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
        border-color: rgba(0, 123, 255, 0.5); /* Hover mai neutru/albastru, nu auriu */
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
        background: rgba(0, 123, 255, 0.1); /* Modificat pe nuanțe de albastru modern */
        color: #007bff;
        border: 1px solid rgba(0, 123, 255, 0.3);
        border-radius: 8px;
        font-weight: 700;
        font-size: 14px;
        width: fit-content;
        margin-top: auto;
        transition: 0.3s;
    }
    .eveniment-card:hover .eveniment-card-price {
        background: #007bff;
        color: white;
    }

    /* === STILURI NOI PENTRU BUTONUL DE FAVORITE === */
    .btn-favorite {
        position: absolute;
        top: 15px;
        left: 15px;
        background: rgba(0, 0, 0, 0.5);
        border: 1px solid rgba(255,255,255,0.2);
        color: white;
        font-size: 18px;
        width: 38px;
        height: 38px;
        border-radius: 50%;
        cursor: pointer;
        z-index: 3;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        backdrop-filter: blur(5px);
        -webkit-backdrop-filter: blur(5px);
        text-decoration: none;
    }
    .btn-favorite:hover {
        transform: scale(1.15);
        background: rgba(0, 0, 0, 0.7);
    }
    .btn-favorite.active {
        color: #ff4d4d;
        border-color: rgba(255, 77, 77, 0.5);
        background: rgba(255, 77, 77, 0.1);
    }

    /* Stil NOU pentru butonul de ADMIN - Roșu Premium */
    .btn-admin-red {
        background: #dc3545; 
        color: white;
        padding: 12px 28px;
        border-radius: 10px;
        text-decoration: none;
        font-weight: bold;
        display: inline-block;
        transition: all 0.3s ease;
        box-shadow: 0 4px 15px rgba(220, 53, 69, 0.3);
        border: none;
    }
    .btn-admin-red:hover {
        background: #c82333;
        transform: translateY(-3px);
        color: white;
        box-shadow: 0 6px 20px rgba(220, 53, 69, 0.4);
    }
</style>

<section class="evenimente-home">
    <h1><?= t('events_choose') ?></h1>
    <p class="page-subtitle"><?= t('events_choose') === 'Alege categoria' ? 'Selectează o categorie și descoperă evenimentele din Brăila' : 'Select a category and discover events in Brăila' ?></p>

    <?php if (isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin'): ?>
        <div style="margin-bottom: 24px;">
            <a href="adauga_eveniment.php" class="btn-admin-red">+ <?= t('events_add_new') ?></a>
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

<script>
function toggleFavorite(event, itemId, tipItem, element) {
    event.preventDefault(); // Previne deschiderea link-ului cardului
    event.stopPropagation(); // Oprește propagarea click-ului

    fetch('toggle_favorite.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            item_id: itemId,
            tip_item: tipItem
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'adaugat') {
            element.classList.add('active');
            element.innerHTML = '❤️';
        } else if (data.status === 'sters') {
            element.classList.remove('active');
            element.innerHTML = '🤍';
        } else if (data.status === 'neautorizat') {
            // Dacă ai funcția openPopup din header.php pentru login, o folosim:
            if(typeof openPopup === 'function') {
                openPopup('loginPopup');
            } else {
                alert('Trebuie să fii logat pentru a salva evenimente la favorite!');
            }
        }
    })
    .catch(error => console.error('Eroare AJAX Favorite:', error));
}
</script>

<?php include 'footer.php'; ?>