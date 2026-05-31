<?php
// language.php

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Includem dicționarul de traduceri
require_once __DIR__ . '/translations.php';

/**
 * Funcție pentru a obține limba curentă a utilizatorului
 */
function getCurrentLanguage() {
    if (isset($_SESSION['language'])) {
        return $_SESSION['language'];
    }
    return 'ro'; // Limba implicită dacă nu a ales nimic
}

/**
 * Funcție de traducere: caută cheia în translations.php
 */
function t($key) {
    // Folosim variabila $translations din fișierul translations.php
    global $translations; 
    
    $lang = getCurrentLanguage();
    
    // Verificăm dacă există traducerea în limba curentă
    if (isset($translations[$lang][$key])) {
        return $translations[$lang][$key];
    }
    
    // Fallback la română dacă lipsește traducerea în engleză
    if (isset($translations['ro'][$key])) {
        return $translations['ro'][$key];
    }
    
    // Dacă nu există deloc cheia, o returnăm așa cum e, pentru a observa că lipsește
    return $key;
}
?>