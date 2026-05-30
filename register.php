<?php
include 'db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {


 if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        header("Location: index.php");
        exit;
    }


    $nume = trim($_POST['nume']);
    $email = trim($_POST['email']);
    $parola = $_POST['parola'];
    $confirmare = $_POST['confirmare'];
    $telefon = trim($_POST['telefon']);
    $newsletter = isset($_POST['newsletter']) ? 1 : 0;

    // NOU
if ($parola !== $confirmare) {
    header("Location: index.php?register=eroare_parole");
    exit;
}

if (strlen($parola) < 8) {
    header("Location: index.php?register=eroare_parola_scurta");
    exit;
}

if (!preg_match('/[A-Z]/', $parola) || !preg_match('/[0-9]/', $parola)) {
    header("Location: index.php?register=eroare_parola_slaba");
    exit;
}

if (!empty($telefon) && !preg_match('/^(\+4|0)[0-9]{9}$/', $telefon)) {
    header("Location: index.php?register=eroare_telefon");
    exit;
}

    // Verificare email duplicat
    $check = $conn->prepare("SELECT id FROM utilizatori WHERE email = ?");
    $check->bind_param("s", $email);
    $check->execute();
    $check->store_result();
    if ($check->num_rows > 0) {
        header("Location: index.php?register=eroare_duplicat");
        exit;
    }
    $check->close();

    // Inserare utilizator nou
    $hash = password_hash($parola, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("INSERT INTO utilizatori (nume, email, parola, telefon, doreste_newsletter) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssi", $nume, $email, $hash, $telefon, $newsletter);

    if ($stmt->execute()) {
        header("Location: index.php?register=succes");
        exit;
    } else {
        error_log("Eroare inregistrare utilizator: " . $stmt->error);
        header("Location: index.php?register=eroare_server");
        exit;
    }

    $stmt->close();
}
?>