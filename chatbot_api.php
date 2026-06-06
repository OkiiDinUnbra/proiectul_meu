<?php
session_start();
require_once 'db_connect.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$action = $data['action'] ?? 'start';
$textUser = isset($data['textUser']) ? trim(strtolower($data['textUser'])) : ''; 

$response = [
    'message' => '',
    'options' => []
];

// ========================================================
// 0. INTERCEPTARE PENTRU TICHETE DE SUPORT
// ========================================================
if ($action === 'search_intent' && isset($_SESSION['chat_state']) && $_SESSION['chat_state'] === 'asteapta_mesaj_tichet' && !empty($textUser)) {
    $action = 'salveaza_tichet';
}

// ========================================================
// 1. LOGICA DE CĂUTARE INTELIGENTĂ (NATURAL LANGUAGE, APIs & CONTEXT)
// ========================================================
if ($action === 'search_intent' && !empty($textUser)) {
    
    if (preg_match('/(ajutor|suport|problema|admin|contact|eroare)/i', $textUser)) {
        unset($_SESSION['chat_context']); 
        $response['message'] = "Am înțeles că ai o problemă sau vrei să contactezi administratorul. Te rog apasă pe butonul de mai jos pentru a deschide un tichet.";
        $response['options'] = [
            ['text' => '🎫 Deschide Tichet Suport', 'action' => 'deschide_tichet'],
            ['text' => '🔙 Anulează', 'action' => 'start']
        ];
        echo json_encode($response);
        exit;
    }

    // ====================================================
    // 💡 A. API VREME CU MEMORIE (CONTEXT AWARENESS)
    // ====================================================
    $isWeatherFollowUp = false;
    $offset_hours = 0;

    if (preg_match('/(?:dar\s*)?(?:in|peste)\s+(\d+)\s+ore?/i', $textUser, $matches)) {
        $offset_hours = intval($matches[1]);
        if (isset($_SESSION['chat_context']) && $_SESSION['chat_context'] === 'vreme') {
            $isWeatherFollowUp = true;
        }
    }

    if (preg_match('/(vreme|temperatura|meteo|ploua|soare|frig|cald)/i', $textUser) || $isWeatherFollowUp) {
        
        $_SESSION['chat_context'] = 'vreme';

        $url = "https://api.open-meteo.com/v1/forecast?latitude=45.2692&longitude=27.9575&current_weather=true&hourly=temperature_2m,windspeed_10m&timezone=Europe%2FBucharest";
        $meteoData = @file_get_contents($url);
        
        if ($meteoData) {
            $meteo = json_decode($meteoData, true);
            
            if ($offset_hours > 0) {
                $target_time = date('Y-m-d\TH:00', strtotime("+$offset_hours hours"));
                $index = array_search($target_time, $meteo['hourly']['time']);
                
                if ($index !== false) {
                    $temp = $meteo['hourly']['temperature_2m'][$index];
                    $wind = $meteo['hourly']['windspeed_10m'][$index];
                    $response['message'] = "⏱️ **Prognoza pentru peste {$offset_hours} ore:** Temperatura în Brăila va fi de **{$temp}°C**, cu un vânt de {$wind} km/h.<br><br>Mai e ceva legat de vreme sau vrei să afli altceva?";
                } else {
                    $response['message'] = "Nu pot vedea atât de departe în viitor. 😔 Încearcă un număr mai mic de ore.";
                }
            } else {
                $temp = $meteo['current_weather']['temperature'];
                $wind = $meteo['current_weather']['windspeed'];
                $response['message'] = "☁️ **Live din Brăila:** În acest moment sunt **{$temp}°C**, iar viteza vântului este de {$wind} km/h.<br><br>Cu ce te mai pot ajuta?";
            }
        } else {
            $response['message'] = "Scuze, momentan serverul meteo nu răspunde. 😔";
        }
        $response['options'] = [['text' => '🔙 Înapoi la meniu', 'action' => 'start']];
        echo json_encode($response);
        exit;
    }

    unset($_SESSION['chat_context']);

    // ====================================================
    // 💡 B. API Curs Valutar
    // ====================================================
    if (preg_match('/(curs|euro|valuta|schimb|bani)/i', $textUser)) {
        $url = "https://api.frankfurter.app/latest?from=EUR&to=RON";
        $cursData = @file_get_contents($url);
        
        if ($cursData) {
            $curs = json_decode($cursData, true);
            $ron = $curs['rates']['RON'];
            $response['message'] = "💶 **Cursul Valutar Live:**<br>1 EUR = **{$ron} RON**.<br><br>Vrei să afli altceva?";
        } else {
            $response['message'] = "Nu am putut prelua cursul valutar în acest moment.";
        }
        $response['options'] = [['text' => '🔙 Înapoi la meniu', 'action' => 'start']];
        echo json_encode($response);
        exit;
    }

    // ====================================================
    // 💡 C. API Wikipedia
    // ====================================================
    if (preg_match('/(?:cine este|ce este|despre|cine a fost) (.*)/i', $textUser, $matches)) {
        $subiect = urlencode(trim($matches[1]));
        $options = [ 'http' => [ 'method' => 'GET', 'header' => "User-Agent: BrailaBot/1.0\r\n" ] ];
        $context = stream_context_create($options);
        $url = "https://ro.wikipedia.org/w/api.php?action=query&prop=extracts&exintro&explaintext&titles={$subiect}&format=json";
        
        $wikiData = @file_get_contents($url, false, $context);
        if ($wikiData) {
            $wiki = json_decode($wikiData, true);
            $pagini = $wiki['query']['pages'];
            $prima_pagina = reset($pagini);
            
            if (isset($prima_pagina['extract']) && !empty($prima_pagina['extract'])) {
                $rezumat = mb_strimwidth($prima_pagina['extract'], 0, 300, "...");
                $response['message'] = "📖 Uite ce am găsit pe internet despre **" . htmlspecialchars(trim($matches[1])) . "**:<br><br><i>{$rezumat}</i>";
            } else {
                $response['message'] = "Nu am găsit nicio informație relevantă pe Wikipedia despre **" . htmlspecialchars(trim($matches[1])) . "**.";
            }
        } else {
            $response['message'] = "Eroare la conectarea cu Wikipedia.";
        }
        $response['options'] = [['text' => '🔙 Înapoi la meniu', 'action' => 'start']];
        echo json_encode($response);
        exit;
    }

    // ====================================================
    // 1.2 DETECTARE INTENȚII LOCALE AVANSATE (DB ROUTING)
    // ====================================================
    
    // MÂNCARE
    if (preg_match('/(pizza|fast-food|kebab|burger|shaorma|rapid)/i', $textUser)) {
        $action = 'mancare_rapida';
    }
    elseif (preg_match('/(restaurant elegant|traditional|peste|cherhana|romantica|fita)/i', $textUser)) {
        $action = 'mancare_restaurant';
    }
    elseif (preg_match('/(mancare|manca|mananc|restaurant|foame|unde mananc|cafea|baut)/i', $textUser)) {
        $action = 'meniu_mancare';
    } 

    // TURISM
    elseif (preg_match('/(ceas|piata traian|faleza|scurt|putin timp)/i', $textUser)) {
        $action = 'turism_rapid';
    }
    elseif (preg_match('/(parc|zoo|croaziera|dunare|timp liber|plimbare lunga)/i', $textUser)) {
        $action = 'turism_lung';
    }
    elseif (preg_match('/(vizit|turism|plimbare|atracti|istoric|monument)/i', $textUser)) {
        $action = 'meniu_turism';
    } 

    // TRAFIC & MOBILITATE
    elseif (preg_match('/(accident|blocaj|politie|aglomerat|strada|waze|raport)/i', $textUser)) {
        $action = 'verifica_trafic';
    }
    elseif (preg_match('/(trafic|autobuz|bilet|transport)/i', $textUser)) {
        $action = 'meniu_trafic';
    } 

    // EVENIMENTE
    elseif (preg_match('/(teatru|muzeu|cultur|concert|arta)/i', $textUser)) {
        $action = 'evenimente_culturale';
    }
    elseif (preg_match('/(meci|sport|fotbal|competitie|alergare)/i', $textUser)) {
        $action = 'evenimente_sportive';
    }
    elseif (preg_match('/(eveniment|spectacol|festival|film|cinema|activitati)/i', $textUser)) {
        $action = 'meniu_evenimente';
    }
    
    // 1.3 FALLBACK: Căutare Evenimente în Baza de Date Internă
    else {
        $keyword = '%' . $conn->real_escape_string($textUser) . '%';
        
        $stmt = $conn->prepare("SELECT id, titlu, data_eveniment, pret FROM evenimente WHERE (titlu LIKE ? OR descriere LIKE ?) AND data_eveniment >= NOW() ORDER BY data_eveniment ASC LIMIT 3");
        $stmt->bind_param("ss", $keyword, $keyword);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $response['message'] = "Uite ce evenimente am găsit care s-ar potrivi cu **\"" . htmlspecialchars($textUser) . "\"**:<br><br>";
            
            while($ev = $result->fetch_assoc()) {
                $data_fmt = date('d/m/Y H:i', strtotime($ev['data_eveniment']));
                $pret = ($ev['pret'] > 0) ? $ev['pret'] . ' RON' : 'Gratuit';
                $response['message'] .= "• **{$ev['titlu']}** (Data: {$data_fmt} | Preț: {$pret})<br>";
                
                $response['options'][] = [
                    'text' => "➡️ Vezi " . mb_strimwidth($ev['titlu'], 0, 15, "..."), 
                    'link' => "evenimentextins.php?id=" . $ev['id']
                ];
            }
            $response['options'][] = ['text' => '🔙 Revino la Meniu', 'action' => 'start'];
            
        } else {
            $response['message'] = "Din păcate, nu am găsit evenimente, și nici nu am știut ce să caut pe internet pentru **\"" . htmlspecialchars($textUser) . "\"**.<br>Încearcă să formulezi cu *\"despre [subiect]\"* sau alege o categorie de mai jos:";
            $response['options'] = [
                ['text' => '🎫 Arată-mi toate evenimentele', 'action' => 'meniu_evenimente'],
                ['text' => '🔙 Înapoi la Meniu', 'action' => 'start']
            ];
        }
        echo json_encode($response);
        exit;
    }
}

// ========================================================
// 2. SISTEM DE TICHETE CĂTRE ADMINISTRATOR (SMART HANDOFF)
// ========================================================
if ($action === 'deschide_tichet') {
    $response['message'] = "Te rog scrie mai jos mesajul detaliat pe care vrei să-l trimiți administratorului. Încearcă să fii cât mai specific!";
    $response['options'] = [
        ['text' => '🔙 Anulează', 'action' => 'start']
    ];
    $_SESSION['chat_state'] = 'asteapta_mesaj_tichet';
    echo json_encode($response);
    exit;
}
elseif ($action === 'salveaza_tichet') {
    $user_id = $_SESSION['user_id'] ?? null;
    $nume_vizitator = $_SESSION['nume'] ?? 'Vizitator Anonim';
    $mesaj_tichet = $conn->real_escape_string($textUser);

    if (!empty($mesaj_tichet)) {
        $stmt_tichet = $conn->prepare("INSERT INTO tichete_suport (user_id, nume_vizitator, mesaj) VALUES (?, ?, ?)");
        $stmt_tichet->bind_param("iss", $user_id, $nume_vizitator, $mesaj_tichet);
        
        if ($stmt_tichet->execute()) {
            $response['message'] = "✅ Mesajul tău a fost trimis cu succes către echipă! Un administrator îl va prelua în scurt timp.";
        } else {
            $response['message'] = "❌ A apărut o eroare la salvarea tichetului. Te rog încearcă din nou mai târziu.";
        }
        $stmt_tichet->close();
    } else {
        $response['message'] = "Nu poți trimite un mesaj gol.";
    }

    unset($_SESSION['chat_state']);
    $response['options'] = [['text' => '🔙 Înapoi la meniul principal', 'action' => 'start']];
    echo json_encode($response);
    exit;
}

// ==========================================
// 3. MENIUL PRINCIPAL (RĂDĂCINA)
// ==========================================
if ($action === 'start') {
    unset($_SESSION['chat_state']); 
    unset($_SESSION['chat_context']);

    $nume_utilizator = isset($_SESSION['nume']) ? htmlspecialchars($_SESSION['nume']) : 'Vizitatorule';

    $response['message'] = "Salut, **{$nume_utilizator}**! 👋 Cu ce te pot ajuta astăzi?<br><br>💡 **Noutate:** M-am conectat la internet! Mă poți întreba *„cum e vremea?”*, *„cât e cursul euro?”* sau *„despre Panait Istrati”*.";
    $response['options'] = [
        ['text' => '🎫 Ce evenimente sunt?', 'action' => 'meniu_evenimente'],
        ['text' => '🍔 Unde pot mânca?', 'action' => 'meniu_mancare'],
        ['text' => '🏛️ Ce pot vizita?', 'action' => 'meniu_turism'],
        ['text' => '🚗 Cum e traficul?', 'action' => 'meniu_trafic'],
        ['text' => '🆘 Raportează o problemă', 'action' => 'deschide_tichet']
    ];
}

// ==========================================
// 4. RAMURA: EVENIMENTE
// ==========================================
elseif ($action === 'meniu_evenimente') {
    $response['message'] = "Super! Brăila este un oraș plin de viață. Ce fel de evenimente te atrag?";
    $response['options'] = [
        ['text' => '🎭 Cultură (Teatru, Muzee)', 'action' => 'evenimente_culturale'],
        ['text' => '⚽ Sport și Competiții', 'action' => 'evenimente_sportive'],
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
            ['text' => '🎫 Da, vreau detalii', 'link' => 'evenimentextins.php?id=' . $ev['id']],
            ['text' => '🚗 Cum e traficul?', 'action' => 'verifica_trafic'],
            ['text' => '🔙 Înapoi', 'action' => 'start']
        ];
    } else {
        $response['message'] = "Momentan nu avem evenimente culturale listate. 😔";
        $response['options'] = [['text' => 'Alege altceva', 'action' => 'start']];
    }
}
elseif ($action === 'evenimente_sportive') {
    $stmt = $conn->query("SELECT id, titlu, data_eveniment FROM evenimente WHERE categorie = 'sportiv' AND data_eveniment >= NOW() ORDER BY data_eveniment ASC LIMIT 1");
    if ($stmt->num_rows > 0) {
        $ev = $stmt->fetch_assoc();
        $data_fmt = date('d/m/Y H:i', strtotime($ev['data_eveniment']));
        $response['message'] = "Următorul eveniment sportiv este **{$ev['titlu']}** (pe {$data_fmt}). Te bagi?";
        $response['options'] = [
            ['text' => '🎫 Hai să văd detalii', 'link' => 'evenimentextins.php?id=' . $ev['id']],
            ['text' => '🔙 Înapoi', 'action' => 'start']
        ];
    } else {
        $response['message'] = "Momentan băieții sunt în pauză. Niciun eveniment sportiv în viitorul apropiat. ⚽";
        $response['options'] = [['text' => 'Alege altceva', 'action' => 'start']];
    }
}

// ==========================================
// 5. RAMURA: MÂNCARE
// ==========================================
elseif ($action === 'meniu_mancare') {
    $response['message'] = "Mi-ai făcut poftă! 🤤 Ce fel de mâncare cauți?";
    $response['options'] = [
        ['text' => '🍕 Ceva rapid (Fast-Food / Pizza)', 'action' => 'mancare_rapida'],
        ['text' => '🍽️ Un restaurant elegant / Tradițional', 'action' => 'mancare_restaurant'],
        ['text' => '🔙 Înapoi', 'action' => 'start']
    ];
}
elseif ($action === 'mancare_rapida') {
    $response['message'] = "Pentru ceva rapid, îți recomand cu căldură **Domino's Pizza** (în Barieră) sau un kebab bun la **KY'S Kebab**. Ambele sunt pe harta noastră turistică!";
    $response['options'] = [
        ['text' => '🗺️ Arată-mi restaurantele pe hartă', 'link' => 'ghid.php'],
        ['text' => '🔙 Înapoi la meniu', 'action' => 'start']
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
// 6. RAMURA: TURISM
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
// 7. RAMURA: TRAFIC & TRANSPORT
// ==========================================
elseif ($action === 'meniu_trafic') {
    $response['message'] = "Informațiile despre mobilitate sunt vitale. Ce te interesează exact?";
    $response['options'] = [
        ['text' => '🚧 Verifică raportările de trafic', 'action' => 'verifica_trafic'],
        ['text' => '🚌 Vreau să cumpăr bilet de autobuz', 'link' => 'genereaza_bilet.php?bus=1'],
        ['text' => '🔙 Înapoi', 'action' => 'start']
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