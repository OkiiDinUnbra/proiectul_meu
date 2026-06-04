<?php
// ACEST FIȘIER TREBUIE SĂ FIE RULAT ZILNIC (O SINGURĂ DATĂ PE ZI) DE CĂTRE SERVER VIA CRON JOB
require_once 'db_connect.php';
require_once 'notificari.php';

// Căutăm evenimentele care au loc exact peste 3 zile
$sql = "SELECT id, titlu, data_eveniment, pret FROM evenimente WHERE DATE(data_eveniment) = CURDATE() + INTERVAL 3 DAY";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    while ($eveniment = $result->fetch_assoc()) {
        $titlu = htmlspecialchars($eveniment['titlu']);
        $pret = $eveniment['pret'];
        $data_formatata = date('d.m.Y H:i', strtotime($eveniment['data_eveniment']));
        
        $subiect = "📅 Mai sunt 3 zile până la: " . $titlu;
        
        $mesaj_html = "<h3 style='color: #ffd700;'>Nu rata evenimentul!</h3>";
        $mesaj_html .= "<p>Îți reamintim că <strong>{$titlu}</strong> va avea loc pe <strong>{$data_formatata}</strong>.</p>";
        
        if ($pret > 0) {
            $mesaj_html .= "<p>Biletele se epuizează rapid. Achiziționează biletul tău digital acum!</p>";
            $mesaj_html .= "<a href='https://braila.ro/evenimentextins.php?id={$eveniment['id']}' style='background: #28a745; color: #111; font-weight: bold; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin-top: 10px;'>🎟️ Cumpără Bilet ({$pret} RON)</a>";
        } else {
            $mesaj_html .= "<p>Accesul este <strong>GRATUIT</strong>. Te așteptăm cu drag!</p>";
            $mesaj_html .= "<a href='https://braila.ro/evenimentextins.php?id={$eveniment['id']}' style='background: #3399ff; color: white; font-weight: bold; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin-top: 10px;'>ℹ️ Vezi Detaliile</a>";
        }

        trimiteNotificareNewsletter($conn, $subiect, $mesaj_html);
    }
    echo "Notificările au fost trimise cu succes pentru evenimentele de peste 3 zile.";
} else {
    echo "Nu există evenimente programate peste exact 3 zile.";
}
?>