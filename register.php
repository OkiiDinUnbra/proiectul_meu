<?php
session_start();
require_once 'db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nume = trim($_POST['nume']);
    $email = trim($_POST['email']);
    $parola = $_POST['parola'];
    $confirmare = $_POST['confirmare'];
    $telefon = trim($_POST['telefon']);
    $newsletter = isset($_POST['newsletter']) ? 1 : 0; // Preia valoarea checkbox-ului

    // Verificăm dacă parolele coincid
    if ($parola !== $confirmare) {
        header("Location: index.php?register=eroare_parole");
        exit;
    }

    // Verificăm lungimea parolei
    if (strlen($parola) < 8) {
        header("Location: index.php?register=eroare_parola_scurta");
        exit;
    }

    // Verificăm să nu existe deja un cont cu acest email
    $stmt = $conn->prepare("SELECT id FROM utilizatori WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        header("Location: index.php?register=eroare_duplicat");
        exit;
    }
    $stmt->close();

    // Inserăm noul utilizator direct în baza de date cu toate datele cerute de SQL-ul tău
    $parola_hash = password_hash($parola, PASSWORD_DEFAULT); // Securizăm parola
    
    // Asigură-te că aceste coloane corespund tabelului tău (nume, email, parola, telefon, doreste_newsletter)
    $stmt = $conn->prepare("INSERT INTO utilizatori (nume, email, parola, telefon, doreste_newsletter, rol, limba) VALUES (?, ?, ?, ?, ?, 'user', 'ro')");
    $stmt->bind_param("ssssi", $nume, $email, $parola_hash, $telefon, $newsletter);

    if ($stmt->execute()) {
        header("Location: index.php?register=succes");
    } else {
        header("Location: index.php?register=eroare");
    }
    $stmt->close();
} else {
    header("Location: index.php");
}
?>