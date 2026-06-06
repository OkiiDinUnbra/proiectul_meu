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
    
    <!-- PWA Meta Tags -->
    <link rel="manifest" href="manifest.json">
    <meta name="theme-color" content="#007bff">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">

    <!-- Înregistrare Service Worker -->
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('sw.js')
                    .then(registration => {
                        console.log('PWA Service Worker înregistrat cu succes: ', registration.scope);
                    })
                    .catch(err => {
                        console.log('Eroare la înregistrarea Service Worker-ului: ', err);
                    });
            });
        }
    </script>
    
    <?php if(isset($needs_calendar) && $needs_calendar): ?>
        <link href='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css' rel='stylesheet' />
        <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js'></script>
    <?php endif; ?>

    <style>
        header { padding: 25px 0 !important; }
        
        /* Layout Header: stânga, centru, dreapta */
        header .container { display: flex; justify-content: space-between; align-items: center; }
        
        .logo a { font-size: 32px !important; }
        .logo-tagline { font-size: 15px !important; display: block; margin-top: 2px; opacity: 0.9; }
        
        nav ul { display: flex; align-items: center; margin: 0; padding: 0; }
        nav ul li a, .dropbtn { font-size: 16px !important; padding: 12px 18px !important; font-weight: 600; }
        .dropdown-content a { font-size: 15px !important; padding: 12px 16px !important; }

        /* Stiluri pentru Bara de Căutare Live (scoasă din meniu) */
        .search-container {
            position: relative;
            display: flex;
            align-items: center;
        }
        .search-input {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: var(--text-main);
            padding: 10px 15px 10px 35px;
            border-radius: 30px;
            font-size: 14px;
            outline: none;
            width: 220px;
            transition: 0.3s ease;
            font-family: inherit;
        }
        .search-input:focus {
            width: 300px;
            border-color: var(--link-color);
            background: rgba(255, 255, 255, 0.1);
        }
        .search-icon {
            position: absolute;
            left: 12px;
            color: rgba(255, 255, 255, 0.5);
            font-size: 15px;
            pointer-events: none;
        }
        .search-results-dropdown {
            position: absolute;
            top: 50px;
            left: 0;
            width: 100%;
            min-width: 260px;
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.5);
            backdrop-filter: blur(15px);
            -webkit-backdrop-filter: blur(15px);
            z-index: 1000;
            display: none;
            overflow: hidden;
        }
        .search-results-dropdown a {
            display: block;
            padding: 12px 15px !important;
            font-size: 14px !important;
            color: var(--text-main) !important;
            text-decoration: none;
            border-bottom: 1px solid rgba(255,255,255,0.05);
            transition: 0.2s;
        }
        .search-results-dropdown a:hover {
            background: rgba(255,255,255,0.05);
            color: var(--link-color) !important;
        }
        .search-type-badge {
            font-size: 11px;
            background: rgba(255,255,255,0.1);
            padding: 3px 6px;
            border-radius: 6px;
            margin-right: 8px;
            color: var(--text-light);
        }
        .no-results {
            padding: 15px;
            color: var(--text-light);
            text-align: center;
            font-size: 14px;
        }
    </style>
</head>
<body>

<header>
    <div class="container">
        <h1 class="logo" style="margin:0;">
            <a href="acasa.php">
                ⚓ <?= t('page_title') ?>
                <span class="logo-tagline" style="color: #3399ff; font-weight: 500;">Istorie, Cultură și Tradiții</span>
            </a>
        </h1>
        
        <div class="header-center" style="display: flex; align-items: center; gap: 20px;">
            <div class="header-weather-time" style="display: flex; justify-content: center; align-items: center; gap: 25px; font-weight: 700; font-size: 18px; color: var(--accent-primary); background: rgba(255, 255, 255, 0.05); padding: 10px 30px; border-radius: 35px; border: 1px solid rgba(255,255,255,0.1); backdrop-filter: blur(5px); box-shadow: 0 4px 15px rgba(0,0,0,0.2); min-width: 200px;">
                <span id="live-time-disp" style="letter-spacing: 1px;">--:--</span>
                <span style="color: rgba(255,255,255,0.2); font-weight: 300;">|</span>
                <span id="live-weather-disp" style="letter-spacing: 1px;">--</span>
            </div>

            <div class="search-container">
                <span class="search-icon">🔍</span>
                <input type="text" id="liveSearchInput" class="search-input" placeholder="Caută pe site..." autocomplete="off">
                <div id="liveSearchResults" class="search-results-dropdown"></div>
            </div>
        </div>

        <nav>
           <ul>
                <li><a href="acasa.php" class="<?= $current_page == 'acasa.php' ? 'nav-active' : '' ?>">Acasă</a></li>
                
                <li class="dropdown-profil">
                    <a href="#" class="dropbtn <?= ($current_page == 'evenimente.php' || $current_page == 'calendar.php') ? 'nav-active' : '' ?>">Evenimente ▼</a>
                    <div class="dropdown-content">
                        <a href="calendar.php?categorie=sportiv">⚽ Sportive</a>
                        <a href="calendar.php?categorie=cultural">🎭 Culturale</a>
                    </div>
                </li>

                <li><a href="ghid.php" class="<?= $current_page == 'ghid.php' ? 'nav-active' : '' ?>">Ghid Turistic</a></li>
                <li><a href="trafic.php" class="nav-error <?= $current_page == 'trafic.php' ? 'nav-active' : '' ?>">Info Trafic</a></li>
                <li><a href="transport.php" class="nav-success <?= $current_page == 'transport.php' ? 'nav-active' : '' ?>">Transport</a></li>
                
                <li class="dropdown-profil">
                    <a href="#" class="dropbtn <?= in_array($current_page, ['stiri.php', 'articole.php', 'articol.php']) ? 'nav-active' : '' ?>">Blog ▼</a>
                    <div class="dropdown-content">
                        <a href="stiri.php">Știri Locale</a>
                        <a href="articole.php">Articole Originale</a>
                    </div>
                </li>

                <li><a href="#" onclick="openPopup('contactPopup')">Contact</a></li>

                <li class="dropdown-language" style="position: relative;">
                    <a href="#" class="dropbtn" style="font-weight: 600;">
                        <?= $current_lang === 'ro' ? 'RO' : 'EN' ?> ▼
                    </a>
                    <div class="dropdown-content" style="width: 150px;">
                        <?php if ($current_lang !== 'ro'): ?>
                            <form method="POST" style="padding: 0;">
                                <input type="hidden" name="change_language" value="1">
                                <input type="hidden" name="language" value="ro">
                                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                                <button type="submit" style="width: 100%; text-align: left; padding: 12px 16px; border: none; background: none; cursor: pointer; font-size: 15px; font-weight: 600;">Română</button>
                            </form>
                        <?php endif; ?>
                        <?php if ($current_lang !== 'en'): ?>
                            <form method="POST" style="padding: 0;">
                                <input type="hidden" name="change_language" value="1">
                                <input type="hidden" name="language" value="en">
                                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                                <button type="submit" style="width: 100%; text-align: left; padding: 12px 16px; border: none; background: none; cursor: pointer; font-size: 15px; font-weight: 600;">English</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </li>

                <?php if (isset($_SESSION['user_id'])): ?>
                    <?php if (isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin'): ?>
                        <li><a href="statistici.php" class="<?= $current_page == 'statistici.php' ? 'nav-active' : '' ?>">Statistici</a></li>
                    <?php endif; ?>
                    
                    <li class="dropdown-profil">
                        <a href="#" class="dropbtn <?= in_array($current_page, ['profil.php', 'setari.php']) ? 'nav-active' : '' ?>"><?= htmlspecialchars($_SESSION['nume']) ?> ▼</a>
                       <div class="dropdown-content">
    <a href="profil.php">🎫 Biletele Mele</a>
    <a href="favorite.php">❤️ Favoritele Mele</a>
    <a href="setari.php">⚙️ Setări Profil</a>
    <a href="logout.php" style="color: var(--accent-delete); font-weight: 600;">🚪 Ieșire Cont</a>
</div>
                    </li>
                <?php else: ?>
                    <li><a href="#" onclick="openPopup('loginPopup')">Autentificare</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>
</header>

<script>
document.addEventListener("DOMContentLoaded", function() {
    // 1. Scriptul pentru Ceas și Vreme
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

    fetch('https://api.open-meteo.com/v1/forecast?latitude=45.2692&longitude=27.9575&current_weather=true')
        .then(res => res.json())
        .then(data => {
            var w = data.current_weather;
            var temp = Math.round(w.temperature);
            var weatherEl = document.getElementById('live-weather-disp');
            if(weatherEl) weatherEl.innerText = temp + '°C';
        })
        .catch(err => console.log('Eroare meteo:', err));

    // 2. Scriptul pentru Căutarea Live
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

            // Așteptăm 300ms după ce s-a oprit din scris pentru a face request-ul (Debounce)
            searchTimeout = setTimeout(() => {
                fetch(`cautare_live.php?q=${encodeURIComponent(query)}`)
                    .then(response => response.json())
                    .then(data => {
                        searchResults.innerHTML = ''; // Curățăm rezultatele vechi
                        
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

        // Ascundem rezultatele dacă dă click în afara barei de căutare
        document.addEventListener('click', function(event) {
            if (!searchInput.contains(event.target) && !searchResults.contains(event.target)) {
                searchResults.style.display = 'none';
            }
        });
        
        // Dacă dă click din nou pe bară, afișează rezultatele vechi
        searchInput.addEventListener('focus', function() {
            if (this.value.trim().length >= 2 && searchResults.innerHTML !== '') {
                searchResults.style.display = 'block';
            }
        });
    }
});
</script>