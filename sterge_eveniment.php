<?php
session_start();
require_once 'db_connect.php';

// Verificare rol admin
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    header("Location: index.php");
    exit;
}

// Verificăm dacă am primit un ID valid
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = intval($_GET['id']);

    $stmt = $conn->prepare("DELETE FROM evenimente WHERE id = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        header("Location: evenimente.php");
        exit;
    } else {
        error_log("Eroare stergere eveniment ID $id: " . $stmt->error);
        header("Location: evenimente.php?eroare=stergere");
        exit;
    }
    $stmt->close();
} else {
    header("Location: evenimente.php");
    exit;
}
?>