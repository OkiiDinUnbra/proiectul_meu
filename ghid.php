<?php 
require_once 'db_connect.php';
$page_title = t('guide_title') . " | " . t('page_title');
include 'header.php';

// Calculăm anotimpul curent pe baza lunii în care ne aflăm
$luna_curenta = (int)date('n');
$anotimp_curent = 'vara'; 

if ($luna_curenta >= 3 && $luna_curenta <= 5) {
    $anotimp_curent = 'primavara';
} elseif ($luna_curenta >= 6 && $luna_curenta <= 8) {
    $anotimp_curent = 'vara';
} elseif ($luna_curenta >= 9 && $luna_curenta <= 11) {
    $anotimp_curent = 'toamna';
} else {
    $anotimp_curent = 'iarna';
}
?>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<style>
    /* === SLIDESHOW & OVERLAY === */
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
        filter: blur(3px); 
        transform: scale(1.05); 
    }
    
    .mySlides.active { opacity: 1; }
    
    .overlay { 
        position: fixed;
        width: 100%; 
        height: 100%; 
        top: 0; 
        left: 0; 
        background: rgba(10, 25, 47, 0.45); 
        z-index: 1; 
    }

    /* === DASHBOARD LAYOUT EXTREM === */
    .guide-dashboard {
        position: relative;
        z-index: 2; 
        display: flex;
        flex-wrap: wrap;
        justify-content: space-between; 
        gap: 60px; 
        padding: 100px 60px 40px; 
        max-width: 100%; 
        margin: 0;
    }

    .section-header { margin-bottom: 20px; }
    .section-header h1, .section-header h2 { 
        font-size: 42px; 
        font-weight: 800;
        color: #ffffff; 
        margin-bottom: 5px; 
        text-shadow: 0 4px 20px rgba(0,0,0,0.9), 0 2px 5px rgba(0,0,0,0.8);
    }
    .section-header p { 
        font-size: 18px; 
        color: #e2e8f0; 
        margin: 0; 
        font-weight: 600;
        text-shadow: 0 2px 10px rgba(0,0,0,0.8);
    }

    /* COLONĂ STÂNGA: HARTA */
    .map-section {
        flex: 1; 
        min-width: 500px;
        max-width: 48%; 
        display: flex;
        flex-direction: column;
    }
    
    #harta-turistica {
        flex: 1; 
        height: 65vh; 
        min-height: 550px; 
        width: 100%; 
        border-radius: 16px; 
        box-shadow: 0 15px 35px rgba(0,0,0,0.6);
        border: 2px solid rgba(255, 255, 255, 0.15);
    }

    /* BARA DE CĂUTARE STRADĂ PENTRU HARTĂ */
    .harta-search-container { display: flex; gap: 15px; margin-top: 15px; }
    .harta-search-container input { flex: 1; padding: 15px 20px; border-radius: 12px; border: 1px solid rgba(255,255,255,0.2); background: rgba(0,0,0,0.5); color: #fff; font-family: inherit; font-size: 16px; outline: none; transition: 0.3s; backdrop-filter: blur(10px);}
    .harta-search-container input:focus { border-color: #38bdf8; background: rgba(0,0,0,0.7);}
    .harta-search-container button { padding: 15px 30px; background: #007bff; color: white; border: none; border-radius: 12px; font-weight: bold; cursor: pointer; transition: 0.2s; font-size: 16px; box-shadow: 0 4px 15px rgba(0, 123, 255, 0.4);}
    .harta-search-container button:hover { background: #38bdf8; color: #0f172a; }

    /* COLONĂ DREAPTA: ANOTIMPURI */
    .seasons-section {
        flex: 1; 
        min-width: 500px;
        max-width: 48%; 
        display: flex;
        flex-direction: column;
    }

    .seasons-content {
        display: flex;
        flex-direction: row; 
        gap: 20px;
        height: 65vh; 
        min-height: 550px;
    }

    .gallery-grid {
        flex: 1; 
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        grid-template-rows: repeat(2, 1fr); 
        gap: 20px;
        height: 100%;
    }

    .filter-vertical {
        width: 180px; 
        display: flex;
        flex-direction: column;
        gap: 15px;
    }

    .filter-btn {
        background: rgba(10, 25, 47, 0.7); 
        border: 1px solid rgba(255, 255, 255, 0.2);
        color: #ffffff;
        padding: 16px 20px;
        border-radius: 12px;
        cursor: pointer;
        font-weight: 700;
        font-size: 16px;
        text-align: left;
        transition: all 0.3s ease;
        backdrop-filter: blur(15px);
        display: flex;
        align-items: center;
        gap: 10px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.3);
    }

    .filter-btn:hover {
        background: rgba(10, 25, 47, 0.9);
        transform: translateX(5px);
    }

    .filter-btn.active {
        background: #38bdf8;
        color: #0f172a;
        border-color: #38bdf8;
        box-shadow: 0 4px 15px rgba(56, 189, 248, 0.6);
        transform: translateX(5px);
    }

    /* CARDURI POZE */
    .gallery-item {
        position: relative;
        border-radius: 16px;
        overflow: hidden;
        display: none; 
        box-shadow: 0 10px 25px rgba(0,0,0,0.5);
        border: 1px solid rgba(255, 255, 255, 0.15);
        width: 100%;
        height: 100%;
    }

    .gallery-item.show {
        display: block;
        animation: fadeInScale 0.4s ease-out forwards;
    }

    .gallery-item img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.6s ease;
    }

    .gallery-overlay {
        position: absolute;
        bottom: -100%;
        left: 0;
        width: 100%;
        padding: 30px 20px 20px;
        background: linear-gradient(to top, rgba(10, 25, 47, 0.95), transparent);
        transition: bottom 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        text-align: left;
    }

    .gallery-overlay h4 {
        color: #38bdf8;
        margin: 0 0 5px 0;
        font-size: 20px; 
        font-weight: 800;
        text-shadow: 0 2px 4px rgba(0,0,0,0.5);
    }

    .gallery-overlay p {
        color: #e2e8f0;
        margin: 0;
        font-size: 14px; 
        font-weight: 500;
    }

    .gallery-item:hover img { transform: scale(1.1); }
    .gallery-item:hover .gallery-overlay { bottom: 0; }

    @keyframes fadeInScale {
        0% { opacity: 0; transform: scale(0.95); }
        100% { opacity: 1; transform: scale(1); }
    }

    .legend {
        line-height: 18px;
        color: #0f172a;
        box-shadow: 0 5px 15px rgba(0,0,0,0.3);
        font-size: 14px;
        background: rgba(255, 255, 255, 0.95);
        border-radius: 10px;
    }

    @media (max-width: 1400px) {
        .guide-dashboard { flex-direction: column; padding: 120px 20px 60px; max-width: 100%; }
        .map-section, .seasons-section { min-width: 100%; max-width: 100%; }
        .seasons-content { flex-direction: column; height: auto; min-height: auto; }
        .filter-vertical { width: 100%; flex-direction: row; flex-wrap: wrap; }
        .filter-btn { flex: 1; justify-content: center; text-align: center; }
        .gallery-grid { height: 550px; }
        #harta-turistica { height: 550px; min-height: 550px; }
    }
</style>

<div class="slideshow-container">
    <div class="mySlides active" style="background-image: url('img/braila1.jpg');"></div>
    <div class="mySlides" style="background-image: url('img/braila2.jpg');"></div>
    <div class="mySlides" style="background-image: url('img/braila3.jpg');"></div>
    <div class="mySlides" style="background-image: url('img/braila4.jpg');"></div>
    <div class="mySlides" style="background-image: url('img/braila5.jpg');"></div>
    <div class="mySlides" style="background-image: url('img/braila6.jpg');"></div>
</div>
<div class="overlay"></div>

<div class="guide-dashboard fade-up-element">
    
    <div class="map-section">
        <div class="section-header">
            <h1><?= t('guide_map_title') ?></h1>
            <p><?= t('guide_map_desc') ?></p>
        </div>
        <div id="harta-turistica"></div>

        <div class="harta-search-container">
            <input type="text" id="inputCautaStrada" placeholder="Caută o stradă (ex: Călărașilor, Brăila)...">
            <button type="button" onclick="cautaStrada()">🔍 Navighează</button>
        </div>
    </div>

    <div class="seasons-section">
        <div class="section-header">
            <h2>Brăila în 4 Anotimpuri</h2>
            <p>Descoperă magia orașului direct din sezonul curent.</p>
        </div>

        <div class="seasons-content">
            
            <div class="gallery-grid">
                <div class="gallery-item primavara"><img src="img/primavara1.jpg" alt="Parcul Monument"><div class="gallery-overlay"><h4>Parcul Monument</h4><p>Natura revine la viață.</p></div></div>
                <div class="gallery-item primavara"><img src="img/primavara2.jpg" alt="Centrul Vechi Brăila"><div class="gallery-overlay"><h4>Centrul Vechi</h4><p>Clădirile luminate de soare.</p></div></div>
                <div class="gallery-item primavara"><img src="img/primavara3.jpg" alt="Festivalul Primăverii"><div class="gallery-overlay"><h4>Festivalul Primăverii</h4><p>Culoare și voie bună.</p></div></div>
                <div class="gallery-item primavara"><img src="img/primavara4.jpg" alt="Monument"><div class="gallery-overlay"><h4>Parcul Monument</h4><p>Peisaj verde și relaxare.</p></div></div>

                <div class="gallery-item vara"><img src="img/vara1.jpg" alt="Centrul Vechi Brăila"><div class="gallery-overlay"><h4>Centrul Vechi</h4><p>Seri calde pe pietonal.</p></div></div>
                <div class="gallery-item vara"><img src="img/vara2.jpg" alt="Faleza Brăila"><div class="gallery-overlay"><h4>Faleza Brăilei</h4><p>O plimbare relaxantă la apus.</p></div></div>
                <div class="gallery-item vara"><img src="img/vara3.jpg" alt="Podul Suspendat"><div class="gallery-overlay"><h4>Podul peste Dunăre</h4><p>Capodoperă inginerească.</p></div></div>
                <div class="gallery-item vara"><img src="img/vara4.jpg" alt="Ruinele cetății"><div class="gallery-overlay"><h4>Cetatea Brăila</h4><p>Istoria ascunsă în ziduri.</p></div></div>

                <div class="gallery-item toamna"><img src="img/toamna1.jpg" alt="Bulevardul Cuza"><div class="gallery-overlay"><h4>Bulevardul A.I. Cuza</h4><p>Frunze ruginii și arhitectură.</p></div></div>
                <div class="gallery-item toamna"><img src="img/toamna2.jpg" alt="Parcul Monument"><div class="gallery-overlay"><h4>Parcul Monument</h4><p>Covor de frunze aurii.</p></div></div>
                <div class="gallery-item toamna"><img src="img/toamna3.jpg" alt="Turnul de apă"><div class="gallery-overlay"><h4>Turnul de Apă</h4><p>Simbolul învăluit de toamnă.</p></div></div>
                <div class="gallery-item toamna"><img src="img/toamna4.jpg" alt="Lacu Sărat"><div class="gallery-overlay"><h4>Lacu Sărat</h4><p>Liniște și spiritualitate.</p></div></div>

                <div class="gallery-item iarna"><img src="img/iarna1.jpg" alt="Calea Călărașilor"><div class="gallery-overlay"><h4>Calea Călărașilor</h4><p>Magia luminițelor de iarnă.</p></div></div>
                <div class="gallery-item iarna"><img src="img/iarna2.jpg" alt="Bradul Falezei"><div class="gallery-overlay"><h4>Bradul de pe Faleză</h4><p>Atmosferă festivă la Dunăre.</p></div></div>
                <div class="gallery-item iarna"><img src="img/iarna3.jpg" alt="Centrul Vechi"><div class="gallery-overlay"><h4>Centrul Vechi</h4><p>Străzi acoperite de zăpadă.</p></div></div>
                <div class="gallery-item iarna"><img src="img/iarna4.jpg" alt="Ceasul Istoric"><div class="gallery-overlay"><h4>Ceasul Istoric</h4><p>Piața Traian sub fulgi de nea.</p></div></div>
            </div>

            <div class="filter-vertical">
                <button class="filter-btn" id="btn-primavara" onclick="filterSelection('primavara', this)">🌸 Primăvară</button>
                <button class="filter-btn" id="btn-vara" onclick="filterSelection('vara', this)">☀️ Vară</button>
                <button class="filter-btn" id="btn-toamna" onclick="filterSelection('toamna', this)">🍂 Toamnă</button>
                <button class="filter-btn" id="btn-iarna" onclick="filterSelection('iarna', this)">❄️ Iarnă</button>
            </div>

        </div>
    </div>
</div>

<script>
    // --- SCRIPT PENTRU SLIDESHOW ---
    let slideIndex = 0;
    const slides = document.querySelectorAll(".mySlides");
    
    function nextSlide() {
        slides[slideIndex].classList.remove("active");
        slideIndex = (slideIndex + 1) % slides.length;
        slides[slideIndex].classList.add("active");
    }
    
    if (slides.length > 0) {
        setInterval(nextSlide, 5000); 
    }

    // --- SCRIPT PENTRU GALERIA ANOTIMPURILOR ---
    function filterSelection(c, btnElement) {
        var items = document.querySelectorAll(".gallery-item");
        items.forEach(item => {
            item.classList.remove("show");
            if (item.classList.contains(c)) {
                item.classList.add("show");
            }
        });

        if (btnElement) {
            var btns = document.querySelectorAll(".filter-btn");
            btns.forEach(b => b.classList.remove("active"));
            btnElement.classList.add("active");
        }
    }

    document.addEventListener("DOMContentLoaded", () => {
        const defaultBtn = document.getElementById("btn-<?= $anotimp_curent ?>");
        if (defaultBtn) {
            filterSelection('<?= $anotimp_curent ?>', defaultBtn);
        }
    });

    // --- SCRIPT PENTRU HARTA LEAFLET ---
    var map; // Declarăm harta global pentru a o putea folosi în funcția de căutare

    document.addEventListener('DOMContentLoaded', function () {
        map = L.map('harta-turistica');

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
                    <h3 style="margin: 0 0 10px 0; font-size: 16px; color: ${loc.tip === 'restaurant' ? '#10b981' : '#38bdf8'};">${loc.nume}</h3>
                    <a href="${loc.wiki}" target="_blank" style="background: ${loc.tip === 'restaurant' ? '#10b981' : '#38bdf8'}; color: #0A192F; padding: 6px 12px; text-decoration: none; border-radius: 4px; font-weight: bold; display: inline-block; font-size: 13px;">
                        Află mai multe
                    </a>
                </div>
            `;
            
            marker.bindPopup(popupContent);
            markerePeHarta.addLayer(marker);
        });

        markerePeHarta.addTo(map);

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
            div.style.border = '1px solid #e2e8f0';
            div.innerHTML = `
                <strong style="display:block; margin-bottom: 5px; font-size:15px; color: #0f172a;">Legenda Hărții</strong>
                <i style="background: #2A81CB; width: 12px; height: 12px; display: inline-block; border-radius: 50%; margin-right: 5px;"></i> Atracții Turistice<br>
                <i style="background: #2AAD27; width: 12px; height: 12px; display: inline-block; border-radius: 50%; margin-right: 5px;"></i> Restaurante & Fast Food<br>
                <i style="background: #CB2B3E; width: 12px; height: 12px; display: inline-block; border-radius: 50%; margin-right: 5px;"></i> Locația Ta
            `;
            return div;
        };
        legend.addTo(map);
    });

    // === SCRIPT NOU PENTRU CĂUTAREA STRĂZILOR PE HARTĂ ===
    function cautaStrada() {
        var input = document.getElementById("inputCautaStrada").value;
        if (input.trim() === "") return;

        var query = encodeURIComponent(input + ", Brăila, România");
        var url = "https://nominatim.openstreetmap.org/search?format=json&limit=1&q=" + query;

        fetch(url)
            .then(response => response.json())
            .then(data => {
                if (data && data.length > 0) {
                    var gasitLat = parseFloat(data[0].lat);
                    var gasitLon = parseFloat(data[0].lon);
                    map.flyTo([gasitLat, gasitLon], 16, { animate: true, duration: 1.5 });
                } else {
                    alert("Nu am găsit această locație. Încearcă să fii mai specific (ex: Calea Călărașilor).");
                }
            })
            .catch(err => console.error("Eroare Nominatim:", err));
    }

    document.getElementById("inputCautaStrada").addEventListener("keypress", function(event) {
        if (event.key === "Enter") {
            event.preventDefault();
            cautaStrada();
        }
    });
</script>

<?php include 'footer.php'; ?>