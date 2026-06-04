<?php 
require_once 'db_connect.php';
$page_title = t('guide_title') . " | " . t('page_title');
include 'header.php';
?>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<style>
    /* STILURI PENTRU HARTA TURISTICĂ */
    .ghid-hero {
        padding: 160px 20px 60px;
        text-align: center;
        background: var(--bg-main);
        color: var(--text-main);
        margin-top: 0; 
    }
    .ghid-hero h1 { 
        font-size: 42px; 
        color: var(--text-main); 
        margin-bottom: 15px; 
    }
    .ghid-hero p { 
        font-size: 18px; 
        color: var(--text-light); 
        max-width: 800px; 
        margin: 0 auto 30px auto; 
    }
    
    #harta-turistica {
        height: 600px;
        width: 100%; 
        max-width: 1200px; 
        margin: 0 auto; 
        border-radius: 12px; 
        box-shadow: 0 8px 20px rgba(0,0,0,0.5);
        z-index: 1; 
        border: 2px solid var(--border-color);
    }

    .legend {
        line-height: 18px;
        color: #333;
        box-shadow: 0 0 15px rgba(0,0,0,0.2);
        font-size: 14px;
        background: rgba(255, 255, 255, 0.9);
    }

    /* STILURI PENTRU GALERIA ANOTIMPURILOR */
    .seasons-section {
        padding: 60px 20px 100px;
        max-width: 1200px;
        margin: 0 auto;
        text-align: center;
    }

    .seasons-title {
        font-size: 36px;
        color: var(--text-main);
        margin-bottom: 10px;
    }

    .seasons-subtitle {
        color: var(--text-light);
        margin-bottom: 40px;
        font-size: 18px;
    }

    .filter-container {
        margin-bottom: 40px;
        display: flex;
        justify-content: center;
        flex-wrap: wrap;
        gap: 15px;
    }

    .filter-btn {
        background: rgba(255, 255, 255, 0.05);
        border: 1px solid var(--border-color);
        color: var(--text-main);
        padding: 12px 25px;
        border-radius: 30px;
        cursor: pointer;
        font-weight: 600;
        font-size: 16px;
        transition: all 0.3s ease;
        backdrop-filter: blur(10px);
    }

    .filter-btn:hover {
        background: rgba(255, 255, 255, 0.1);
        transform: translateY(-2px);
    }

    .filter-btn.active {
        background: var(--link-color);
        color: white;
        border-color: var(--link-color);
        box-shadow: 0 4px 15px rgba(51, 153, 255, 0.4);
    }

    .gallery-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 25px;
    }

    .gallery-item {
        position: relative;
        border-radius: 16px;
        overflow: hidden;
        display: none;
        aspect-ratio: 4/3;
        box-shadow: 0 5px 15px rgba(0,0,0,0.2);
    }

    .gallery-item.show {
        display: block;
        animation: fadeInScale 0.5s ease-out;
    }

    .gallery-item img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.5s ease;
    }

    .gallery-overlay {
        position: absolute;
        bottom: -100%;
        left: 0;
        width: 100%;
        padding: 20px;
        background: linear-gradient(to top, rgba(0,0,0,0.9), transparent);
        transition: bottom 0.3s ease;
        text-align: left;
    }

    .gallery-overlay h4 {
        color: white;
        margin: 0 0 5px 0;
        font-size: 18px;
    }

    .gallery-overlay p {
        color: #ddd;
        margin: 0;
        font-size: 13px;
    }

    .gallery-item:hover img {
        transform: scale(1.1);
    }

    .gallery-item:hover .gallery-overlay {
        bottom: 0;
    }

    @keyframes fadeInScale {
        0% { opacity: 0; transform: scale(0.9); }
        100% { opacity: 1; transform: scale(1); }
    }
</style>

<section class="ghid-hero">
    <h1><?= t('guide_map_title') ?></h1>
    <p><?= t('guide_map_desc') ?></p>
    
    <div id="harta-turistica"></div>
</section>

<div class="seasons-section">
    <h2 class="seasons-title">Brăila în 4 Anotimpuri</h2>
    <p class="seasons-subtitle">Descoperă magia orașului pe tot parcursul anului.</p>

    <div class="filter-container">
        <button class="filter-btn active" onclick="filterSelection('all')">🌍 Toate</button>
        <button class="filter-btn" onclick="filterSelection('primavara')">🌸 Primăvară</button>
        <button class="filter-btn" onclick="filterSelection('vara')">☀️ Vară</button>
        <button class="filter-btn" onclick="filterSelection('toamna')">🍂 Toamnă</button>
        <button class="filter-btn" onclick="filterSelection('iarna')">❄️ Iarnă</button>
    </div>

    <div class="gallery-grid">
        
        <div class="gallery-item primavara">
            <img src="img/primavara1.jpg" alt="Parcul Monument">
            <div class="gallery-overlay">
                <h4>Parcul Monument</h4>
                <p>Natura revine la viață pe aleile parcului.</p>
            </div>
        </div>
        <div class="gallery-item primavara">
            <img src="img/primavara2.jpg" alt="Centrul Vechi Brăila">
            <div class="gallery-overlay">
                <h4>Centrul Vechi</h4>
                <p>Clădirile istorice luminate de soarele blând de primăvară.</p>
            </div>
        </div>
        <div class="gallery-item primavara">
            <img src="img/primavara3.jpg" alt="Festivalul Primăverii">
            <div class="gallery-overlay">
                <h4>Festivalul Primăverii</h4>
                <p>Culoare și voie bună în Centrul Vechi al orașului.</p>
            </div>
        </div>
        <div class="gallery-item primavara">
            <img src="img/primavara4.jpg" alt="Monument">
            <div class="gallery-overlay">
                <h4>Parcul Monument</h4>
                <p>Peisaj verde și relaxare în aer liber.</p>
            </div>
        </div>

        <div class="gallery-item vara">
            <img src="img/vara1.jpg" alt="Centrul Vechi Brăila">
            <div class="gallery-overlay">
                <h4>Centrul Vechi</h4>
                <p>Seri calde de vară petrecute pe străduțele pietonale.</p>
            </div>
        </div>
        <div class="gallery-item vara">
            <img src="img/vara2.jpg" alt="Faleza Brăila">
            <div class="gallery-overlay">
                <h4>Faleza Brăilei</h4>
                <p>O plimbare relaxantă la apus pe malul Dunării.</p>
            </div>
        </div>
        <div class="gallery-item vara">
            <img src="img/vara3.jpg" alt="Podul Suspendat peste Dunăre">
            <div class="gallery-overlay">
                <h4>Podul peste Dunăre</h4>
                <p>O capodoperă inginerească impresionantă la apus.</p>
            </div>
        </div>
        <div class="gallery-item vara">
            <img src="img/vara4.jpg" alt="Ruinele cetății Brăila">
            <div class="gallery-overlay">
                <h4>Ruinele Cetății Brăila</h4>
                <p>Istoria orașului ascunsă în vechile ziduri.</p>
            </div>
        </div>

        <div class="gallery-item toamna">
            <img src="img/toamna1.jpg" alt="Bulevardul A.I.Cuza">
            <div class="gallery-overlay">
                <h4>Bulevardul A.I. Cuza</h4>
                <p>Frunze ruginii și arhitectură spectaculoasă.</p>
            </div>
        </div>
        <div class="gallery-item toamna">
            <img src="img/toamna2.jpg" alt="Parcul Monument Toamna">
            <div class="gallery-overlay">
                <h4>Parcul Monument</h4>
                <p>Un covor de frunze aurii acoperă aleile liniștite.</p>
            </div>
        </div>
        <div class="gallery-item toamna">
            <img src="img/toamna3.jpg" alt="Turnul de apă Grădina Publică">
            <div class="gallery-overlay">
                <h4>Turnul de Apă (Grădina Publică)</h4>
                <p>Simbolul orașului învăluit de culorile toamnei.</p>
            </div>
        </div>
        <div class="gallery-item toamna">
            <img src="img/toamna4.jpg" alt="Mănăstirea Lacul Sărat">
            <div class="gallery-overlay">
                <h4>Mănăstirea Lacu Sărat</h4>
                <p>Liniște și spiritualitate în inima stațiunii.</p>
            </div>
        </div>

        <div class="gallery-item iarna">
            <img src="img/iarna1.jpg" alt="Bulevardul Calea Călărașilor">
            <div class="gallery-overlay">
                <h4>Bulevardul Calea Călărașilor</h4>
                <p>Magia sărbătorilor și luminițele de iarnă.</p>
            </div>
        </div>
        <div class="gallery-item iarna">
            <img src="img/iarna2.jpg" alt="Bradul Falezei Brăilei">
            <div class="gallery-overlay">
                <h4>Bradul de pe Faleză</h4>
                <p>Atmosferă festivă pe malul înghețat al Dunării.</p>
            </div>
        </div>
        <div class="gallery-item iarna">
            <img src="img/iarna3.jpg" alt="Centrul Vechi Iarna">
            <div class="gallery-overlay">
                <h4>Centrul Vechi</h4>
                <p>Străzile istorice acoperite de un strat proaspăt de zăpadă.</p>
            </div>
        </div>
        <div class="gallery-item iarna">
            <img src="img/iarna4.jpg" alt="Ceasul Istoric al Brăilei">
            <div class="gallery-overlay">
                <h4>Ceasul Istoric</h4>
                <p>Piața Traian sub magia fulgilor de nea.</p>
            </div>
        </div>

    </div>
</div>

<script>
    // --- SCRIPT PENTRU GALERIA ANOTIMPURILOR REPARAT ---
    filterSelection("all");

    function filterSelection(c) {
        var x = document.getElementsByClassName("gallery-item");
        
        for (var i = 0; i < x.length; i++) {
            x[i].classList.remove("show");
            
            // Folosim classList.contains() pentru a verifica clasa EXACTĂ
            if (c === "all" || x[i].classList.contains(c)) {
                setTimeout((function(item) {
                    return function() { item.classList.add("show"); }
                })(x[i]), 50);
            }
        }

        var btns = document.getElementsByClassName("filter-btn");
        for (var i = 0; i < btns.length; i++) {
            btns[i].addEventListener("click", function() {
                var current = document.getElementsByClassName("active");
                if (current.length > 0) {
                    current[0].className = current[0].className.replace(" active", "");
                }
                this.className += " active";
            });
        }
    }

    // --- SCRIPT PENTRU HARTA LEAFLET (Codul tău original) ---
    document.addEventListener('DOMContentLoaded', function () {
        var map = L.map('harta-turistica');

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(map);

        const blueIcon = L.icon({
            iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-blue.png',
            iconSize: [25, 41], iconAnchor: [12, 41], popupAnchor: [1, -34]
        });

        const greenIcon = L.icon({
            iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-green.png',
            iconSize: [25, 41], iconAnchor: [12, 41], popupAnchor: [1, -34]
        });

        const redIcon = L.icon({
            iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-red.png',
            iconSize: [25, 41], iconAnchor: [12, 41], popupAnchor: [1, -34]
        });

        var locatii = [
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
            { nume: "McDonald's (Barieră)", lat: 45.2550517694067, lng: 27.958166039091335, wiki: "https://www.google.com/search?q=McDonalds+Bariera+Braila", tip: "restaurant" },
            { nume: "Domino's Pizza", lat: 45.25533324134098, lng: 27.95837057298481, wiki: "https://www.google.com/search?q=Dominos+Pizza+Braila", tip: "restaurant" },
            { nume: "KFC Bariera", lat: 45.25598001783551, lng: 27.960233624981157, wiki: "https://www.google.com/search?q=KFC+Bariera+Braila", tip: "restaurant" },
            { nume: "Restaurant Carol", lat: 45.27210479958391, lng: 27.975163712161493, wiki: "https://www.google.com/search?q=Restaurant+Carol+Braila", tip: "restaurant" },
            { nume: "Cherhanaua", lat: 45.26934443572491, lng: 27.97946802340389, wiki: "https://www.google.com/search?q=Cherhanaua+Braila", tip: "restaurant" },
            { nume: "Heavens", lat: 45.267897478489445, lng: 27.972469424073214, wiki: "https://www.google.com/search?q=Heavens+Braila", tip: "restaurant" },
            { nume: "Bella Italia", lat: 45.2764115374821, lng: 27.9666702642137, wiki: "https://www.google.com/search?q=Bella+Italia+Braila", tip: "restaurant" },
            { nume: "The Irish Pub", lat: 45.26533042429084, lng: 27.97059461115861, wiki: "https://www.google.com/search?q=The+Irish+Pub+Braila", tip: "restaurant" },
            { nume: "Thassos Food", lat: 45.258678319899076, lng: 27.96140826671608, wiki: "https://www.google.com/search?q=Thassos+Food+Braila", tip: "restaurant" },
            { nume: "All Saints", lat: 45.25749069272165, lng: 27.959114917769334, wiki: "https://www.google.com/search?q=All+Saints+Braila", tip: "restaurant" },
            { nume: "KY'S Kebab", lat: 45.25636542931921, lng: 27.959029056836886, wiki: "https://www.google.com/search?q=KYS+Kebab+Braila", tip: "restaurant" }
        ];
        
        var markerePeHarta = L.featureGroup();

        locatii.forEach(function(loc) {
            var marker = L.marker([loc.lat, loc.lng], {
                icon: (loc.tip === 'restaurant') ? greenIcon : blueIcon
            });

            var popupContent = `
                <div style="text-align: center; padding: 5px;">
                    <h3 style="margin: 0 0 10px 0; font-size: 16px; color: ${loc.tip === 'restaurant' ? '#28a745' : '#0056b3'};">${loc.nume}</h3>
                    <a href="${loc.wiki}" target="_blank" style="background: ${loc.tip === 'restaurant' ? '#28a745' : '#0056b3'}; color: white; padding: 6px 12px; text-decoration: none; border-radius: 4px; font-weight: bold; display: inline-block; font-size: 13px;">
                        Află mai multe
                    </a>
                </div>
            `;
            
            marker.bindPopup(popupContent);
            markerePeHarta.addLayer(marker);
        });
        markerePeHarta.addTo(map);

        // Tracking live locație utilizator
        map.locate({setView: true, maxZoom: 14});
        map.on('locationfound', function(e) {
            var radius = e.accuracy / 2;
            L.marker(e.latlng, {icon: redIcon}).addTo(map)
                .bindPopup("Te afli aici! (Acuratețe: " + Math.round(radius) + " metri)").openPopup();
            L.circle(e.latlng, radius).addTo(map);
        });
        
        map.on('locationerror', function(e) {
            console.log("Locația nu a putut fi preluată. Harta se centrează pe Brăila implicit.");
            map.fitBounds(markerePeHarta.getBounds(), { padding: [30, 30] });
        });
        
        var legend = L.control({ position: 'bottomright' });
        legend.onAdd = function (map) {
            var div = L.DomUtil.create('div', 'info legend');
            div.style.padding = '12px'; 
            div.style.borderRadius = '8px';
            div.style.border = '1px solid #ccc';
            div.innerHTML = `
                <strong style="display:block; margin-bottom: 5px; font-size:15px;">Legenda Hărții</strong>
                <i style="background: #2A81CB; width: 12px; height: 12px; display: inline-block; border-radius: 50%; margin-right: 5px;"></i> Atracții Turistice<br>
                <i style="background: #2AAD27; width: 12px; height: 12px; display: inline-block; border-radius: 50%; margin-right: 5px;"></i> Restaurante & Fast Food<br>
                <i style="background: #CB2B3E; width: 12px; height: 12px; display: inline-block; border-radius: 50%; margin-right: 5px;"></i> Locația Ta
            `;
            return div;
        };
        legend.addTo(map);
    });
</script>

<?php include 'footer.php'; ?>