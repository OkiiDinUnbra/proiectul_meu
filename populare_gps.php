<?php
require_once 'db_connect.php';
// Setăm timpul de execuție la nelimitat, deoarece API-ul ne cere să facem pauză 1 secundă între căutări
set_time_limit(0); 

echo "<h1 style='font-family: sans-serif; color: #0A192F;'>🤖 Robotul de Geocodare Brăila</h1>";
echo "<p style='font-family: sans-serif;'>Caut coordonatele pentru stațiile fără GPS...</p>";
echo "<hr>";

// Selectăm doar stațiile care nu au încă GPS-ul setat
$statii = $conn->query("SELECT id, nume_statie FROM transport_statii WHERE lat IS NULL OR lng IS NULL");

if ($statii->num_rows == 0) {
    echo "<h2 style='color: green;'>Toate stațiile au deja coordonate! Ai terminat! 🎉</h2>";
    exit;
}

// Setăm un User-Agent (OBLIGATORIU pentru Nominatim API)
$context = stream_context_create(['http' => ['header' => "User-Agent: BrailaTransportLicenta/1.0\r\n"]]);

while ($row = $statii->fetch_assoc()) {
    $id = $row['id'];
    $nume_original = $row['nume_statie'];

    // Curățăm numele pentru a ajuta motorul de căutare 
    // Ex: "YAZAKI (cf prg)" devine "YAZAKI"
    $nume_curatat = preg_replace('/\(.*?\)/', '', $nume_original);
    $nume_curatat = trim($nume_curatat);

    // Facem request-ul către OpenStreetMap (Nominatim)
    // Căutăm specific în Brăila pentru a nu ne da străzi din alte orașe
    $url = "https://nominatim.openstreetmap.org/search?format=json&city=Brăila&q=" . urlencode($nume_curatat);
    
    $raspuns = @file_get_contents($url, false, $context);
    $date = json_decode($raspuns, true);

    if (!empty($date)) {
        $lat = floatval($date[0]['lat']);
        $lng = floatval($date[0]['lon']);
        
        // Salvăm în baza de date
        $conn->query("UPDATE transport_statii SET lat = $lat, lng = $lng WHERE id = $id");
        echo "<div style='color: green; font-family: monospace; margin-bottom: 5px;'>✅ GĂSIT: <b>$nume_original</b> -> Lat: $lat, Lng: $lng</div>";
    } else {
        echo "<div style='color: red; font-family: monospace; margin-bottom: 5px;'>❌ RATAT: <b>$nume_original</b> -> (Va trebui adăugat manual din phpMyAdmin)</div>";
    }

    // Trimitem datele către ecran în timp real
    ob_flush(); 
    flush();
    
    // ⚠️ REGULA DE AUR: Nominatim API ne dă voie la maxim 1 request pe secundă.
    // Dacă scoți acest sleep(1), API-ul îți va bloca IP-ul!
    sleep(1); 
}

echo "<hr><h2 style='color: #007bff; font-family: sans-serif;'>Procesare finalizată! 🏁</h2>";
echo "<a href='transport.php'>Înapoi la transport</a>";
?>