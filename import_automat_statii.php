<?php
require_once 'db_connect.php';
set_time_limit(0); // Scriptul poate dura câteva secunde, nu vrem să se întrerupă

echo "<h1 style='font-family: sans-serif; color: #0A192F;'>🤖 Robot Import Stații (OpenStreetMap)</h1>";
echo "<p style='font-family: sans-serif;'>Se descarcă stațiile din Brăila de pe serverul global...</p>";
echo "<hr>";

// 1. Scriem interogarea (Query-ul) pentru API-ul Overpass
// Căutăm în zona "Brăila" orice punct (node) care este "bus_stop" sau "platform" (stație de tramvai/autobuz)
$query = '
[out:json][timeout:25];
area["name"="Brăila"]->.searchArea;
(
  node["highway"="bus_stop"](area.searchArea);
  node["public_transport"="platform"](area.searchArea);
);
out body;
';

// 2. Setăm header-ul și facem cererea către serverul Overpass
$context = stream_context_create(['http' => ['header' => "User-Agent: BrailaTransportLicenta/1.0\r\n"]]);
$url = "https://overpass-api.de/api/interpreter?data=" . urlencode($query);

$raspuns = @file_get_contents($url, false, $context);

if ($raspuns === false) {
    die("<h3 style='color: red;'>Eroare de conectare la API-ul OSM. Încearcă din nou peste câteva minute.</h3>");
}

$date = json_decode($raspuns, true);

if (!isset($date['elements']) || empty($date['elements'])) {
    die("<h3 style='color: orange;'>Nu am găsit elemente. Probabil API-ul e suprasolicitat.</h3>");
}

$statii_gasite = $date['elements'];
$adaugate = 0;
$sarite = 0;

echo "<h3>Am găsit " . count($statii_gasite) . " stații pe hartă. Încep importul în baza de date...</h3>";

// 3. Parcurgem fiecare stație găsită și o băgăm în baza de date
foreach ($statii_gasite as $statie) {
    $lat = $statie['lat'];
    $lng = $statie['lon'];
    
    // Extragem numele (dacă cineva l-a completat pe harta globală, altfel punem un nume generic)
    $nume = isset($statie['tags']['name']) ? $statie['tags']['name'] : "Stație Necunoscută OSM_" . rand(1000, 9999);
    
    // Curățăm numele pentru a nu avea probleme în SQL
    $nume = $conn->real_escape_string($nume);

    // Verificăm dacă nu cumva există deja o stație fix cu acest nume
    $check = $conn->query("SELECT id FROM transport_statii WHERE nume_statie = '$nume'");
    
    if ($check->num_rows == 0) {
        // O inserăm direct cu tot cu coordonate!
        $sql = "INSERT INTO transport_statii (nume_statie, lat, lng) VALUES ('$nume', $lat, $lng)";
        if ($conn->query($sql)) {
            echo "<div style='color: green; font-family: monospace;'>✅ ADĂUGATĂ: $nume (Lat: $lat, Lng: $lng)</div>";
            $adaugate++;
        }
    } else {
        echo "<div style='color: gray; font-family: monospace;'>⏭️ SĂRITĂ (Există deja): $nume</div>";
        $sarite++;
    }
}

echo "<hr>";
echo "<h2 style='color: #007bff; font-family: sans-serif;'>Import Finalizat! 🏁</h2>";
echo "<p><b>$adaugate</b> stații noi au fost adăugate pe hartă și în baza de date.</p>";
echo "<a href='mapare_statii.php' style='display:inline-block; padding:10px 20px; background:#10b981; color:white; text-decoration:none; border-radius:8px; font-weight:bold;'>🗺️ Mergi la Map Editor pentru a le redenumi</a>";
?>