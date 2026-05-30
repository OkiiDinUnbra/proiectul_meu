<?php
require_once 'db_connect.php'; 

$current_lang = getCurrentLanguage();
$page_title = isset($page_title) ? $page_title : t('page_title');
?>
<!DOCTYPE html>
<html lang="<?= $current_lang ?>"">>>
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
        <h1 class="logo"><a href="acasa.php"><?= t('page_title') ?></a></h1>
        <nav>
           <ul>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <li><a href="acasa.php"><?= t('nav_home') ?></a></li>
                    <li><a href="evenimente.php"><?= t('nav_events') ?></a></li>
                    <li><a href="ghid.php"><?= t('nav_guide') ?></a></li>
                    <li><a href="transport.php" style="color: #ffd700; font-weight: bold;"><?= t('nav_transport') ?></a></li>
                    <li><a href="blog.php"><?= t('nav_blog') ?></a></li>

                    <?php if (isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin'): ?>
                        <li><a href="statistici.php" style="color: #ff4757; font-weight: bold;"><?= t('nav_statistics') ?></a></li>
                    <?php endif; ?>

                    <li><a href="#" onclick="openPopup('contactPopup')"><?= t('nav_contact') ?></a></li>

                    <!-- Language Selector -->
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
                                    <button type="submit" style="width: 100%; text-align: left; padding: 12px 16px; border: none; background: none; cursor: pointer; font-size: 14px;">🇷🇴 Română</button>
                                </form>
                            <?php endif; ?>
                            <?php if ($current_lang !== 'en'): ?>
                                <form method="POST" style="padding: 0;">
                                    <input type="hidden" name="change_language" value="1">
                                    <input type="hidden" name="language" value="en">
                                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                    <button type="submit" style="width: 100%; text-align: left; padding: 12px 16px; border: none; background: none; cursor: pointer; font-size: 14px;">🇬🇧 English</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </li>

                    <li class="dropdown-profil">
                        <a href="#" class="dropbtn">👤 <?= htmlspecialchars($_SESSION['nume']) ?> ▼</a>
                        <div class="dropdown-content">
                            <a href="profil.php"><?= t('nav_tickets') ?></a>
                            <a href="setari.php"><?= t('nav_settings') ?></a>
                            <a href="logout.php" style="color: #dc3545; font-weight: 600;"><?= t('nav_logout') ?></a>
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