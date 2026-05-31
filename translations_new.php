<?php
// File: translations.php
// Sistem centralizat de traduceri pentru limba română și engleză

$translations = [
    'ro' => [
        // Header Navigation
        'nav_home' => 'Acasă',
        'nav_events' => 'evenimente',
        'nav_guide' => 'Ghid Turistic',
        'nav_transport' => 'Transport',
        'nav_blog' => 'Blog',
        'nav_statistics' => 'Statistici',
        'nav_contact' => 'Contact',
        'nav_tickets' => '🎫 Biletele mele',
        'nav_settings' => '⚙️ Setări cont',
        'nav_logout' => '🚪 Delogare',
        'nav_login' => 'Login',
        'nav_register' => 'Înregistrare',

        // Home Page
        'home_welcome' => 'Bine ai venit în Brăila',
        'home_subtitle' => 'Explorează cultura, tradiția și frumusețea orașului de la Dunăre',
        'home_events_btn' => 'Vezi Evenimente',
        'home_transport_btn' => '🚍 Smart Transit',
        'home_brand' => '@descoperaBraila',

        // Events Page
        'events_title' => 'Evenimente',
        'events_choose' => 'Alege categoria de evenimente',
        'events_cultural' => 'Evenimente Culturale',
        'events_sports' => 'Evenimente Sportive',
        'events_add_new' => '⚙️ Adaugă Eveniment Nou',
        'events_no_found' => 'Nu au fost găsite evenimente pentru această categorie.',
        'events_date' => 'Data',
        'events_location' => 'Locație',
        'events_description' => 'Descriere',
        'events_category' => 'Categorie',
        'events_price' => 'Preț',
        'events_buy_ticket' => 'Cumpără Bilet',
        'events_edit' => 'Editează',
        'events_delete' => 'Șterge',

        // Travel Guide Page
        'guide_title' => 'Ghid Turistic',
        'guide_map_title' => '📍 Harta Obiectivelor din Brăila',
        'guide_map_desc' => 'Apasă pe oricare dintre punctele de interes de pe harta de mai jos pentru a afla ce reprezintă și pentru a citi mai multe detalii despre istoria și importanța sa!',
        'guide_learn_more' => '📖 Află mai multe',

        // Transport Page
        'transport_title' => 'Smart Transit Brăila',
        'transport_subtitle' => 'Găsește traseul optim și achiziționează bilete digitale instant.',
        'transport_plan' => '📍 Planifică-ți Călătoria',
        'transport_from' => 'Punct de plecare:',
        'transport_to' => 'Destinație:',
        'transport_from_placeholder' => '-- Alege stația de plecare --',
        'transport_to_placeholder' => '-- Alege destinația --',
        'transport_search' => '🔍 Caută Traseul',
        'transport_digital_ticket' => '🎫 Bilet Digital (60 min)',
        'transport_price' => 'Preț: 2 lei',
        'transport_login_required' => 'Trebuie să te loghezi pentru a cumpăra bilete.',
        'transport_buy_ticket' => 'Cumpără Bilet Digital',
        'transport_daily_pass' => '📅 Abonament Zilei',
        'transport_weekly_pass' => '📅 Abonament Săptămânii',
        'transport_monthly_pass' => '📅 Abonament Lunar',

        // Blog Page
        'blog_title' => 'Articole & Știri Locale',
        'blog_article1_title' => '5 lucruri pe care nu le știai despre Brăila',
        'blog_article1_desc' => 'Istorie, gastronomie, tradiții – lucruri care te vor surprinde.',
        'blog_article2_title' => 'Trasee de o zi prin județ',
        'blog_article2_desc' => 'Recomandări de excursii și locuri de descoperit în natură.',
        'blog_read_more' => 'Citește mai mult',
        'blog_coming_soon' => 'Articolul va fi disponibil în curând!',

        // Profile/Tickets Page
        'profile_title' => 'Profilul Meu',
        'profile_my_tickets' => 'Biletele Mele',
        'profile_no_tickets' => 'Nu ai niciun bilet încă.',
        'profile_ticket_type' => 'Tip Bilet',
        'profile_ticket_date' => 'Data Achiziției',
        'profile_ticket_expires' => 'Expiră la',
        'profile_ticket_qr' => 'Cod QR',

        // Settings Page
        'settings_title' => 'Setări Cont',
        'settings_profile' => 'Profil',
        'settings_password' => 'Parolă',
        'settings_language' => 'Limbă',
        'settings_name' => 'Nume',
        'settings_email' => 'Email',
        'settings_phone' => 'Telefon',
        'settings_update' => 'Actualizare Profil',
        'settings_old_password' => 'Parola veche',
        'settings_new_password' => 'Parola nouă',
        'settings_confirm_password' => 'Confirmare parolă',
        'settings_change_password' => 'Schimbă Parolă',
        'settings_select_language' => 'Selectează limba',
        'settings_romanian' => 'Română',
        'settings_english' => 'Engleză',
        'settings_save_language' => 'Salvează Limba',

        // Statistics Page
        'stats_title' => 'Statistici',
        'stats_total_users' => 'Utilizatori Totali',
        'stats_total_events' => 'Evenimente Totale',
        'stats_total_tickets_sold' => 'Bilete Vândute',
        'stats_revenue' => 'Venituri Totale',

        // Messages
        'msg_success' => 'Success!',
        'msg_error' => 'Eroare!',
        'msg_profile_updated' => 'Datele personale au fost actualizate cu succes!',
        'msg_email_taken' => 'Acest email este deja asociat altui cont!',
        'msg_update_error' => 'Eroare la actualizarea datelor.',
        'msg_password_mismatch' => 'Parolele noi nu coincid!',
        'msg_password_short' => 'Parola nouă trebuie să aibă minimum 8 caractere!',
        'msg_password_incorrect' => 'Parola veche este incorectă!',
        'msg_password_changed' => 'Parola a fost schimbată cu succes!',
        'msg_language_updated' => 'Limba a fost schimbată cu succes!',
        'msg_complete_fields' => 'Te rugăm să completezi toate câmpurile!',

        // Footer
        'footer_about' => 'Despre',
        'footer_contact' => 'Contact',
        'footer_terms' => 'Termeni și Condiții',
        'footer_privacy' => 'Politica de Confidențialitate',
        'footer_rights' => '© 2024 Descoperă Brăila. Toate drepturile rezervate.',

        // Contact Form
        'contact_title' => 'Contactează-ne',
        'contact_name' => 'Nume',
        'contact_email' => 'Email',
        'contact_message' => 'Mesaj',
        'contact_send' => 'Trimite Mesaj',

        // Common
        'page_title' => 'Descoperă Brăila',
        'btn_save' => 'Salvează',
        'btn_cancel' => 'Anulează',
        'btn_edit' => 'Editează',
        'btn_delete' => 'Șterge',
        'btn_back' => 'Înapoi',
        'btn_submit' => 'Trimite',
    ],
    'en' => [
        // Header Navigation
        'nav_home' => 'Home',
        'nav_events' => 'Events',
        'nav_guide' => 'Travel Guide',
        'nav_transport' => 'Transport',
        'nav_blog' => 'Blog',
        'nav_statistics' => 'Statistics',
        'nav_contact' => 'Contact',
        'nav_tickets' => '🎫 My Tickets',
        'nav_settings' => '⚙️ Account Settings',
        'nav_logout' => '🚪 Logout',
        'nav_login' => 'Login',
        'nav_register' => 'Register',

        // Home Page
        'home_welcome' => 'Welcome to Brăila',
        'home_subtitle' => 'Explore the culture, tradition and beauty of the Danube city',
        'home_events_btn' => 'View Events',
        'home_transport_btn' => '🚍 Smart Transit',
        'home_brand' => '@discoverBraila',

        // Events Page
        'events_title' => 'Events',
        'events_choose' => 'Choose an event category',
        'events_cultural' => 'Cultural Events',
        'events_sports' => 'Sports Events',
        'events_add_new' => '⚙️ Add New Event',
        'events_no_found' => 'No events found for this category.',
        'events_date' => 'Date',
        'events_location' => 'Location',
        'events_description' => 'Description',
        'events_category' => 'Category',
        'events_price' => 'Price',
        'events_buy_ticket' => 'Buy Ticket',
        'events_edit' => 'Edit',
        'events_delete' => 'Delete',

        // Travel Guide Page
        'guide_title' => 'Travel Guide',
        'guide_map_title' => '📍 Map of Attractions in Brăila',
        'guide_map_desc' => 'Click on any of the points of interest on the map below to find out what they represent and read more details about their history and importance!',
        'guide_learn_more' => '📖 Learn More',

        // Transport Page
        'transport_title' => 'Smart Transit Brăila',
        'transport_subtitle' => 'Find the optimal route and purchase digital tickets instantly.',
        'transport_plan' => '📍 Plan Your Journey',
        'transport_from' => 'Departure point:',
        'transport_to' => 'Destination:',
        'transport_from_placeholder' => '-- Choose departure station --',
        'transport_to_placeholder' => '-- Choose destination --',
        'transport_search' => '🔍 Search Route',
        'transport_digital_ticket' => '🎫 Digital Ticket (60 min)',
        'transport_price' => 'Price: 2 RON',
        'transport_login_required' => 'You need to log in to purchase tickets.',
        'transport_buy_ticket' => 'Buy Digital Ticket',
        'transport_daily_pass' => '📅 Daily Pass',
        'transport_weekly_pass' => '📅 Weekly Pass',
        'transport_monthly_pass' => '📅 Monthly Pass',

        // Blog Page
        'blog_title' => 'Articles & Local News',
        'blog_article1_title' => '5 Things You Didn\'t Know About Brăila',
        'blog_article1_desc' => 'History, gastronomy, traditions – things that will surprise you.',
        'blog_article2_title' => 'Day Trips Around the County',
        'blog_article2_desc' => 'Recommendations for excursions and places to discover in nature.',
        'blog_read_more' => 'Read More',
        'blog_coming_soon' => 'Article will be available soon!',

        // Profile/Tickets Page
        'profile_title' => 'My Profile',
        'profile_my_tickets' => 'My Tickets',
        'profile_no_tickets' => 'You have no tickets yet.',
        'profile_ticket_type' => 'Ticket Type',
        'profile_ticket_date' => 'Purchase Date',
        'profile_ticket_expires' => 'Expires at',
        'profile_ticket_qr' => 'QR Code',

        // Settings Page
        'settings_title' => 'Account Settings',
        'settings_profile' => 'Profile',
        'settings_password' => 'Password',
        'settings_language' => 'Language',
        'settings_name' => 'Name',
        'settings_email' => 'Email',
        'settings_phone' => 'Phone',
        'settings_update' => 'Update Profile',
        'settings_old_password' => 'Old password',
        'settings_new_password' => 'New password',
        'settings_confirm_password' => 'Confirm password',
        'settings_change_password' => 'Change Password',
        'settings_select_language' => 'Select language',
        'settings_romanian' => 'Romanian',
        'settings_english' => 'English',
        'settings_save_language' => 'Save Language',

        // Statistics Page
        'stats_title' => 'Statistics',
        'stats_total_users' => 'Total Users',
        'stats_total_events' => 'Total Events',
        'stats_total_tickets_sold' => 'Tickets Sold',
        'stats_revenue' => 'Total Revenue',

        // Messages
        'msg_success' => 'Success!',
        'msg_error' => 'Error!',
        'msg_profile_updated' => 'Personal data has been updated successfully!',
        'msg_email_taken' => 'This email is already associated with another account!',
        'msg_update_error' => 'Error updating data.',
        'msg_password_mismatch' => 'New passwords do not match!',
        'msg_password_short' => 'New password must be at least 8 characters!',
        'msg_password_incorrect' => 'Old password is incorrect!',
        'msg_password_changed' => 'Password has been changed successfully!',
        'msg_language_updated' => 'Language has been changed successfully!',
        'msg_complete_fields' => 'Please complete all fields!',

        // Footer
        'footer_about' => 'About',
        'footer_contact' => 'Contact',
        'footer_terms' => 'Terms and Conditions',
        'footer_privacy' => 'Privacy Policy',
        'footer_rights' => '© 2024 Discover Brăila. All rights reserved.',

        // Contact Form
        'contact_title' => 'Contact Us',
        'contact_name' => 'Name',
        'contact_email' => 'Email',
        'contact_message' => 'Message',
        'contact_send' => 'Send Message',

        // Common
        'page_title' => 'Discover Brăila',
        'btn_save' => 'Save',
        'btn_cancel' => 'Cancel',
        'btn_edit' => 'Edit',
        'btn_delete' => 'Delete',
        'btn_back' => 'Back',
        'btn_submit' => 'Submit',
    ]
];

?>
