<?php
$page_title = 'Transport Public | Descoperă Brăila';
include 'header.php';

$statii_query = $conn->query('SELECT id, nume_statie FROM transport_statii ORDER BY nume_statie ASC');
$toate_statiile = [];
while ($row = $statii_query->fetch_assoc()) {
    $toate_statiile[] = $row;
}
?>



<section class="hero-transport">
    <h1>Smart Transit Brăila</h1>
    <p style="font-size: 18px;">Găsește traseul optim și achiziționează bilete digitale instant.</p>
</section>

<div class="transport-container">

    <div class="card-modul">
        <h2>📍 Planifică-ți Călătoria</h2>
        <form method="GET" action="rutare.php">
            <div class="form-group">
                <label>Punct de plecare:</label>
                <select name="plecare" required>
                    <option value="">-- Alege stația de plecare --</option>
                    <?php foreach ($toate_statiile as $statie): ?>
                        <option value="<?= $statie['id'] ?>"><?= htmlspecialchars($statie['nume_statie']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Destinație:</label>
                <select name="destinatie" required>
                    <option value="">-- Alege destinația --</option>
                    <?php foreach ($toate_statiile as $statie): ?>
                        <option value="<?= $statie['id'] ?>"><?= htmlspecialchars($statie['nume_statie']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <button type="submit" class="btn-full">🔍 Caută Traseul</button>
        </form>
    </div>

    <div class="card-modul">
        <h2>🎫 Bilet Digital (60 min)</h2>
        <?php if (isset($_SESSION['user_id'])): ?>
            <p class="bilet-info">Biletul tău va fi valabil 60 de minute pe orice linie Braicar din momentul achiziției. Plata se face securizat.</p>

            <div class="ticket-demo">
                <h3 style="margin-bottom: 10px;">Preț: 2.50 RON</h3>
<?php $_SESSION['payment_token'] = bin2hex(random_bytes(16)); ?>
<form method="POST" action="genereaza_bilet.php">
    <input type="hidden" name="payment_token" value="<?= $_SESSION['payment_token'] ?>">
    <button type="submit" class="btn-full" style="background: #28a745; color: white;">💳 Cumpără Bilet Acum</button>
</form>
            </div>
        <?php else: ?>
            <div class="ticket-demo">
                <p style="color: #dc3545; font-weight: bold; margin-bottom: 15px;">Trebuie să fii autentificat pentru a cumpăra bilete.</p>
                <button onclick="openPopup('loginPopup')" class="btn-full">🔒 Login pentru achiziție</button>
            </div>
        <?php endif; ?>
    </div>

</div>

<?php include 'footer.php'; ?>