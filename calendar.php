<?php 
// 1. Verificăm ce categorie a fost cerută prin URL (default este 'cultural')
$categorie = (isset($_GET['categorie']) && $_GET['categorie'] === 'sportiv') ? 'sportiv' : 'cultural';

// 2. Setăm variabilele dinamice în funcție de categorie
if ($categorie === 'sportiv') {
    $page_title = "Evenimente Sportive | Descoperă Brăila";
    $bg_image = "img/sport-bg.jpg";
    $titlu_pagina = "Evenimente Sportive în Brăila";
    $titlu_calendar = "Calendar Sportiv";
    $culoare_buton = "#007bff";
    $culoare_bg_sectiune = "#eef5ff";
} else {
    $page_title = "Evenimente Culturale | Descoperă Brăila";
    $bg_image = "img/cultural-bg.jpg";
    $titlu_pagina = "Evenimente Culturale în Brăila";
    $titlu_calendar = "Calendar Cultural";
    $culoare_buton = "#28a745";
    $culoare_bg_sectiune = "#f7f2eb";
}

$needs_calendar = true; 
include 'header.php'; 
?>

<style>
    .hero-calendar { background: url('<?= $bg_image ?>') no-repeat center center/cover; color: white; padding: 100px 20px; text-align: center; margin-top: 80px; }
    .hero-calendar h1 { font-size: 48px; text-shadow: 2px 2px 4px rgba(0,0,0,0.7); }
    .calendar-section { background: var(--bg-section); padding: 60px 20px; text-align: center; color: var(--text-main); }
    .calendar-section h2 { color: var(--text-main); }

    .fc-event-title {
        white-space: normal !important;
        word-wrap: break-word !important;
        font-size: 13px;
        line-height: 1.2;
    }
    .fc-event {
        cursor: pointer; 
        padding: 2px 4px;
    }
</style>

<section class="hero-calendar">
    <h1><?= $titlu_pagina ?></h1>
</section>

<section class="calendar-section">
    <h2><?= $titlu_calendar ?></h2>
    
    <?php if (isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin'): ?>
    <div style="max-width: 900px; margin: 0 auto 15px auto; text-align: right;">
        <a href="adauga_eveniment.php?categorie=<?= $categorie ?>" style="background: <?= $culoare_buton ?>; color: white; padding: 10px 20px; text-decoration: none; border-radius: 8px; font-weight: bold; display: inline-block; box-shadow: 0 4px 6px rgba(0,0,0,0.1); transition: 0.3s;">
            ➕ Adaugă Eveniment <?= ucfirst($categorie) ?>
        </a>
    </div>
    <?php endif; ?>

    <div id="calendar" style="max-width: 900px; margin: 0 auto; background: var(--card-bg); padding: 20px; border-radius: 10px; box-shadow: 0 4px 10px var(--shadow-light); color: var(--text-main);"></div>
</section>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const calendarEl = document.getElementById('calendar');
        const calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: ''
            },
            // Tragem datele dinamic de la API în funcție de categorie
            events: 'api_evenimente.php?categorie=<?= $categorie ?>', 
            eventClick: function(info) {
                info.jsEvent.preventDefault(); 
                
                const id = info.event.id || ''; 
                const title = info.event.title || 'Fără titlu';
                
                let formattedDate = 'Dată necunoscută';
                if (info.event.start) {
                    formattedDate = info.event.start.toLocaleDateString('ro-RO', { day: 'numeric', month: 'long', year: 'numeric' });
                }
                
                const location = (info.event.extendedProps && info.event.extendedProps.location) ? info.event.extendedProps.location : 'Nespecificat';
                const description = (info.event.extendedProps && info.event.extendedProps.description) ? info.event.extendedProps.description : 'Nu există detalii suplimentare.';

                if (typeof openEventPopup === "function") {
                    openEventPopup(id, title, formattedDate, location, description);
                } else {
                    console.error("Eroare: Funcția openEventPopup nu a fost găsită în pagină.");
                }
            }
        });
        calendar.render();
    });
</script>

<?php include 'footer.php'; ?>