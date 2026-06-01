<?php 
require_once 'db_connect.php';
$page_title = t('guide_title') . " | " . t('page_title');
include 'header.php';
?>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<style>
    .ghid-hero {
    padding: 160px 20px 60px; /* Am compensat din interior */
    text-align: center;
    background: #f8f9fa;
    margin-top: 0; /* Am scos gaura albă și de pe această pagină */
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

    .legend {
    line-height: 18px;
    color: #555;
    box-shadow: 0 0 15px rgba(0,0,0,0.2);
    font-size: 14px;
}
</style>

<section class="ghid-hero">
    <h1><?= t('guide_map_title') ?></h1>
    <p><?= t('guide_map_desc') ?></p>
    
    <div id="harta-turistica"></div>
</section>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Inițializăm harta (centrată inițial pe Brăila)
        var map = L.map('harta-turistica');

        // Stratul de hartă (OpenStreetMap)
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(map);

        // Definește iconițele colorate
        const blueIcon = L.icon({
            iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-blue.png',
            iconSize: [25, 41], iconAnchor: [12, 41], popupAnchor: [1, -34]
        });

        const greenIcon = L.icon({
            iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-green.png',
            iconSize: [25, 41], iconAnchor: [12, 41], popupAnchor: [1, -34]
        });

        // Lista completă de locații (Atracții + Restaurante & Fast Food)
        var locatii = [
            // ================== ATRACȚII TURISTICE & MALL (Albastru) ==================
            { nume: "Ceasul Public", lat: 45.271606, lng: 27.974753, wiki: "https://ro.wikipedia.org/wiki/Pia%C8%9Ba_Traian_din_Br%C4%83ila#Ceasul_public", tip: "atractie" },
            { nume: "Faleza Brăilei", lat: 45.262879, lng: 27.967981, wiki: "https://ro.wikipedia.org/wiki/Br%C4%83ila#Turism", tip: "atractie" },
            { nume: "Stadionul Municipal", lat: 45.258271, lng: 27.946957, wiki: "https://ro.wikipedia.org/wiki/Stadionul_Municipal_(Br%C4%83ila)", tip: "atractie" },
            { nume: "Grădina Zoologică", lat: 45.237733, lng: 27.932565, wiki: "https://www.google.com/search?q=Gradina+Zoologica+Braila", tip: "atractie" },
            { nume: "Parcul Monument", lat: 45.254552, lng: 27.947634, wiki: "https://ro.wikipedia.org/wiki/Parcul_Monument_din_Br%C4%83ila", tip: "atractie" },
            { nume: "Stațiunea Lacu Sărat", lat: 45.215897, lng: 27.913027, wiki: "https://ro.wikipedia.org/wiki/Lacu_S%C4%83rat,_Br%C4%83ila", tip: "atractie" },
            { nume: "Casa Memorială Panait Istrati", lat: 45.274037, lng: 27.979128, wiki: "https://ro.wikipedia.org/wiki/Casa_Memorial%C4%83_%E2%80%9EPanait_Istrati%E2%80%9D", tip: "atractie" },
            { nume: "Teatrul Maria Filotti", lat: 45.272024, lng: 27.973320, wiki: "https://ro.wikipedia.org/wiki/Teatrul_%E2%80%9EMaria_Filotti%E2%80%9D", tip: "atractie" },
            { nume: "Centrul Istoric Brăila", lat: 45.271499, lng: 27.971406, wiki: "https://ro.wikipedia.org/wiki/Br%C4%83ila#Centrul_istoric", tip: "atractie" },
            { nume: "Muzeul Brăilei „Carol I”", lat: 45.272212, lng: 27.974188, wiki: "https://ro.wikipedia.org/wiki/Muzeul_Br%C4%83ilei", tip: "atractie" },
            { nume: "Grădina Publică", lat: 45.274676, lng: 27.977218, wiki: "https://ro.wikipedia.org/wiki/Gr%C4%83dina_Public%C4%83_din_Br%C4%83ila", tip: "atractie" },
            { nume: "Casa Memorială D.P. Perpessicius", lat: 45.277263, lng: 27.979539, wiki: "https://ro.wikipedia.org/wiki/Dumitru_Panaitescu-Perpessicius#Casa_memorială", tip: "atractie" },
            { nume: "Secția Științelor Naturii a Muzeului", lat: 45.255086, lng: 27.946441, wiki: "https://www.google.com/search?q=Sectia+Stiintelor+Naturii+Muzeul+Brailei", tip: "atractie" },
            { nume: "Biserica Greacă „Buna Vestire”", lat: 45.270258, lng: 27.975432, wiki: "https://ro.wikipedia.org/wiki/Biserica_Greac%C4%83_din_Br%C4%83ila", tip: "atractie" },
            { nume: "Promenada Mall", lat: 45.230273860412765, lng: 27.93828318564685, wiki: "https://www.google.com/search?q=Promenada+Mall+Braila", tip: "atractie" },

            // ================== RESTAURANTE & FAST FOOD (Verde) ==================
            { nume: "McDonald's (Barieră)", lat: 45.2550517694067, lng: 27.958166039091335, wiki: "https://www.google.com/search?q=McDonalds+Bariera+Braila", tip: "restaurant" },
            { nume: "Domino's Pizza", lat: 45.25533324134098, lng: 27.95837057298481, wiki: "https://www.google.com/search?q=Dominos+Pizza+Braila", tip: "restaurant" },
            { nume: "KFC Bariera", lat: 45.25598001783551, lng: 27.960233624981157, wiki: "https://www.google.com/search?q=KFC+Bariera+Braila", tip: "restaurant" },
            { nume: "Restaurant Carol (Cel mai bun din Oras)", lat: 45.27210479958391, lng: 27.975163712161493, wiki: "https://www.google.com/search?q=Restaurant+Carol+Braila", tip: "restaurant" },
            { nume: "Cherhanaua (fosta Sunrise Marina)", lat: 45.26934443572491, lng: 27.97946802340389, wiki: "https://www.google.com/search?q=Cherhanaua+Braila", tip: "restaurant" },
            { nume: "Heavens", lat: 45.267897478489445, lng: 27.972469424073214, wiki: "https://www.google.com/search?q=Heavens+Braila", tip: "restaurant" },
            { nume: "Bella Italia", lat: 45.2764115374821, lng: 27.9666702642137, wiki: "https://www.google.com/search?q=Bella+Italia+Braila", tip: "restaurant" },
            { nume: "The Irish Pub", lat: 45.26533042429084, lng: 27.97059461115861, wiki: "https://www.google.com/search?q=The+Irish+Pub+Braila", tip: "restaurant" },
            { nume: "Thassos Food", lat: 45.258678319899076, lng: 27.96140826671608, wiki: "https://www.google.com/search?q=Thassos+Food+Braila", tip: "restaurant" },
            { nume: "All Saints", lat: 45.25749069272165, lng: 27.959114917769334, wiki: "https://www.google.com/search?q=All+Saints+Braila", tip: "restaurant" },
            { nume: "KY'S Kebab", lat: 45.25636542931921, lng: 27.959029056836886, wiki: "https://www.google.com/search?q=KYS+Kebab+Braila", tip: "restaurant" }
        ];

        var markerePeHarta = L.featureGroup();

        // Parcurgem lista și punem fiecare punct pe hartă
        locatii.forEach(function(loc) {
            // Aici selectăm culoarea pe baza tipului setat în array
            var marker = L.marker([loc.lat, loc.lng], {
                icon: (loc.tip === 'restaurant') ? greenIcon : blueIcon
            });

            // Creăm design-ul pentru fereastra de detalii
            var popupContent = `
                <div style="text-align: center; padding: 5px;">
                    <h3 style="margin: 0 0 10px 0; font-size: 16px; color: ${loc.tip === 'restaurant' ? '#28a745' : '#0056b3'};">${loc.nume}</h3>
                    <a href="${loc.wiki}" target="_blank" style="background: ${loc.tip === 'restaurant' ? '#28a745' : '#0056b3'}; color: white; padding: 6px 12px; text-decoration: none; border-radius: 4px; font-weight: bold; display: inline-block; font-size: 13px;">
                        📖 Află mai multe
                    </a>
                </div>
            `;
            
            marker.bindPopup(popupContent);
            markerePeHarta.addLayer(marker);
        });

        // Adăugăm toate markerele pe hartă
        markerePeHarta.addTo(map);

        // Facem zoom automat pentru a cuprinde toate punctele pe ecran
        map.fitBounds(markerePeHarta.getBounds(), { padding: [30, 30] });

        // ================== ADĂUGARE LEGENDĂ ==================
        var legend = L.control({ position: 'bottomright' });
        legend.onAdd = function (map) {
            var div = L.DomUtil.create('div', 'info legend');
            div.style.background = 'white'; 
            div.style.padding = '12px'; 
            div.style.borderRadius = '8px';
            div.style.boxShadow = '0 4px 10px rgba(0,0,0,0.1)';
            div.style.fontSize = '14px';
            div.style.color = '#333';
            div.style.lineHeight = '24px';
            
            div.innerHTML = `
                <strong style="display:block; margin-bottom: 5px; font-size:15px;">Legenda Hărții</strong>
                <i style="background: #2A81CB; width: 12px; height: 12px; display: inline-block; border-radius: 50%; margin-right: 5px;"></i> Atracții Turistice<br>
                <i style="background: #2AAD27; width: 12px; height: 12px; display: inline-block; border-radius: 50%; margin-right: 5px;"></i> Restaurante & Fast Food
            `;
            return div;
        };
        legend.addTo(map);
    });
</script>

<?php include 'footer.php'; ?>