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
    
    <?php if(isset($needs_calendar) && $needs_calendar): ?>
        <link href='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css' rel='stylesheet' />
        <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js'></script>
    <?php endif; ?>

    <style>
        header { padding: 25px 0 !important; }
        .logo a { font-size: 32px !important; }
        .logo-tagline { font-size: 15px !important; display: block; margin-top: 2px; opacity: 0.7; }
        nav ul li a, .dropbtn { font-size: 16px !important; padding: 12px 18px !important; font-weight: 600; }
        .dropdown-content a { font-size: 15px !important; padding: 12px 16px !important; }
    </style>
</head>
<body>

<header>
    <div class="container">
        <h1 class="logo">
            <a href="acasa.php">
                <?= t('page_title') ?>
                <span class="logo-tagline">braila.ro</span>
            </a>
        </h1>
        
        <div class="header-weather-time" style="display: flex; justify-content: center; align-items: center; gap: 25px; font-weight: 700; font-size: 20px; color: var(--accent-primary); background: rgba(255, 255, 255, 0.05); padding: 12px 35px; border-radius: 35px; border: 1px solid rgba(255,255,255,0.1); backdrop-filter: blur(5px); box-shadow: 0 4px 15px rgba(0,0,0,0.2); min-width: 250px;">
            <span id="live-time-disp" style="letter-spacing: 1px;">--:--</span>
            <span style="color: rgba(255,255,255,0.2); font-weight: 300;">|</span>
            <span id="live-weather-disp" style="letter-spacing: 1px;">--</span>
        </div>

        <nav>
           <ul>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <li><a href="acasa.php" class="<?= $current_page == 'acasa.php' ? 'nav-active' : '' ?>"><?= t('nav_home') ?></a></li>
                    <li><a href="evenimente.php" class="<?= $current_page == 'evenimente.php' ? 'nav-active' : '' ?>"><?= t('nav_events') ?></a></li>
                    <li><a href="ghid.php" class="<?= $current_page == 'ghid.php' ? 'nav-active' : '' ?>"><?= t('nav_guide') ?></a></li>
                    <li><a href="trafic.php" class="nav-error <?= $current_page == 'trafic.php' ? 'nav-active' : '' ?>">Info Trafic</a></li>
                    <li><a href="transport.php" class="nav-success <?= $current_page == 'transport.php' ? 'nav-active' : '' ?>"><?= t('nav_transport') ?></a></li>
                    
                    <li class="dropdown-profil">
                        <a href="#" class="dropbtn <?= in_array($current_page, ['stiri.php', 'articole.php', 'articol.php']) ? 'nav-active' : '' ?>"><?= t('nav_blog') ?> ▼</a>
                        <div class="dropdown-content">
                            <a href="stiri.php">Știri Locale</a>
                            <a href="articole.php">Articole Originale</a>
                        </div>
                    </li>

                    <?php if (isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin'): ?>
                        <li><a href="statistici.php" class="<?= $current_page == 'statistici.php' ? 'nav-active' : '' ?>">Statistici</a></li>
                    <?php endif; ?>

                    <li><a href="#" onclick="openPopup('contactPopup')"><?= t('nav_contact') ?></a></li>

                    <li class="dropdown-language" style="position: relative;">
                        <a href="#" class="dropbtn" style="font-weight: 600;">
                            <?= $current_lang === 'ro' ? 'RO' : 'EN' ?> ▼
                        </a>
                        <div class="dropdown-content" style="width: 150px;">
                            <?php if ($current_lang !== 'ro'): ?>
                                <form method="POST" style="padding: 0;">
                                    <input type="hidden" name="change_language" value="1">
                                    <input type="hidden" name="language" value="ro">
                                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                    <button type="submit" style="width: 100%; text-align: left; padding: 12px 16px; border: none; background: none; cursor: pointer; font-size: 15px; font-weight: 600;">Română</button>
                                </form>
                            <?php endif; ?>
                            <?php if ($current_lang !== 'en'): ?>
                                <form method="POST" style="padding: 0;">
                                    <input type="hidden" name="change_language" value="1">
                                    <input type="hidden" name="language" value="en">
                                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                    <button type="submit" style="width: 100%; text-align: left; padding: 12px 16px; border: none; background: none; cursor: pointer; font-size: 15px; font-weight: 600;">English</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </li>

                    <li class="dropdown-profil">
                        <a href="#" class="dropbtn <?= in_array($current_page, ['profil.php', 'setari.php']) ? 'nav-active' : '' ?>"><?= htmlspecialchars($_SESSION['nume']) ?> ▼</a>
                        <div class="dropdown-content">
                            <a href="profil.php"><?= t('nav_tickets') ?></a>
                            <a href="setari.php"><?= t('nav_settings') ?></a>
                            <a href="logout.php" style="color: var(--accent-delete); font-weight: 600;"><?= t('nav_logout') ?></a>
                        </div>
                    </li>

                <?php else: ?>
                    <li><a href="#" onclick="openPopup('loginPopup')"><?= t('nav_login') ?></a></li>
                    <li><a href="#" onclick="openPopup('registerPopup')"><?= t('nav_register') ?></a></li>
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

    fetch('https://api.open-meteo.com/v1/forecast?latitude=45.2692&longitude=27.9575&current_weather=true')
        .then(res => res.json())
        .then(data => {
            var w = data.current_weather;
            var temp = Math.round(w.temperature);
            var weatherEl = document.getElementById('live-weather-disp');
            if(weatherEl) weatherEl.innerText = temp + '°C';
        })
        .catch(err => console.log('Eroare meteo:', err));
});
</script>