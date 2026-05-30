<?php
// File: language.php
// Funcții pentru gestionarea limbii

require_once 'translations.php';

/**
 * Obține limba curentă din sesiune sau bază de date
 * @return string 'ro' pentru română, 'en' pentru engleză
 */
function getCurrentLanguage() {
    // Dacă limba este deja în sesiune, o returnez
    if (isset($_SESSION['language'])) {
        return $_SESSION['language'];
    }
    
    // Dacă utilizatorul este logat, preiau limba din baza de date
    if (isset($_SESSION['user_id'])) {
        global $conn;
        $user_id = $_SESSION['user_id'];
        $stmt = $conn->prepare("SELECT limba FROM utilizatori WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result && $row = $result->fetch_assoc()) {
            $limba = $row['limba'] ?? 'ro';
            $_SESSION['language'] = $limba;
            $stmt->close();
            return $limba;
        }
        $stmt->close();
    }
    
    // Valoarea implicită
    $_SESSION['language'] = 'ro';
    return 'ro';
}

/**
 * Obține o traducere pentru o cheie
 * @param string $key - cheia de traducere
 * @param string $language - (optional) limba preferată
 * @return string - textul tradus
 */
function t($key, $language = null) {
    global $translations;
    
    if ($language === null) {
        $language = getCurrentLanguage();
    }
    
    // Verifică dacă cheia există în limba selectată
    if (isset($translations[$language][$key])) {
        return $translations[$language][$key];
    }
    
    // Dacă nu găsește, încearcă în limba română (fallback)
    if (isset($translations['ro'][$key])) {
        return $translations['ro'][$key];
    }
    
    // Dacă nici acolo nu găsește, returnează cheia
    return $key;
}

/**
 * Setează limba pentru utilizatorul curent
 * @param string $language - 'ro' sau 'en'
 * @return bool
 */
function setUserLanguage($language) {
    global $conn;
    
    // Validare
    if (!in_array($language, ['ro', 'en'])) {
        return false;
    }
    
    $_SESSION['language'] = $language;
    
    // Dacă utilizatorul este logat, salvez în baza de date
    if (isset($_SESSION['user_id'])) {
        $user_id = $_SESSION['user_id'];
        $stmt = $conn->prepare("UPDATE utilizatori SET limba = ? WHERE id = ?");
        $stmt->bind_param("si", $language, $user_id);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }
    
    return true;
}

/**
 * Schimbă limba și redirecționează la pagina curentă
 * Folosit în formulare
 */
function handleLanguageChange() {
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['change_language'])) {
        $language = $_POST['language'] ?? 'ro';
        
        // Validare CSRF
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            return ['success' => false, 'message' => 'CSRF token invalid'];
        }
        
        if (setUserLanguage($language)) {
            return ['success' => true, 'message' => t('msg_language_updated')];
        } else {
            return ['success' => false, 'message' => 'Eroare la schimbarea limbii'];
        }
    }
    return null;
}

?>
