<?php
// Funcție generală pentru a trimite emailuri către toți abonații la newsletter
function trimiteNotificareNewsletter($conn, $subiect, $mesaj_html) {
    // Selectăm doar utilizatorii care au bifat că doresc newsletter (doreste_newsletter = 1)
    $sql = "SELECT email, nume FROM utilizatori WHERE doreste_newsletter = 1";
    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        // Setăm headerele pentru a trimite un email HTML frumos formatat
        $headers  = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type: text/html; charset=UTF-8" . "\r\n";
        $headers .= "From: Descoperă Brăila <contact@braila.ro>" . "\r\n";

        while ($user = $result->fetch_assoc()) {
            $to = $user['email'];
            $nume = htmlspecialchars($user['nume']);
            
            // Construim șablonul emailului
            $continut = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; background: #1a1a24; color: #ffffff; border-radius: 10px; overflow: hidden; border: 1px solid #333;'>
                <div style='background: #ffd700; color: #111; padding: 20px; text-align: center;'>
                    <h2 style='margin: 0;'>⚓ Descoperă Brăila</h2>
                </div>
                <div style='padding: 30px;'>
                    <p style='font-size: 16px;'>Salutare, <strong>{$nume}</strong>!</p>
                    <div style='font-size: 15px; line-height: 1.6; color: #e0e0e0;'>
                        {$mesaj_html}
                    </div>
                </div>
                <div style='text-align: center; padding: 15px; font-size: 12px; color: #888; background: #111;'>
                    Ai primit acest email deoarece ești abonat la noutățile braila.ro.
                </div>
            </div>";

            // Trimitem emailul (folosim @ pentru a ascunde erorile pe localhost dacă serverul de mail nu e configurat)
            @mail($to, $subiect, $continut, $headers);
        }
    }
}
?>