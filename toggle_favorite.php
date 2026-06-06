<?php
session_start();
require_once 'db_connect.php';

// Spunem browserului că îi vom răspunde în format JSON
header('Content-Type: application/json');

// Verificăm dacă utilizatorul este logat
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'neautorizat', 'mesaj' => 'Trebuie să fii logat pentru a salva la favorite.']);
    exit;
}

// Preluăm datele trimise prin JavaScript (AJAX)
$data = json_decode(file_get_contents('php://input'), true);

if (isset($data['item_id']) && isset($data['tip_item'])) {
    $user_id = $_SESSION['user_id'];
    $item_id = intval($data['item_id']);
    $tip_item = $data['tip_item']; // Ex: 'eveniment'

    // Verificăm dacă evenimentul este deja la favorite
    $stmt = $conn->prepare("SELECT id FROM favorite WHERE user_id = ? AND tip_item = ? AND item_id = ?");
    $stmt->bind_param("isi", $user_id, $tip_item, $item_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Dacă există deja, înseamnă că utilizatorul vrea să-l ȘTEARGĂ (Toggle OFF)
        $stmt_del = $conn->prepare("DELETE FROM favorite WHERE user_id = ? AND tip_item = ? AND item_id = ?");
        $stmt_del->bind_param("isi", $user_id, $tip_item, $item_id);
        $stmt_del->execute();
        
        echo json_encode(['status' => 'sters']);
    } else {
        // Dacă nu există, înseamnă că utilizatorul vrea să-l ADAUGE (Toggle ON)
        $stmt_ins = $conn->prepare("INSERT INTO favorite (user_id, tip_item, item_id) VALUES (?, ?, ?)");
        $stmt_ins->bind_param("isi", $user_id, $tip_item, $item_id);
        $stmt_ins->execute();
        
        echo json_encode(['status' => 'adaugat']);
    }

    $stmt->close();
} else {
    echo json_encode(['status' => 'eroare', 'mesaj' => 'Date incomplete.']);
}
?>