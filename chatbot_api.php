<?php
require_once 'db_connect.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$action = $data['action'] ?? 'start';

$response = [
    'message' => '',
    'options' => []
];

// ==========================================
// 1. MENIUL PRINCIPAL (RĂDĂCINA)
// ==========================================
if ($action === 'start') {
    $response['message'] = 'Salut! 👋 Sunt asistentul tău digital pentru Brăila. Cu ce te pot ajuta astăzi? Alege o categorie de mai jos:';
    $response['options'] = [
        ['text' => '🎫 Ce evenimente sunt în perioada următoare?', 'action' => 'meniu_evenimente'],
        ['text' => '🍔 Unde pot mânca ceva bun?', 'action' => 'meniu_mancare'],
        ['text' => '🏛️ Ce pot vizita prin oraș?', 'action' => 'meniu_turism'],
        ['text' => '🚗 Cum e traficul / Transportul?', 'action' => 'meniu_trafic']
    ];
} 

// ==========================================
// 2. RAMURA: EVENIMENTE
// ==========================================
elseif ($action === 'meniu_evenimente') {
    $response['message'] = "Super! Brăila este un oraș plin de viață. Ce fel de evenimente te atrag?";
    $response['options'] = [
        ['text' => '🎭 Cultură (Teatru, Muzee, Concerte)', 'action' => 'evenimente_culturale'],
        ['text' => '⚽ Sport și Competiții', 'action' => 'evenimente_sportive'],
        ['text' => '🆓 Vreau să văd evenimentele gratuite', 'action' => 'evenimente_gratuite'],
        ['text' => '🔙 Înapoi la meniul principal', 'action' => 'start']
    ];
}
elseif ($action === 'evenimente_culturale') {
    $stmt = $conn->query("SELECT id, titlu, data_eveniment FROM evenimente WHERE categorie = 'cultural' AND data_eveniment >= NOW() ORDER BY data_eveniment ASC LIMIT 1");
    if ($stmt->num_rows > 0) {
        $ev = $stmt->fetch_assoc();
        $data_fmt = date('d/m/Y H:i', strtotime($ev['data_eveniment']));
        $response['message'] = "Următorul eveniment cultural este **{$ev['titlu']}** (pe {$data_fmt}). Vrei să mergi la el?";
        $response['options'] = [
            ['text' => '🎫 Da, vreau să cumpăr bilet / detalii', 'link' => 'evenimentextins.php?id=' . $ev['id']],
            ['text' => '🚗 Cum e traficul până acolo?', 'action' => 'verifica_trafic'],
            ['text' => '🔙 Înapoi', 'action' => 'start']
        ];
    } else {
        $response['message'] = "Momentan nu avem evenimente culturale listate. 😔";
        $response['options'] = [['text' => 'Alege altceva', 'action' => 'start']];
    }
}
// (Aici se pot adăuga similar ramurile pentru 'evenimente_sportive' și 'evenimente_gratuite')

// ==========================================
// 3. RAMURA: MÂNCARE
// ==========================================
elseif ($action === 'meniu_mancare') {
    $response['message'] = "Mi-ai făcut poftă! 🤤 Ce fel de mâncare cauți?";
    $response['options'] = [
        ['text' => '🍕 Ceva rapid (Fast-Food / Pizza)', 'action' => 'mancare_rapida'],
        ['text' => '🍽️ Un restaurant elegant / Tradițional', 'action' => 'mancare_restaurant'],
        ['text' => '☕ O cafenea pentru relaxare', 'action' => 'mancare_cafenea'],
        ['text' => '🔙 Înapoi la început', 'action' => 'start']
    ];
}
elseif ($action === 'mancare_rapida') {
    $response['message'] = "Pentru ceva rapid, îți recomand cu căldură **Domino's Pizza** (în Barieră) sau un kebab bun la **KY'S Kebab**. Ambele sunt pe harta noastră turistică!";
    $response['options'] = [
        ['text' => '🗺️ Arată-mi restaurantele pe hartă', 'link' => 'ghid.php'],
        ['text' => '🔙 M-am răzgândit, înapoi la meniu', 'action' => 'start']
    ];
}
elseif ($action === 'mancare_restaurant') {
    $response['message'] = "Dacă vrei o experiență culinară deosebită, poți încerca **Cherhanaua** pentru pește proaspăt pe malul Dunării sau **Bella Italia** în Centrul Vechi.";
    $response['options'] = [
        ['text' => '🎭 După masă aș vrea să văd un eveniment', 'action' => 'meniu_evenimente'],
        ['text' => '🔙 Înapoi', 'action' => 'start']
    ];
}

// ==========================================
// 4. RAMURA: TURISM
// ==========================================
elseif ($action === 'meniu_turism') {
    $response['message'] = "Brăila are o istorie fascinantă. Cât timp ai la dispoziție pentru explorat?";
    $response['options'] = [
        ['text' => '⏱️ Doar vreo 30 de minute (Sunt pe fugă)', 'action' => 'turism_rapid'],
        ['text' => '🚶 Câteva ore (Vreau o plimbare serioasă)', 'action' => 'turism_lung'],
        ['text' => '🔙 Înapoi', 'action' => 'start']
    ];
}
elseif ($action === 'turism_rapid') {
    $response['message'] = "Dacă ai puțin timp, cel mai bine este să mergi direct în **Piața Traian** să vezi Ceasul Istoric și clădirile din jur, sau să faci o plimbare scurtă pe **Faleza Dunării**.<br><br>Apropo, ești cu mașina? Vrei să știi dacă e aglomerat în zonă?";
    $response['options'] = [
        ['text' => '🚗 Da, verifică traficul', 'action' => 'verifica_trafic'],
        ['text' => '🔙 Nu, revino la meniu', 'action' => 'start']
    ];
}
elseif ($action === 'turism_lung') {
    $response['message'] = "Excelent! Îți recomand o plimbare prin **Parcul Monument**, urmată de o vizită la **Grădina Zoologică**, iar spre seară o croazieră pe Dunăre! Găsești locațiile exacte pe harta noastră interactivă.";
    $response['options'] = [
        ['text' => '🗺️ Deschide Harta Turistică', 'link' => 'ghid.php'],
        ['text' => '🍔 Mi s-a făcut foame. Unde mănânc?', 'action' => 'meniu_mancare']
    ];
}

// ==========================================
// 5. RAMURA: TRAFIC & TRANSPORT
// ==========================================
elseif ($action === 'meniu_trafic') {
    $response['message'] = "Informațiile despre mobilitate sunt vitale. Ce te interesează exact?";
    $response['options'] = [
        ['text' => '🚧 Verifică raportările de trafic', 'action' => 'verifica_trafic'],
        ['text' => '🚌 Vreau să cumpăr bilet de autobuz', 'link' => 'genereaza_bilet.php?bus=1'],
        ['text' => '🔙 Înapoi la meniul principal', 'action' => 'start']
    ];
}
elseif ($action === 'verifica_trafic') {
    $stmt = $conn->query("SELECT locatie, tip_problema FROM rapoarte_trafic WHERE status = 'activ' ORDER BY data_raport DESC LIMIT 2");
    
    if ($stmt->num_rows > 0) {
        $response['message'] = "⚠️ Atenție! Au fost raportate următoarele probleme în oraș:<br>";
        while ($row = $stmt->fetch_assoc()) {
            $response['message'] .= "• <strong>{$row['tip_problema']}</strong> în zona {$row['locatie']}<br>";
        }
        $response['message'] .= "<br>Vrei să intri pe panoul complet de trafic pentru a găsi rute alternative?";
        $response['options'] = [
            ['text' => '🚦 Mergi la Info Trafic', 'link' => 'trafic.php'],
            ['text' => '🔙 Înapoi la meniu', 'action' => 'start']
        ];
    } else {
        $response['message'] = "🟢 Drum întins! Nu avem nicio problemă majoră de trafic raportată momentan.";
        $response['options'] = [
            ['text' => '🏛️ Ok, atunci ce pot vizita azi?', 'action' => 'meniu_turism'],
            ['text' => '🔙 Înapoi la meniu', 'action' => 'start']
        ];
    }
}

echo json_encode($response);
?>