<?php
error_reporting(0);
include 'db_connect.php';

$categorie = isset($_GET['categorie']) ? $_GET['categorie'] : '';
$luna = isset($_GET['luna']) ? intval($_GET['luna']) : null;
$an = isset($_GET['an']) ? intval($_GET['an']) : null;

// Construim query-ul dinamic
$conditions = [];
$params = [];
$types = '';

if ($categorie) {
    $conditions[] = "categorie LIKE ?";
    $params[] = "%" . $categorie . "%";
    $types .= 's';
}

if ($luna && $an) {
    $conditions[] = "MONTH(data_eveniment) = ?";
    $conditions[] = "YEAR(data_eveniment) = ?";
    $params[] = $luna;
    $params[] = $an;
    $types .= 'ii';
} elseif ($an) {
    $conditions[] = "YEAR(data_eveniment) = ?";
    $params[] = $an;
    $types .= 'i';
}

$sql = "SELECT id, titlu as title, data_eveniment as start, descriere, locatie FROM evenimente";

if (!empty($conditions)) {
    $sql .= " WHERE " . implode(" AND ", $conditions);
}

$sql .= " ORDER BY data_eveniment ASC LIMIT 100";

if (!empty($params)) {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query($sql);
}

$evenimente_array = [];

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $evenimente_array[] = [
            'id' => $row['id'],
            'title' => $row['title'],
            'start' => $row['start'],
            'extendedProps' => [
                'description' => $row['descriere'],
                'location' => $row['locatie']
            ]
        ];
    }
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode($evenimente_array);
exit;
?>