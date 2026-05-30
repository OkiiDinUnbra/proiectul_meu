<?php
$page_title = "Planificator Rută | Descoperă Brăila";
include 'header.php';

$mesaj_eroare = "";
$ruta_gasita = [];
$nume_plecare = "";
$nume_destinatie = "";

if (isset($_GET['plecare']) && isset($_GET['destinatie'])) {
    $id_plecare = intval($_GET['plecare']);
    $id_destinatie = intval($_GET['destinatie']);

    if ($id_plecare === $id_destinatie) {
        $mesaj_eroare = "Ai selectat aceeași stație pentru plecare și sosire.";
    } else {
        // 1. Preluăm toate stațiile în memorie pentru rapiditate extremă
        $nume_statii = [];
        $res_statii = $conn->query("SELECT id, nume_statie FROM transport_statii");
        while ($r = $res_statii->fetch_assoc()) {
            $nume_statii[$r['id']] = $r['nume_statie'];
        }

        $nume_plecare = $nume_statii[$id_plecare] ?? "Necunoscut";
        $nume_destinatie = $nume_statii[$id_destinatie] ?? "Necunoscut";

        // ====================================================================
        // MOTORUL ALGORITMIC DE RUTARE (Grafuri + Dijkstra)
        // ====================================================================

        // 2. Construim Graful Rețelei de Transport (Adjacency List)
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

        // Creăm muchiile (legăturile) din graf
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

        // 3. Rulăm Algoritmul Dijkstra
        $pq = new SplPriorityQueue(); // Coadă de priorități
        $pq->insert(['statie' => $id_plecare, 'directie_id' => null, 'cost' => 0, 'path' => []], 0);

        $distante = []; 
        $best_path = null;

        while (!$pq->isEmpty()) {
            $curent = $pq->extract();
            $u = $curent['statie'];
            $dir_u = $curent['directie_id'];
            $cost_u = $curent['cost'];
            $path = $curent['path'];

            // Am ajuns la destinație?
            if ($u == $id_destinatie) {
                $best_path = $path;
                break; // Dijkstra garantează că primul rezultat ajuns este drumul cel mai scurt
            }

            // Evităm ciclurile infinite (vizităm o stație pe o anumită linie doar dacă e mai ieftin)
            $state_key = $dir_u !== null ? $dir_u : 'start';
            if (isset($distante[$u][$state_key]) && $distante[$u][$state_key] <= $cost_u) {
                continue;
            }
            $distante[$u][$state_key] = $cost_u;

            if (!isset($graf[$u])) continue;

            // Explorăm stațiile următoare
            foreach ($graf[$u] as $muchie) {
                $v = $muchie['to'];
                $dir_v = $muchie['directie_id'];

                $cost_statie = 1; // 1 stație = 1 punct distanță
                $penalizare_transfer = 0;

                // Dacă trebuie să schimbăm linia, penalizăm enorm ca algoritmul să evite asta dacă poate
                if ($dir_u !== null && $dir_u != $dir_v) {
                    $penalizare_transfer = 1000; 
                }

                $cost_nou = $cost_u + $cost_statie + $penalizare_transfer;

                $path_nou = $path;
                $path_nou[] = [
                    'from' => $u,
                    'to' => $v,
                    'directie_id' => $dir_v,
                    'linia' => $muchie['linia'],
                    'tip' => $muchie['tip'],
                    'dir_nume' => $muchie['dir_nume']
                ];

                // Inserăm cu prioritate negativă (cel mai mic cost iese primul)
                $pq->insert(['statie' => $v, 'directie_id' => $dir_v, 'cost' => $cost_nou, 'path' => $path_nou], -$cost_nou);
            }
        }

        // 4. Pregătim datele pentru Afișare (Împărțim drumul pe Autobuze)
        if ($best_path) {
            $segmente = [];
            $segment_curent = null;
            $total_statii = 0;

            foreach ($best_path as $pas) {
                if ($segment_curent === null || $segment_curent['directie_id'] != $pas['directie_id']) {
                    if ($segment_curent !== null) {
                        $segmente[] = $segment_curent;
                    }
                    $segment_curent = [
                        'directie_id' => $pas['directie_id'],
                        'linia' => $pas['linia'],
                        'tip' => $pas['tip'],
                        'dir_nume' => $pas['dir_nume'],
                        'statii' => [$nume_statii[$pas['from']], $nume_statii[$pas['to']]]
                    ];
                } else {
                    $segment_curent['statii'][] = $nume_statii[$pas['to']];
                }
                $total_statii++;
            }
            if ($segment_curent !== null) {
                $segmente[] = $segment_curent;
            }

            $ruta_gasita = [
                'segmente' => $segmente,
                'total_statii' => $total_statii,
                'nr_schimbari' => count($segmente) - 1
            ];
        }
    }
}
?>

<style>
    :root { --primary: #0056b3; --accent: #ffd700; --bg: #f8f9fa; }
    .rutare-page { background: var(--bg); padding-top: 100px; min-height: 100vh; font-family: 'Segoe UI', sans-serif; }
    .container { max-width: 900px; margin: 0 auto; padding: 20px; }
    
    .route-summary { background: white; border-radius: 15px; padding: 25px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); margin-bottom: 30px; border-bottom: 4px solid var(--accent); }
    .route-nodes { display: flex; align-items: center; justify-content: space-between; position: relative; }
    .node { text-align: center; flex: 1; }
    .node span { font-weight: 700; font-size: 1.1rem; color: #333; }
    .connector { flex: 0.5; height: 2px; background: #ddd; position: relative; margin-top: 10px; }
    .connector::after { content: '➔'; position: absolute; top: -10px; left: 45%; color: #bbb; }

    .card-route { background: white; border-radius: 12px; overflow: hidden; margin-bottom: 25px; box-shadow: 0 4px 12px rgba(0,0,0,0.08); }
    .route-header { padding: 20px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #eee; background: #fdfdfd;}
    .line-badge { background: var(--primary); color: white; padding: 8px 15px; border-radius: 8px; font-weight: 800; font-size: 1.1rem; display: flex; align-items: center; gap: 10px; }
    
    .itinerary { padding: 20px; background: #fff; }
    .timeline { position: relative; padding-left: 30px; list-style: none; margin-bottom: 0;}
    .timeline::before { content: ''; position: absolute; left: 7px; top: 5px; width: 2px; height: 90%; background: #e0e0e0; }
    .timeline-item { position: relative; margin-bottom: 15px; font-size: 0.95rem; color: #555; }
    .timeline-item::before { content: ''; position: absolute; left: -28px; top: 4px; width: 12px; height: 12px; background: white; border: 2px solid var(--primary); border-radius: 50%; z-index: 2; }
    .timeline-item.active { color: var(--primary); font-weight: bold; }
    .timeline-item.active::before { background: var(--primary); }

    .route-footer { padding: 15px 20px; background: #fefefe; display: flex; justify-content: space-between; align-items: center; border-top: 1px solid #eee;}
    .btn-ticket { background: #28a745; color: white; padding: 10px 20px; border-radius: 8px; text-decoration: none; font-weight: 600; transition: 0.3s; }
    .btn-ticket:hover { background: #218838; box-shadow: 0 3px 10px rgba(40, 167, 69, 0.3); }

    .transfer-box { background: #ffc107; color: #000; padding: 12px; border-radius: 8px; text-align: center; font-weight: bold; margin: 15px 0 25px 0; box-shadow: 0 4px 6px rgba(0,0,0,0.1); border: 2px dashed #d39e00;}
</style>

<div class="rutare-page">
    <div class="container">
        <?php if ($mesaj_eroare): ?>
            <div style="background: #f8d7da; color: #721c24; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                <?= $mesaj_eroare ?>
            </div>
        <?php else: ?>
            
            <div class="route-summary">
                <div class="route-nodes">
                    <div class="node">
                        <span style="font-size:24px; display:block;">🟢</span>
                        <span><?= htmlspecialchars($nume_plecare) ?></span>
                    </div>
                    <div class="connector"></div>
                    <div class="node">
                        <span style="font-size:24px; display:block;">🏁</span>
                        <span><?= htmlspecialchars($nume_destinatie) ?></span>
                    </div>
                </div>
            </div>

            <?php if (empty($ruta_gasita)): ?>
                <div style="text-align: center; padding: 50px; background: white; border-radius: 12px; box-shadow: 0 4px 10px rgba(0,0,0,0.05);">
                    <h3 style="color: #666;">Traseu imposibil.</h3>
                    <p>Ne pare rău, dar rețeaua curentă nu permite o legătură între aceste două zone (nici măcar cu schimburi).</p>
                    <a href="transport.php" style="display:inline-block; margin-top: 15px; color: var(--primary); font-weight: bold; text-decoration:none;">⬅️ Înapoi la căutare</a>
                </div>

            <?php else: ?>
                
                <h4 style="margin-bottom: 20px; color: #333;">
    📍 Traseu Recomandat
    <?php if($ruta_gasita['nr_schimbari'] > 0) echo '<span style="color:#dc3545; font-size:14px; margin-left:10px;">(Necesită ' . $ruta_gasita['nr_schimbari'] . ' transferuri)</span>'; ?>
</h4>
                
                <div class="card-route" style="<?= $ruta_gasita['nr_schimbari'] > 0 ? 'border: 2px solid #ffc107;' : 'border: 1px solid #ddd;' ?>">
                    <div class="route-header" style="<?= $ruta_gasita['nr_schimbari'] > 0 ? 'background: #fffbea;' : '' ?>">
                        <div class="line-badge" style="<?= $ruta_gasita['nr_schimbari'] > 0 ? 'background: #e0a800;' : '' ?>">
                            ⏱️ Timp Estimat: <?= $ruta_gasita['total_statii'] * 3 ?> minute
                        </div>
                        <div style="color: #666; font-weight:bold;">
                            <?= $ruta_gasita['total_statii'] ?> stații în total
                        </div>
                    </div>

                    <div class="itinerary">
                        <?php 
                        $nr_segmente = count($ruta_gasita['segmente']);
                        foreach ($ruta_gasita['segmente'] as $index => $segment): 
                        ?>
                            
                            <p style="color: var(--primary); margin-bottom: 15px;">
                                <b><?= $index + 1 ?>. Urcă în <?= $segment['tip'] == 'autobuz' ? 'Autobuzul' : 'Tramvaiul' ?> Linia <?= htmlspecialchars($segment['linia']) ?> (spre <?= htmlspecialchars($segment['dir_nume']) ?>)</b>
                            </p>
                            
                            <ul class="timeline">
                                <?php foreach ($segment['statii'] as $i => $statie): ?>
                                    <li class="timeline-item <?= ($i == 0 || $i == count($segment['statii'])-1) ? 'active' : '' ?>">
                                        <?= htmlspecialchars($statie) ?>
                                        <?php if($i == 0 && $index == 0) echo ' <span style="color:#28a745; font-size:12px;">(Punct de plecare)</span>'; ?>
                                        <?php if($i == count($segment['statii'])-1 && $index == $nr_segmente - 1) echo ' <span style="color:#dc3545; font-size:12px;">(Destinația finală)</span>'; ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>

                            <?php if ($index < $nr_segmente - 1): ?>
                                <?php 
                                    // Găsim stația unde se intersectează (ultima stație din segmentul curent)
                                    $statie_transfer = end($segment['statii']); 
                                ?>
                                <div class="transfer-box">
                                    🔄 COBORI AICI PENTRU TRANSFER: <?= htmlspecialchars($statie_transfer) ?>
                                </div>
                            <?php endif; ?>

                        <?php endforeach; ?>
                    </div>

                    <div class="route-footer" style="<?= $ruta_gasita['nr_schimbari'] > 0 ? 'background: #fffbea;' : '' ?>">
                        <span style="color: #666;">ℹ️ Biletul de 60 de minute este valabil pentru tot traseul!</span>
                        <a href="transport.php" class="btn-ticket">🎫 Cumpără Bilet - 2.50 RON</a>
                    </div>
                </div>

            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<?php include 'footer.php'; ?>