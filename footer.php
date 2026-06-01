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
</head>
<body>

<header>
    <div class="container">
        <h1 class="logo">
            <a href="acasa.php">
                ⚓ <?= t('page_title') ?>
                <span class="logo-tagline">descoperă orașul tău</span>
            </a>
        </h1>
        
        <div class="header-weather-time">
            <span id="live-time">--:--</span>
            <span style="color: rgba(255,255,255,0.2); font-weight: 300;">|</span>
            <span id="live-weather">⏳</span>
        </div>

        <nav>
           <ul>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <li><a href="acasa.php" <?= $current_page==='acasa.php'?'class="nav-active"':'' ?>><?= t('nav_home') ?></a></li>
                    <li><a href="evenimente.php" <?= $current_page==='evenimente.php'||$current_page==='evenimentextins.php'?'class="nav-active"':'' ?>><?= t('nav_events') ?></a></li>
                    <li><a href="ghid.php" <?= $current_page==='ghid.php'?'class="nav-active"':'' ?>><?= t('nav_guide') ?></a></li>
                    <li><a href="trafic.php" class="nav-warning<?= $current_page==='trafic.php'?' nav-active':'' ?>">🚦 Info Trafic</a></li>
                    <li><a href="transport.php" class="nav-success<?= $current_page==='transport.php'?' nav-active':'' ?>"><?= t('nav_transport') ?></a></li>
                    
                    <li class="dropdown-profil">
                        <a href="#" class="dropbtn<?= in_array($current_page,['stiri.php','articole.php','articol.php','admin_articol.php'])?' nav-active':'' ?>"><?= t('nav_blog') ?> ▼</a>
                        <div class="dropdown-content">
                            <a href="stiri.php">📰 Știri Locale</a>
                            <a href="articole.php">📝 Articole Originale</a>
                        </div>
                    </li>

                    <?php if (isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin'): ?>
                        <li><a href="statistici.php" class="nav-warning<?= $current_page==='statistici.php'?' nav-active':'' ?>"><?= t('nav_statistics') ?></a></li>
                    <?php endif; ?>

                    <li><a href="#" onclick="openPopup('contactPopup')"><?= t('nav_contact') ?></a></li>

                    <li class="dropdown-language" style="position: relative;">
                        <a href="#" class="dropbtn" style="font-weight: 600;">
                            <?= $current_lang === 'ro' ? '🇷🇴 RO' : '🇬🇧 EN' ?> ▼
                        </a>
                        <div class="dropdown-content" style="width: 150px;">
                            <?php if ($current_lang !== 'ro'): ?>
                                <form method="POST" style="padding: 0;">
                                    <input type="hidden" name="change_language" value="1">
                                    <input type="hidden" name="language" value="ro">
                                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                    <button type="submit" style="width:100%;text-align:left;padding:9px 14px;border:none;background:none;cursor:pointer;font-size:13px;color:rgba(255,255,255,0.7);">🇷🇴 Română</button>
                                </form>
                            <?php endif; ?>
                            <?php if ($current_lang !== 'en'): ?>
                                <form method="POST" style="padding: 0;">
                                    <input type="hidden" name="change_language" value="1">
                                    <input type="hidden" name="language" value="en">
                                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                    <button type="submit" style="width:100%;text-align:left;padding:9px 14px;border:none;background:none;cursor:pointer;font-size:13px;color:rgba(255,255,255,0.7);">🇬🇧 English</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </li>

                    <li class="dropdown-profil">
                        <a href="#" class="dropbtn<?= in_array($current_page,['profil.php','setari.php'])?' nav-active':'' ?>">👤 <?= htmlspecialchars($_SESSION['nume']) ?> ▼</a>
                        <div class="dropdown-content">
                            <a href="profil.php"><?= t('nav_tickets') ?></a>
                            <a href="setari.php"><?= t('nav_settings') ?></a>
                            <a href="logout.php" style="color:var(--accent-delete)!important"><?= t('nav_logout') ?></a>
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
document.addEventListener('DOMContentLoaded', function() {
    function updateTime() {
        const acum = new Date();
        const ora = acum.getHours().toString().padStart(2, '0');
        const min = acum.getMinutes().toString().padStart(2, '0');
        document.getElementById('live-time').textContent = ora + ':' + min;
    }
    setInterval(updateTime, 1000);
    updateTime();

    async function fetchWeather() {
        try {
            const response = await fetch('https://api.open-meteo.com/v1/forecast?latitude=45.2692&longitude=27.9575&current_weather=true');
            const data = await response.json();
            const temp = Math.round(data.current_weather.temperature);
            const isDay = data.current_weather.is_day;
            const code = data.current_weather.weathercode;
            let icon = isDay ? '☀️' : '🌙';
            if (code >= 1 && code <= 3) icon = isDay ? '⛅' : '☁️';
            if (code >= 45 && code <= 67) icon = '🌧️';
            if (code >= 71 && code <= 82) icon = '❄️';
            if (code >= 95) icon = '⛈️';
            document.getElementById('live-weather').textContent = `${icon} ${temp}°C`;
        } catch (error) {
            document.getElementById('live-weather').textContent = '☁️ --°C';
        }
    }
    fetchWeather();
    setInterval(fetchWeather, 1800000);
});
</script>