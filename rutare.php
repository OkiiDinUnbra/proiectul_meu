<?php
$page_title = "Planificator Rută | Descoperă Brăila";
include 'header.php';

$mesaj_eroare = "";
$ruta_gasita = [];
$nume_plecare = "";
$nume_destinatie = "";
$distanta_mers_jos_start = 0;
$distanta_mers_jos_end = 0;

// ==========================================
// FORMULA HAVERSINE (Găsește distanța dintre 2 puncte GPS în metri)
// ==========================================
function calculeazaDistantaMetri($lat1, $lon1, $lat2, $lon2) {
    if (!$lat1 || !$lon1 || !$lat2 || !$lon2) return 999999;
    $earthRadius = 6371000; // Raza Pământului în metri
    $latDelta = deg2rad($lat2 - $lat1);
    $lonDelta = deg2rad($lon2 - $lon1);
    $a = sin($latDelta / 2) * sin($latDelta / 2) +
         cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
         sin($lonDelta / 2) * sin($lonDelta / 2);
    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
    return round($earthRadius * $c);
}

// Funcția Euristică pentru Algoritmul A*
function heuristic($lat1, $lon1, $lat2, $lon2) {
    return calculeazaDistantaMetri($lat1, $lon1, $lat2, $lon2) / 100; 
}

// Dacă am primit datele din formularul "Smart Route"
if (isset($_GET['gps_lat']) && isset($_GET['gps_lng']) && isset($_GET['adresa_destinatie'])) {
    
    $user_lat = floatval($_GET['gps_lat']);
    $user_lng = floatval($_GET['gps_lng']);
    $adresa_dest = trim($_GET['adresa_destinatie']);

    if ($user_lat == 0 || $user_lng == 0) {
        $mesaj_eroare = "Nu am putut prelua locația ta GPS. Apasă butonul de locație.";
    } else {
       // 1. GEOCODING: Transformăm adresa/atracția în coordonate (Nominatim)
        $context = stream_context_create(['http' => ['header' => "User-Agent: BrailaTransportApp/1.0\r\n"]]);
        
        // Am schimbat căutarea din "street" într-o căutare generală "q" (Puncte de interes, restaurante, muzee, străzi)
        $cautare_libera = urlencode($adresa_dest . ", Brăila, România");
        $url_nominatim = "https://nominatim.openstreetmap.org/search?format=json&q=" . $cautare_libera;
        
        $raspuns_geo = @file_get_contents($url_nominatim, false, $context);
        $date_geo = json_decode($raspuns_geo, true);

        if (empty($date_geo)) {
            $mesaj_eroare = "Nu am găsit locația/atracția introdusă în Brăila. Încearcă să o scrii mai clar (ex: 'Faleza Dunării', 'Restaurant...', 'Calea Galați').";
        } else {
            $dest_lat = floatval($date_geo[0]['lat']);
            $dest_lng = floatval($date_geo[0]['lon']);

            // 2. Extragem toate stațiile din baza de date
            $statii_date = [];
            $res_statii = $conn->query("SELECT id, nume_statie, lat, lng FROM transport_statii WHERE lat IS NOT NULL");
            while ($r = $res_statii->fetch_assoc()) {
                $statii_date[$r['id']] = $r;
            }

            if (empty($statii_date)) {
                $mesaj_eroare = "Baza de date nu are stații cu coordonate GPS salvate încă.";
            } else {
                // 3. Găsim stația CEA MAI APROPIATĂ de locația curentă (Start)
                $id_plecare = null;
                $min_dist_start = 999999;
                
                // 4. Găsim stația CEA MAI APROPIATĂ de adresa căutată (End)
                $id_destinatie = null;
                $min_dist_end = 999999;

                foreach ($statii_date as $id => $statie) {
                    $dist_start = calculeazaDistantaMetri($user_lat, $user_lng, $statie['lat'], $statie['lng']);
                    if ($dist_start < $min_dist_start) {
                        $min_dist_start = $dist_start;
                        $id_plecare = $id;
                    }

                    $dist_end = calculeazaDistantaMetri($dest_lat, $dest_lng, $statie['lat'], $statie['lng']);
                    if ($dist_end < $min_dist_end) {
                        $min_dist_end = $dist_end;
                        $id_destinatie = $id;
                    }
                }

                $nume_plecare = $statii_date[$id_plecare]['nume_statie'];
                $nume_destinatie = $statii_date[$id_destinatie]['nume_statie'];
                $distanta_mers_jos_start = $min_dist_start;
                $distanta_mers_jos_end = $min_dist_end;

                // ====================================================================
                // 5. MOTORUL ALGORITMIC A* (A-Star) IDENTIC CA ÎNAINTE
                // ====================================================================
                
                $sql_rute = "SELECT r.statie_id, r.directie_id, r.ordine, l.numar_linia, l.tip_vehicul, d.nume_directie
                             FROM transport_rute r
                             JOIN transport_directii d ON r.directie_id = d.id
                             JOIN transport_linii l ON d.linia_id = l.id
                             ORDER BY r.directie_id, r.ordine ASC";
                $res_rute = $conn->query($sql_rute);
                
                $trasee_pe_directii = [];
                while ($row = $res_rute->fetch_assoc()) {
                    $trasee_pe_directii[$row['directie_id']][] = $row;
                }

                $graf = [];
                foreach ($trasee_pe_directii as $dir_id => $statii) {
                    for ($i = 0; $i < count($statii) - 1; $i++) {
                        $curent = $statii[$i];
                        $urmator = $statii[$i + 1];

                        $graf[$curent['statie_id']][] = [
                            'to' => $urmator['statie_id'],
                            'directie_id' => $dir_id,
                            'linia' => $curent['numar_linia'],
                            'tip' => $curent['tip_vehicul'],
                            'dir_nume' => $curent['nume_directie']
                        ];
                    }
                }

                // A* Logic...
                $pq = new SplPriorityQueue();
                $g_score = [];
                $best_path = null;

                $h_start = heuristic($statii_date[$id_plecare]['lat'], $statii_date[$id_plecare]['lng'], $dest_lat, $dest_lng);
                $pq->insert(['statie' => $id_plecare, 'directie_id' => null, 'g' => 0, 'path' => []], -$h_start);

                while (!$pq->isEmpty()) {
                    $curent = $pq->extract();
                    $u = $curent['statie'];
                    $dir_u = $curent['directie_id'];
                    $g_u = $curent['g'];
                    $path = $curent['path'];

                    if ($u == $id_destinatie) {
                        $best_path = $path;
                        break;
                    }

                    $state_key = $dir_u !== null ? $dir_u : 'start';
                    if (isset($g_score[$u][$state_key]) && $g_score[$u][$state_key] <= $g_u) { continue; }
                    $g_score[$u][$state_key] = $g_u;

                    if (!isset($graf[$u])) continue;

                    foreach ($graf[$u] as $muchie) {
                        $v = $muchie['to'];
                        $dir_v = $muchie['directie_id'];

                        $cost_statie = 1;
                        $penalizare_transfer = ($dir_u !== null && $dir_u != $dir_v) ? 500 : 0;
                        $tentative_g = $g_u + $cost_statie + $penalizare_transfer;
                        
                        $path_nou = $path;
                        $path_nou[] = [
                            'from' => $u, 'to' => $v, 'directie_id' => $dir_v,
                            'linia' => $muchie['linia'], 'tip' => $muchie['tip'], 'dir_nume' => $muchie['dir_nume']
                        ];

                        $h_v = heuristic($statii_date[$v]['lat'], $statii_date[$v]['lng'], $dest_lat, $dest_lng);
                        $pq->insert(['statie' => $v, 'directie_id' => $dir_v, 'g' => $tentative_g, 'path' => $path_nou], -($tentative_g + $h_v));
                    }
                }

                if ($best_path) {
                    $segmente = [];
                    $segment_curent = null;
                    $total_statii = 0;

                    foreach ($best_path as $pas) {
                        if ($segment_curent === null || $segment_curent['directie_id'] != $pas['directie_id']) {
                            if ($segment_curent !== null) { $segmente[] = $segment_curent; }
                            $segment_curent = [
                                'directie_id' => $pas['directie_id'], 'linia' => $pas['linia'],
                                'tip' => $pas['tip'], 'dir_nume' => $pas['dir_nume'],
                                'statii' => [$statii_date[$pas['from']]['nume_statie'], $statii_date[$pas['to']]['nume_statie']]
                            ];
                        } else {
                            $segment_curent['statii'][] = $statii_date[$pas['to']]['nume_statie'];
                        }
                        $total_statii++;
                    }
                    if ($segment_curent !== null) { $segmente[] = $segment_curent; }

                    $ruta_gasita = [
                        'segmente' => $segmente,
                        'total_statii' => $total_statii,
                        'nr_schimbari' => count($segmente) - 1
                    ];
                }
            }
        }
    }
}
?>

<style>
    body { background-color: #0A192F; color: #fff; margin: 0; padding: 0; }
    
    .rutare-page {
        padding: 140px 20px 60px;
        min-height: 100vh;
        font-family: 'Segoe UI', sans-serif;
    }
    
    .container { max-width: 900px; margin: 0 auto; }
    
    .route-summary {
        background: rgba(255, 255, 255, 0.03);
        border-radius: 16px;
        padding: 30px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.5);
        margin-bottom: 40px;
        border: 1px solid rgba(255,255,255,0.1);
        backdrop-filter: blur(15px);
        border-top: 4px solid #38bdf8;
    }
    
    .route-nodes { display: flex; align-items: center; justify-content: space-between; position: relative; }
    .node { text-align: center; flex: 1; }
    .node span { font-weight: 800; font-size: 1.2rem; color: #ffffff; display: block; margin-top: 8px;}
    .connector { flex: 0.5; height: 2px; background: rgba(56, 189, 248, 0.4); position: relative; }
    .connector::after { content: '➔'; position: absolute; top: -10px; left: 45%; color: #38bdf8; font-size: 20px;}

    .card-route {
        background: rgba(10, 25, 47, 0.6);
        border-radius: 16px;
        overflow: hidden;
        margin-bottom: 25px;
        box-shadow: 0 10px 35px rgba(0,0,0,0.4);
        border: 1px solid rgba(255, 255, 255, 0.1);
        backdrop-filter: blur(15px);
    }
    
    .route-header {
        padding: 20px 25px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 1px solid rgba(255,255,255,0.1);
        background: rgba(0,0,0,0.2);
    }
    
    .line-badge {
        background: #38bdf8;
        color: #0f172a;
        padding: 8px 18px;
        border-radius: 8px;
        font-weight: 800;
        font-size: 1.1rem;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .itinerary { padding: 30px; }
    
    .timeline { position: relative; padding-left: 35px; list-style: none; margin-bottom: 0; }
    .timeline::before { content: ''; position: absolute; left: 10px; top: 10px; width: 3px; height: 90%; background: rgba(255,255,255,0.1); border-radius: 10px;}
    
    .timeline-item { position: relative; margin-bottom: 20px; font-size: 1.05rem; color: #a8b2d1; font-weight: 500;}
    .timeline-item::before {
        content: ''; position: absolute; left: -32px; top: 3px; width: 14px; height: 14px;
        background: #0A192F; border: 3px solid #38bdf8; border-radius: 50%; z-index: 2;
    }
    
    .timeline-item.active { color: #ffffff; font-weight: 700; font-size: 1.1rem;}
    .timeline-item.active::before { background: #38bdf8; box-shadow: 0 0 15px rgba(56,189,248,0.8); }

    .route-footer {
        padding: 20px 30px;
        background: rgba(0,0,0,0.3);
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-top: 1px solid rgba(255,255,255,0.1);
    }
    
    .btn-ticket {
        background: #10b981;
        color: white;
        padding: 12px 25px;
        border-radius: 8px;
        text-decoration: none;
        font-weight: 800;
        transition: 0.3s;
        box-shadow: 0 4px 15px rgba(16, 185, 129, 0.4);
    }
    .btn-ticket:hover { background: #059669; transform: translateY(-2px); }

    .transfer-box {
        background: rgba(245, 158, 11, 0.15);
        color: #f59e0b;
        padding: 15px;
        border-radius: 12px;
        text-align: center;
        font-weight: 800;
        margin: 25px 0;
        border: 2px dashed rgba(245, 158, 11, 0.5);
    }
</style>

<div class="rutare-page fade-up-element">
    <div class="container">
        
        <?php if ($mesaj_eroare): ?>
            <div style="background: rgba(220, 53, 69, 0.1); border: 1px solid #dc3545; color: #ff4d4d; padding: 20px; border-radius: 12px; margin-bottom: 30px; font-weight: bold; text-align: center;">
                <?= $mesaj_eroare ?>
            </div>
        <?php else: ?>
            
            <div class="route-summary">
                <div class="route-nodes">
                    <div class="node">
                        <span style="font-size:32px;">📍</span>
                        <span><?= htmlspecialchars($nume_plecare) ?></span>
                    </div>
                    <div class="connector"></div>
                    <div class="node">
                        <span style="font-size:32px;">🏁</span>
                        <span><?= htmlspecialchars($nume_destinatie) ?></span>
                    </div>
                </div>
            </div>

            <?php if (empty($ruta_gasita)): ?>
                <div style="text-align: center; padding: 60px 30px; background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.1); border-radius: 16px;">
                    <h3 style="color: #a8b2d1; font-size: 24px; margin-bottom: 15px;">Traseu imposibil.</h3>
                    <p style="color: #8892b0; font-size: 16px;">Ne pare rău, dar rețeaua curentă nu permite o legătură între aceste două zone (nici măcar cu schimburi).</p>
                    <a href="transport.php" style="display:inline-block; margin-top: 25px; color: #38bdf8; font-weight: bold; text-decoration:none; padding: 10px 20px; border: 1px solid #38bdf8; border-radius: 8px;">⬅️ Înapoi la căutare</a>
                </div>
            <?php else: ?>
                
                <h3 style="margin-bottom: 25px; color: #ffffff; font-size: 26px;">
                    Traseu Optimizat A*
                    <?php if($ruta_gasita['nr_schimbari'] > 0) echo '<span style="color:#f59e0b; font-size:16px; margin-left:10px; font-weight: 600;">(' . $ruta_gasita['nr_schimbari'] . ' transferuri)</span>'; ?>
                </h3>
                <div style="background: rgba(16, 185, 129, 0.1); border: 1px dashed #10b981; padding: 15px; border-radius: 12px; margin-bottom: 20px; color: #a7f3d0;">
    🚶‍♂️ <b>Pasul 1:</b> Mergi pe jos ~<?= $distanta_mers_jos_start ?> metri de la locația ta până la stația <b><?= $nume_plecare ?></b>.
</div>

<!-- AICI VINE CARDUL CU AUTOBUZELE -->

<div style="background: rgba(16, 185, 129, 0.1); border: 1px dashed #10b981; padding: 15px; border-radius: 12px; margin-top: 20px; color: #a7f3d0;">
    🚶‍♂️ <b>Pasul Final:</b> După ce cobori la <b><?= $nume_destinatie ?></b>, mai mergi pe jos ~<?= $distanta_mers_jos_end ?> metri până la destinație!
</div>
                <div class="card-route" style="<?= $ruta_gasita['nr_schimbari'] > 0 ? 'border-color: rgba(245, 158, 11, 0.4);' : '' ?>">
                    <div class="route-header">
                        <div class="line-badge" style="<?= $ruta_gasita['nr_schimbari'] > 0 ? 'background: #f59e0b; color: #fff;' : '' ?>">
                            ⏱️ Timp Estimat: <?= $ruta_gasita['total_statii'] * 3 ?> minute
                        </div>
                        <div style="color: #a8b2d1; font-weight:600; font-size: 16px;">
                            <?= $ruta_gasita['total_statii'] ?> stații în total
                        </div>
                    </div>

                    <div class="itinerary">
                        <?php 
                        $nr_segmente = count($ruta_gasita['segmente']);
                        foreach ($ruta_gasita['segmente'] as $index => $segment): 
                        ?>
                            
                            <h4 style="color: #38bdf8; margin-bottom: 20px; font-size: 18px;">
                                🚌 Pasul <?= $index + 1 ?>: Ia <?= $segment['tip'] == 'autobuz' ? 'Autobuzul' : 'Tramvaiul' ?> Linia <?= htmlspecialchars($segment['linia']) ?> <br>
                                <span style="font-size: 14px; color: #8892b0; font-weight: 500;">(Direcția: <?= htmlspecialchars($segment['dir_nume']) ?>)</span>
                            </h4>
                            
                            <ul class="timeline">
                                <?php foreach ($segment['statii'] as $i => $statie): ?>
                                    <li class="timeline-item <?= ($i == 0 || $i == count($segment['statii'])-1) ? 'active' : '' ?>">
                                        <?= htmlspecialchars($statie) ?>
                                        
                                        <?php if($i == 0 && $index == 0) echo ' <span style="color:#10b981; font-size:13px; font-weight:bold; margin-left:10px;">[Urcare Aici]</span>'; ?>
                                        <?php if($i == count($segment['statii'])-1 && $index == $nr_segmente - 1) echo ' <span style="color:#dc3545; font-size:13px; font-weight:bold; margin-left:10px;">[Destinație Finală]</span>'; ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>

                            <?php if ($index < $nr_segmente - 1): ?>
                                <?php $statie_transfer = end($segment['statii']); ?>
                                <div class="transfer-box">
                                    🔄 COBORI AICI PENTRU TRANSFER: <?= htmlspecialchars($statie_transfer) ?>
                                </div>
                            <?php endif; ?>

                        <?php endforeach; ?>
                    </div>

                    <div class="route-footer">
                        <span style="color: #e2e8f0; font-size: 15px; font-weight: 500;">ℹ️ Biletul este valabil 60 de minute pe tot traseul!</span>
                        <a href="transport.php" class="btn-ticket">🎫 Cumpără Bilet - 3 RON</a>
                    </div>
                </div>

            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<?php include 'footer.php'; ?>