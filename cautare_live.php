<?php
require_once 'db_connect.php';

// Setăm header-ul pentru a returna date în format JSON
header('Content-Type: application/json');

$query = isset($_GET['q']) ? trim($_GET['q']) : '';

// Dacă utilizatorul a tastat mai puțin de 2 caractere, nu căutăm nimic (pentru optimizare)
if (strlen($query) < 2) {
    echo json_encode([]);
    exit;
}

$rezultate = [];
$termen_cautare = "%" . $query . "%";

// 1. Căutăm în EVENIMENTE
$stmt_ev = $conn->prepare("SELECT id, titlu FROM evenimente WHERE titlu LIKE ? LIMIT 4");
$stmt_ev->bind_param("s", $termen_cautare);
$stmt_ev->execute();
$res_ev = $stmt_ev->get_result();

while ($row = $res_ev->fetch_assoc()) {
    $rezultate[] = [
        'titlu' => $row['titlu'],
        'tip' => '🎭 Eveniment',
        'url' => 'evenimentextins.php?id=' . $row['id']
    ];
}
$stmt_ev->close();

// 2. Căutăm în BLOG (Articole/Știri)
// Presupunem că tabela se numește `blog` (sau ajustează numele tabelei dacă diferă)
$stmt_blog = $conn->prepare("SELECT id, titlu FROM blog WHERE titlu LIKE ? LIMIT 4");
$stmt_blog->bind_param("s", $termen_cautare);
$stmt_blog->execute();
$res_blog = $stmt_blog->get_result();

while ($row = $res_blog->fetch_assoc()) {
    $rezultate[] = [
        'titlu' => $row['titlu'],
        'tip' => '📰 Articol',
        'url' => 'articol.php?id=' . $row['id']
    ];
}
$stmt_blog->close();

// Returnăm rezultatele sub formă de pachet JSON către scriptul din header
echo json_encode($rezultate);
exit;
?>