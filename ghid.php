<?php 
require_once 'db_connect.php';
$page_title = t('guide_title') . " | " . t('page_title');
include 'header.php';
?>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<style>
    .ghid-hero {
        padding: 120px 20px 60px;
        text-align: center;
        background: #f8f9fa;
        margin-top: 80px;
    }
    .ghid-hero h1 { font-size: 42px; color: #333; margin-bottom: 15px; }
    .ghid-hero p { font-size: 18px; color: #666; max-width: 800px; margin: 0 auto 30px auto; }
    
    #harta-turistica {
        height: 600px;
        width: 100%; 
        max-width: 1200px; 
        margin: 0 auto; 
        border-radius: 12px; 
        box-shadow: 0 8px 20px rgba(0,0,0,0.15);
        z-index: 1; 
        border: 4px solid #fff;
    }
</style>

<section class="ghid-hero">
    <h1><?= t('guide_map_title') ?></h1>
    <p><?= t('guide_map_desc') ?></p>
    
    <div id="harta-turistica"></div>
</section>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Inițializăm harta
        var map = L.map('harta-turistica');

        // Stratul de hartă (OpenStreetMap)
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(map);

        // Lista de puncte cu coordonatele tale exacte și link-uri către info
        var locatii = [
            { nume: "Ceasul Public", lat: 45.271606001433845, lng: 27.97475371201764, wiki: "https://ro.wikipedia.org/wiki/Pia%C8%9Ba_Traian_din_Br%C4%83ila#Ceasul_public" },
            { nume: "Faleza Brăilei", lat: 45.262879549259345, lng: 27.967981528840024, wiki: "https://ro.wikipedia.org/wiki/Br%C4%83ila#Turism" },
            { nume: "Stadionul Municipal", lat: 45.258271419999794, lng: 27.94695780431002, wiki: "https://ro.wikipedia.org/wiki/Stadionul_Municipal_(Br%C4%83ila)" },
            { nume: "Brăila Mall", lat: 45.229470046258726, lng: 27.93692885140785, wiki: "https://ro.wikipedia.org/wiki/Br%C4%83ila_Mall" },
            { nume: "Grădina Zoologică", lat: 45.237733180864026, lng: 27.932565538581596, wiki: "https://www.google.com/search?q=Gradina+Zoologica+Braila" },
            { nume: "Parcul Monument", lat: 45.25455271981476, lng: 27.947634223404553, wiki: "https://ro.wikipedia.org/wiki/Parcul_Monument_din_Br%C4%83ila" },
            { nume: "Stațiunea Lacu Sărat", lat: 45.21589723357077, lng: 27.91302759483157, wiki: "https://ro.wikipedia.org/wiki/Lacu_S%C4%83rat,_Br%C4%83ila" },
            { nume: "Casa Memorială Panait Istrati", lat: 45.27403745324816, lng: 27.979128204525807, wiki: "https://ro.wikipedia.org/wiki/Casa_Memorial%C4%83_%E2%80%9EPanait_Istrati%E2%80%9D" },
            { nume: "Teatrul Maria Filotti", lat: 45.272024971432, lng: 27.97332028137194, wiki: "https://ro.wikipedia.org/wiki/Teatrul_%E2%80%9EMaria_Filotti%E2%80%9D" },
            { nume: "Centrul Istoric Brăila", lat: 45.271499690895254, lng: 27.971406542088857, wiki: "https://ro.wikipedia.org/wiki/Br%C4%83ila#Centrul_istoric" },
            { nume: "Muzeul Brăilei „Carol I”", lat: 45.27221224456756, lng: 27.974188202777317, wiki: "https://ro.wikipedia.org/wiki/Muzeul_Br%C4%83ilei" },
            { nume: "Grădina Publică", lat: 45.274676386983664, lng: 27.977218017727434, wiki: "https://ro.wikipedia.org/wiki/Gr%C4%83dina_Public%C4%83_din_Br%C4%83ila" },
            { nume: "Casa Memorială D.P. Perpessicius", lat: 45.27726323651136, lng: 27.97953979425381, wiki: "https://ro.wikipedia.org/wiki/Dumitru_Panaitescu-Perpessicius#Casa_memorială" },
            { nume: "Secția Științelor Naturii a Muzeului", lat: 45.255086768446226, lng: 27.946441752964706, wiki: "https://www.google.com/search?q=Sectia+Stiintelor+Naturii+Muzeul+Brailei" },
            { nume: "Biserica Greacă „Buna Vestire”", lat: 45.27025850952869, lng: 27.9754324925146, wiki: "https://ro.wikipedia.org/wiki/Biserica_Greac%C4%83_din_Br%C4%83ila" }
        ];

        // Creăm un FeatureGroup ca să putem face zoom automat mai târziu
        var markerePeHarta = L.featureGroup();

        // Parcurgem lista și punem fiecare punct pe hartă
        locatii.forEach(function(loc) {
            var marker = L.marker([loc.lat, loc.lng]);

            // Creăm design-ul pentru fereastra de detalii
            var popupContent = `
                <div style="text-align: center; padding: 5px;">
                    <h3 style="margin: 0 0 10px 0; font-size: 16px; color: #0056b3;">${loc.nume}</h3>
                    <a href="${loc.wiki}" target="_blank" style="background: #28a745; color: white; padding: 6px 12px; text-decoration: none; border-radius: 4px; font-weight: bold; display: inline-block; font-size: 13px;">
                        📖 Află mai multe
                    </a>
                </div>
            `;
            
            // Atașăm bula la marker și adăugăm marker-ul la grup
            marker.bindPopup(popupContent);
            markerePeHarta.addLayer(marker);
        });

        // Adăugăm toate markerele pe hartă dintr-un foc
        markerePeHarta.addTo(map);

        // Calculăm automat cadrul optim de zoom ca să se vadă TOATE punctele pe ecran
        map.fitBounds(markerePeHarta.getBounds(), { padding: [30, 30] });
    });
</script>

<?php include 'footer.php'; ?>