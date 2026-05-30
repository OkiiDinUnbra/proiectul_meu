<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
include 'db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {

if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        header("Location: index.php");
        exit;
    }
    
    $email = trim($_POST['email']);
    $parola = $_POST['parola'];

    $stmt = $conn->prepare("SELECT id, nume, parola, rol FROM utilizatori WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();
        if (password_verify($parola, $user['parola'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['nume'] = $user['nume'];
            $_SESSION['rol'] = $user['rol'];

            header("Location: index.php?login=succes");
            exit;
        } else {
            header("Location: index.php?login=eroare_parola");
            exit;
        }
    } else {
        header("Location: index.php?login=eroare_email");
        exit;
    }
    $stmt->close();
}
?>