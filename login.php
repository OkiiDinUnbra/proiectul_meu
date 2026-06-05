<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
include 'db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // 1. PROTECȚIE CSRF (Cross-Site Request Forgery)
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        // Dacă token-ul nu corespunde, e clar un atacator. Îl oprim aici.
        die("Eroare de securitate: Cerere neautorizată (CSRF Token invalid)!");
    }
    
    // 2. PROTECȚIE BRUTE-FORCE (Rate Limiting)
    $max_incercari = 5;
    $timp_blocare = 15 * 60; // 15 minute în secunde

    // Verificăm dacă utilizatorul este deja blocat
    if (isset($_SESSION['lockout_until'])) {
        if (time() < $_SESSION['lockout_until']) {
            // Dacă nu a trecut timpul, îl redirecționăm și îi spunem câte minute mai are de așteptat
            $minute_ramase = ceil(($_SESSION['lockout_until'] - time()) / 60);
            header("Location: index.php?login=blocat&minute=" . $minute_ramase);
            exit;
        } else {
            // Dacă au trecut cele 15 minute, expiră penalizarea și resetăm contoarele
            unset($_SESSION['login_attempts']);
            unset($_SESSION['lockout_until']);
        }
    }

    // Inițializăm contorul de încercări dacă nu există
    if (!isset($_SESSION['login_attempts'])) {
        $_SESSION['login_attempts'] = 0;
    }

    $email = trim($_POST['email']);
    $parola = $_POST['parola'];

    // 3. PROTECȚIE SQL INJECTION (Prepared Statements)
    $stmt = $conn->prepare("SELECT id, nume, parola, rol FROM utilizatori WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();
        
        if (password_verify($parola, $user['parola'])) {
            // SUCCES: Parola e corectă! Resetăm contorul de greșeli.
            unset($_SESSION['login_attempts']);
            unset($_SESSION['lockout_until']);

            // Setăm sesiunea
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['nume'] = $user['nume'];
            $_SESSION['rol'] = $user['rol'];

            header("Location: index.php?login=succes");
            exit;
        } else {
            // EROARE: Parolă greșită
            $_SESSION['login_attempts']++;
            if ($_SESSION['login_attempts'] >= $max_incercari) {
                $_SESSION['lockout_until'] = time() + $timp_blocare; // Setăm blocajul
            }
            header("Location: index.php?login=eroare_parola");
            exit;
        }
    } else {
        // EROARE: Email inexistent
        // NOTĂ DE SECURITATE: Contorizăm și emailul greșit pentru a nu permite 
        // hackerilor să verifice prin "ghicire" ce adrese de mail sunt înregistrate pe site.
        $_SESSION['login_attempts']++;
        if ($_SESSION['login_attempts'] >= $max_incercari) {
            $_SESSION['lockout_until'] = time() + $timp_blocare; // Setăm blocajul
        }
        header("Location: index.php?login=eroare_email");
        exit;
    }
    
    $stmt->close();
}
?>