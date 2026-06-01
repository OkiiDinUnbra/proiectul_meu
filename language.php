<?php
// Pornim sesiunea dacă nu este deja pornită pentru a putea salva limba
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Funcție pentru a schimba și a prelua limba curentă
function getCurrentLanguage() {
    if (isset($_POST['change_language']) && isset($_POST['language'])) {
        $_SESSION['language'] = $_POST['language'];
    }
    
    if (isset($_SESSION['language'])) {
        return $_SESSION['language'];
    }
    
    return 'ro';
}

// Funcția principală de traducere
function t($key) {
    $lang = getCurrentLanguage();
    
    // Dicționarul complet al site-ului tău
    $translations = [
        'ro' => [
            // Meniu
            'page_title' => 'Descoperă Brăila',
            'nav_home' => 'Acasă',
            'nav_events' => 'Evenimente',
            'nav_guide' => 'Ghid Turistic',
            'nav_transport' => 'Transport',
            'nav_blog' => 'Blog',
            'nav_statistics' => 'Statistici',
            'nav_contact' => 'Contact',
            'nav_login' => 'Login',
            'nav_register' => 'Înregistrare',
            'nav_tickets' => 'Biletele mele',
            'nav_settings' => 'Setări',
            'nav_logout' => 'Deconectare',
            
            // Pagina Principală
            'home_welcome' => 'Descoperă Brăila',
            'home_subtitle' => 'Explorează istoria, cultura și evenimentele orașului de la Dunăre.',
            'home_events_btn' => '📅 Vezi Evenimentele',
            'home_transport_btn' => '🚍 Transport Live',
            'home_brand' => '@descoperaBraila',
            
            // Evenimente (NOU)
            'events_choose' => 'Alege categoria de evenimente',
            'events_add_new' => 'Adaugă eveniment nou',
            'events_cultural' => 'Culturale (Teatru, Concerte)',
            'events_sports' => 'Sportive (Meciuri, Competiții)',
            
            // Ghid Turistic / Harta (NOU)
            'guide_map_title' => 'Harta Turistică Interactivă',
            'guide_map_desc' => 'Descoperă atracțiile principale și cele mai bune restaurante din oraș.',
            
            // Transport
            'transport_title' => 'Smart Transit Brăila',
            'transport_subtitle' => 'Găsește traseul optim și achiziționează bilete digitale instant.',
            'transport_plan' => 'Planifică-ți Călătoria',
            'transport_from' => 'Punct de plecare:',
            'transport_from_placeholder' => '-- Alege stația de plecare --',
            'transport_to' => 'Destinație:',
            'transport_to_placeholder' => '-- Alege destinația --',
            'transport_search' => 'Caută Traseul',
            'transport_digital_ticket' => 'Bilet Digital (60 min)',
            'transport_ticket_info' => 'Biletul tău va fi valabil 60 de minute pe orice linie Braicar din momentul achiziției. Plata se face securizat.',
            'transport_price' => 'Preț: 2 lei',
            'transport_buy_ticket' => 'Cumpără Bilet Digital',
            'transport_login_required' => 'Trebuie să fii autentificat pentru a cumpăra bilete.',
            
            // Login / Setări generale
            'settings_title' => 'Autentificare',
            'settings_email' => 'Email',
            'settings_name' => 'Nume complet',
            'settings_password' => 'Parolă',
            'settings_confirm_password' => 'Confirmă Parola',
            'settings_phone' => 'Telefon'
        ],
        'en' => [
            // Meniu
            'page_title' => 'Discover Brăila',
            'nav_home' => 'Home',
            'nav_events' => 'Events',
            'nav_guide' => 'Tourist Guide',
            'nav_transport' => 'Transport',
            'nav_blog' => 'Blog',
            'nav_statistics' => 'Statistics',
            'nav_contact' => 'Contact',
            'nav_login' => 'Login',
            'nav_register' => 'Register',
            'nav_tickets' => 'My Tickets',
            'nav_settings' => 'Settings',
            'nav_logout' => 'Logout',
            
            // Pagina Principală
            'home_welcome' => 'Welcome to Brăila',
            'home_subtitle' => 'Explore the history, culture and events of the city by the Danube.',
            'home_events_btn' => '📅 View Events',
            'home_transport_btn' => '🚍 Live Transport',
            'home_brand' => '@discoverBraila',
            
            // Evenimente (NOU)
            'events_choose' => 'Choose event category',
            'events_add_new' => 'Add new event',
            'events_cultural' => 'Cultural (Theater, Concerts)',
            'events_sports' => 'Sports (Matches, Competitions)',
            
            // Ghid Turistic / Harta (NOU)
            'guide_map_title' => 'Interactive Tourist Map',
            'guide_map_desc' => 'Discover the main attractions and best restaurants in the city.',
            
            // Transport
            'transport_title' => 'Smart Transit Brăila',
            'transport_subtitle' => 'Find the optimal route and purchase digital tickets instantly.',
            'transport_plan' => 'Plan Your Journey',
            'transport_from' => 'Departure:',
            'transport_from_placeholder' => '-- Choose departure station --',
            'transport_to' => 'Destination:',
            'transport_to_placeholder' => '-- Choose destination --',
            'transport_search' => 'Search Route',
            'transport_digital_ticket' => 'Digital Ticket (60 min)',
            'transport_ticket_info' => 'Your ticket will be valid for 60 minutes on any Braicar line from the moment of purchase. Payment is secured.',
            'transport_price' => 'Price: 2 RON',
            'transport_buy_ticket' => 'Buy Digital Ticket',
            'transport_login_required' => 'You must be logged in to purchase tickets.',
            
            // Login / Setări generale
            'settings_title' => 'Authentication',
            'settings_email' => 'Email',
            'settings_name' => 'Full Name',
            'settings_password' => 'Password',
            'settings_confirm_password' => 'Confirm Password',
            'settings_phone' => 'Phone'
        ]
    ];

    // Dacă există traducerea, o afișăm. Altfel, afișăm direct cheia ca să vedem ce lipsește.
    return isset($translations[$lang][$key]) ? $translations[$lang][$key] : $key;
}
?>