<?php
require_once 'db_connect.php'; 

$current_lang = getCurrentLanguage();
$page_title = isset($page_title) ? $page_title : t('page_title');
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="<?= $current_lang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?></title>

    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
    
    <link rel="manifest" href="manifest.json">
    <meta name="theme-color" content="#0A192F">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">

    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('sw.js')
                    .then(registration => console.log('PWA Service Worker înregistrat cu succes: ', registration.scope))
                    .catch(err => console.log('Eroare la înregistrarea Service Worker-ului: ', err));
            });
        }
    </script>
    
    <?php if(isset($needs_calendar) && $needs_calendar): ?>
        <link href='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css' rel='stylesheet' />
        <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js'></script>
    <?php endif; ?>

    <style>
        html, body { margin: 0 !important; padding: 0 !important; }

        /* === HEADER PREMIUM: NAVY BLUE & WHITE === */
        header { 
            padding: 15px 0 !important; 
            background: rgba(10, 25, 47, 0.98) !important; 
            backdrop-filter: blur(12px);
            border-bottom: 1px solid rgba(255,255,255,0.05);
            box-shadow: 0 4px 20px rgba(0,0,0,0.4);
            position: fixed; 
            top: 0;
            left: 0;
            width: 100%;
            box-sizing: border-box;
            z-index: 9999;
            margin-top: 0 !important;
        }
        
        header .container { 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            width: 100%;
            max-width: 100%; /* Folosește 100% din lățimea monitorului */
            margin: 0;
            padding: 0 40px; /* Margini laterale generoase */
            box-sizing: border-box;
        }

        /* STÂNGA: Logo */
        .header-logo { 
            flex: 1; 
            display: flex; 
            justify-content: flex-start; 
        }
        .logo a { 
            font-size: 36px !important; /* Mărit */
            color: #ffffff !important; 
            text-decoration: none; 
            font-weight: 800;
            letter-spacing: 0.5px;
            white-space: nowrap;
        }
        .logo-tagline { 
            font-size: 16px !important; /* Mărit */
            display: block; 
            margin-top: 2px; 
            font-weight: 500; 
            color: #8892b0 !important; 
            white-space: nowrap;
        }
        
        /* CENTRU: Vreme & Căutare */
        .header-center {
            flex: 1.5; /* Primește spațiu mai generos */
            display: flex; 
            justify-content: center; 
            align-items: center; 
            gap: 30px; /* Extins spațiul dintre ele */
        }

        /* WIDGET VREME/ORĂ - SCALAT FOARTE MARE */
        .header-weather-time {
            display: flex; align-items: center; gap: 20px; 
            font-weight: 800; font-size: 21px; /* MĂRIT DE 2.5X */
            color: #ffffff; 
            background: rgba(255, 255, 255, 0.05); 
            padding: 15px 40px; 
            border-radius: 50px; 
            border: 1px solid rgba(255,255,255,0.1); 
        }

        .search-container { position: relative; display: flex; align-items: center; }
        .search-input {
            background: rgba(0, 0, 0, 0.2); 
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: #ffffff; padding: 12px 20px 12px 45px; border-radius: 30px; font-size: 16px; /* Mărit */
            outline: none; width: 220px; transition: 0.3s ease; font-family: inherit;
        }
        .search-input::placeholder { color: #8892b0; }
        .search-input:focus { 
            width: 320px; /* Se extinde mai mult la click */
            border-color: #38bdf8; 
            background: rgba(0, 0, 0, 0.4); 
            box-shadow: 0 0 0 3px rgba(56, 189, 248, 0.1);
        }
        .search-icon { position: absolute; left: 18px; color: #8892b0; font-size: 16px; pointer-events: none; }
        
        .search-results-dropdown {
            position: absolute; top: 55px; left: 0; width: 100%; min-width: 320px;
            background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.3); z-index: 1000; display: none; overflow: hidden;
            text-align: left;
        }
        .search-results-dropdown a { display: block; padding: 12px 15px !important; font-size: 15px !important; color: #0f172a !important; text-decoration: none; border-bottom: 1px solid #f1f5f9; transition: 0.2s; }
        .search-results-dropdown a:hover { background: #f8fafc; color: #007bff !important; }
        .search-type-badge { font-size: 12px; background: #e2e8f0; padding: 4px 8px; border-radius: 6px; margin-right: 8px; color: #475569; font-weight: bold;}
        .no-results { padding: 15px; color: #64748b; text-align: center; font-size: 15px; }

        /* DREAPTA: Meniu Navigație */
        nav.header-nav { 
            flex: 2; /* Are nevoie de cel mai mult spațiu */
            display: flex; 
            justify-content: flex-end; 
        }
        nav.header-nav ul { display: flex; align-items: center; margin: 0; padding: 0; list-style: none; gap: 10px; /* Extins */ }
        
        nav.header-nav ul li a, .dropbtn { 
            font-size: 18px !important; /* Mărit considerabil pentru lizibilitate */
            padding: 12px 16px !important; 
            font-weight: 700; 
            color: #e2e8f0 !important; 
            text-decoration: none;
            transition: all 0.3s ease;
            border-radius: 6px;
            white-space: nowrap;
        }
        
        nav.header-nav ul li a:hover, .dropbtn:hover { 
            color: #38bdf8 !important; 
            background: rgba(56, 189, 248, 0.05);
        }
        
        .dropdown-content {
            background: #ffffff;
            border-radius: 8px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            border: 1px solid #e2e8f0;
            overflow: hidden;
            margin-top: 5px;
            text-align: left;
        }
        .dropdown-content a { 
            font-size: 16px !important; /* Mărit */
            padding: 14px 20px !important; 
            color: #0f172a !important; 
            font-weight: 600;
        }
        .dropdown-content a:hover { 
            color: #007bff !important; 
            background: #f1f5f9; 
        }
    </style>
</head>
<body>

<header>
    <div class="container">
        <div class="header-logo">
            <h1 class="logo" style="margin:0;">
                <a href="acasa.php">
                    ⚓ <?= t('page_title') ?>
                    <span class="logo-tagline">Istorie, Cultură și Tradiții</span>
                </a>
            </h1>
        </div>
        
        <div class="header-center">
            <div class="header-weather-time">
                <span id="live-time-disp">--:--</span>
                <span style="color: rgba(255,255,255,0.2);">|</span>
                <span id="live-weather-disp">--</span>
            </div>

            <div class="search-container">
                <span class="search-icon">🔍</span>
                <input type="text" id="liveSearchInput" class="search-input" placeholder="Caută pe site..." autocomplete="off">
                <div id="liveSearchResults" class="search-results-dropdown"></div>
            </div>
        </div>

        <nav class="header-nav">
           <ul>
                <li><a href="acasa.php">Acasă</a></li>
                
                <li class="dropdown-profil">
                    <a href="#" class="dropbtn">Evenimente ▼</a>
                    <div class="dropdown-content">
                        <a href="calendar.php?categorie=sportiv">⚽ Sportive</a>
                        <a href="calendar.php?categorie=cultural">🎭 Culturale</a>
                    </div>
                </li>

                <li><a href="ghid.php">Ghid Turistic</a></li>
                <li><a href="trafic.php">Info Trafic</a></li>
                <li><a href="transport.php">Transport</a></li>
                
                <li class="dropdown-profil">
                    <a href="#" class="dropbtn">Blog ▼</a>
                    <div class="dropdown-content">
                        <a href="stiri.php">Știri Locale</a>
                        <a href="articole.php">Articole Originale</a>
                    </div>
                </li>

                <li><a href="#" onclick="openPopup('contactPopup')">Contact</a></li>

                <li class="dropdown-language" style="position: relative;">
                    <a href="#" class="dropbtn">
                        <?= $current_lang === 'ro' ? 'RO' : 'EN' ?> ▼
                    </a>
                    <div class="dropdown-content" style="width: 150px;">
                        <?php if ($current_lang !== 'ro'): ?>
                            <form method="POST" style="padding: 0; margin: 0;">
                                <input type="hidden" name="change_language" value="1">
                                <input type="hidden" name="language" value="ro">
                                <button type="submit" style="width: 100%; text-align: left; padding: 12px 16px; border: none; background: none; cursor: pointer; font-size: 16px; font-weight: 600;">Română</button>
                            </form>
                        <?php endif; ?>
                        <?php if ($current_lang !== 'en'): ?>
                            <form method="POST" style="padding: 0; margin: 0;">
                                <input type="hidden" name="change_language" value="1">
                                <input type="hidden" name="language" value="en">
                                <button type="submit" style="width: 100%; text-align: left; padding: 12px 16px; border: none; background: none; cursor: pointer; font-size: 16px; font-weight: 600;">English</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </li>

                <?php if (isset($_SESSION['user_id'])): ?>
                    <?php if (isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin'): ?>
                        <li><a href="statistici.php">Statistici</a></li>
                    <?php endif; ?>
                    
                    <li class="dropdown-profil">
                        <a href="#" class="dropbtn" style="color: #38bdf8 !important;">👤 <?= htmlspecialchars($_SESSION['nume']) ?> ▼</a>
                       <div class="dropdown-content" style="right: 0;">
                            <a href="profil.php">🎫 Biletele Mele</a>
                            <a href="favorite.php">❤️ Favoritele Mele</a>
                            
                            <?php if (isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin'): ?>
                                <a href="admin_tichete.php" style="color: #dc3545 !important; font-weight: bold; border-top: 1px solid #e2e8f0; margin-top: 5px; padding-top: 10px;">🛠️ Panou Tichete</a>
                            <?php endif; ?>
                            
                            <a href="setari.php">⚙️ Setări Profil</a>
                            <a href="logout.php" style="color: #dc3545 !important; font-weight: 600;">🚪 Ieșire Cont</a>
                        </div>
                    </li>
                <?php else: ?>
                    <li><a href="#" onclick="openPopup('loginPopup')" style="background: #007bff; color: white !important; padding: 10px 18px !important; border-radius: 6px; margin-left: 10px;">Autentificare</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>
</header>

<script>
document.addEventListener("DOMContentLoaded", function() {
    function refreshClock() {
        var el = document.getElementById('live-time-disp');
        if(el) {
            var d = new Date();
            var h = d.getHours() < 10 ? '0' + d.getHours() : d.getHours();
            var m = d.getMinutes() < 10 ? '0' + d.getMinutes() : d.getMinutes();
            el.innerText = h + ':' + m;
        }
    }
    
    refreshClock();
    setInterval(refreshClock, 1000);

    // INTEGRARE API METEO COMPLETĂ (Grade + Stare Vreme)
    fetch('https://api.open-meteo.com/v1/forecast?latitude=45.2692&longitude=27.9575&current_weather=true')
        .then(res => res.json())
        .then(data => {
            var w = data.current_weather;
            var temp = Math.round(w.temperature);
            var code = w.weathercode;
            var condition = "🌤️ Variabil"; // Default fallback

            // Maparea codurilor WMO în text/emoji (Organizația Meteorologică Mondială)
            if (code === 0) condition = "☀️ Senin";
            else if (code === 1 || code === 2) condition = "⛅ Parțial noros";
            else if (code === 3) condition = "☁️ Înnorat";
            else if (code >= 45 && code <= 48) condition = "🌫️ Ceață";
            else if (code >= 51 && code <= 55) condition = "🌧️ Burniță";
            else if (code >= 61 && code <= 65) condition = "☔ Ploaie";
            else if (code >= 71 && code <= 77) condition = "❄️ Ninsoare";
            else if (code >= 95) condition = "⛈️ Furtună";

            var weatherEl = document.getElementById('live-weather-disp');
            if(weatherEl) weatherEl.innerHTML = condition + " &nbsp; " + temp + "°C";
        })
        .catch(err => console.log('Eroare meteo:', err));

    const searchInput = document.getElementById('liveSearchInput');
    const searchResults = document.getElementById('liveSearchResults');
    let searchTimeout;

    if(searchInput) {
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            const query = this.value.trim();

            if (query.length < 2) {
                searchResults.style.display = 'none';
                return;
            }

            searchTimeout = setTimeout(() => {
                fetch(`cautare_live.php?q=${encodeURIComponent(query)}`)
                    .then(response => response.json())
                    .then(data => {
                        searchResults.innerHTML = ''; 
                        
                        if (data.length === 0) {
                            searchResults.innerHTML = '<div class="no-results">Nu s-a găsit nimic pentru "' + query + '" 🤷‍♂️</div>';
                        } else {
                            data.forEach(item => {
                                const a = document.createElement('a');
                                a.href = item.url;
                                a.innerHTML = `<span class="search-type-badge">${item.tip}</span> ${item.titlu}`;
                                searchResults.appendChild(a);
                            });
                        }
                        searchResults.style.display = 'block';
                    })
                    .catch(err => console.error('Eroare la căutare:', err));
            }, 300);
        });

        document.addEventListener('click', function(event) {
            if (!searchInput.contains(event.target) && !searchResults.contains(event.target)) {
                searchResults.style.display = 'none';
            }
        });
        
        searchInput.addEventListener('focus', function() {
            if (this.value.trim().length >= 2 && searchResults.innerHTML !== '') {
                searchResults.style.display = 'block';
            }
        });
    }
});
</script>