<?php
// 1. Sesiunea trebuie pornită prima
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// 2. INCLUDEM LIMBA IMEDIAT! Astfel, funcția t() este recunoscută pe tot site-ul
require_once __DIR__ . '/language.php';

// 3. Generare token CSRF dacă nu există
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

date_default_timezone_set('Europe/Bucharest');

// 4. Conexiunea la baza de date
$env = parse_ini_file(__DIR__ . '/.env');
$conn = new mysqli($env['DB_HOST'], $env['DB_USER'], $env['DB_PASS'], $env['DB_NAME']);

if ($conn->connect_error) {
    error_log("DB connection failed: " . $conn->connect_error);
    die("A apărut o eroare internă. Încearcă mai târziu.");
}

// 5. Procesează schimbarea limbii din form
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['change_language'])) {
    if (isset($_POST['csrf_token']) && $_POST['csrf_token'] === $_SESSION['csrf_token']) {
        $language = $_POST['language'] ?? 'ro';
        if (in_array($language, ['ro', 'en'])) {
            $_SESSION['language'] = $language;
            
            // Dacă utilizatorul e logat, salvează preferința în BD
            if (isset($_SESSION['user_id'])) {
                $user_id = $_SESSION['user_id'];
                $stmt = $conn->prepare("UPDATE utilizatori SET limba = ? WHERE id = ?");
                $stmt->bind_param("si", $language, $user_id);
                $stmt->execute();
                $stmt->close();
            }
            
            // Redirecționează la pagina curentă pentru a reîncărca cu noua limbă
            $redirect = !empty($_POST['redirect']) ? $_POST['redirect'] : (isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'acasa.php');
            header("Location: " . $redirect);
            exit();
        }
    }
}
?>