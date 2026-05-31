<?php
require_once 'db_connect.php';
$page_title = t('transport_title') . ' | ' . t('page_title');
include 'header.php';

$statii_query = $conn->query('SELECT id, nume_statie FROM transport_statii ORDER BY nume_statie ASC');
$toate_statiile = [];
while ($row = $statii_query->fetch_assoc()) {
    $toate_statiile[] = $row;
}
?>



<section class="hero-transport">
    <h1><?= t('transport_title') ?></h1>
    <p style="font-size: 18px;"><?= t('transport_subtitle') ?></p>
</section>

<div class="transport-container">

    <div class="card-modul">
        <h2><?= t('transport_plan') ?></h2>
        <form method="GET" action="rutare.php">
            <div class="form-group">
                <label><?= t('transport_from') ?></label>
                <select name="plecare" required>
                    <option value=""><?= t('transport_from_placeholder') ?></option>
                    <?php foreach ($toate_statiile as $statie): ?>
                        <option value="<?= $statie['id'] ?>"><?= htmlspecialchars($statie['nume_statie']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label><?= t('transport_to') ?></label>
                <select name="destinatie" required>
                    <option value=""><?= t('transport_to_placeholder') ?></option>
                    <?php foreach ($toate_statiile as $statie): ?>
                        <option value="<?= $statie['id'] ?>"><?= htmlspecialchars($statie['nume_statie']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <button type="submit" class="btn-full"><?= t('transport_search') ?></button>
        </form>
    </div>

    <div class="card-modul">
        <h2><?= t('transport_digital_ticket') ?></h2>
        <?php if (isset($_SESSION['user_id'])): ?>
            <p class="bilet-info"><?= t('transport_ticket_info') ?></p>

            <div class="ticket-demo">
                <h3 style="margin-bottom: 10px;"><?= t('transport_price') ?></h3>
<?php $_SESSION['payment_token'] = bin2hex(random_bytes(16)); ?>
<form method="POST" action="genereaza_bilet.php">
    <input type="hidden" name="payment_token" value="<?= $_SESSION['payment_token'] ?>">
    <button type="submit" class="btn-full" style="background: #28a745; color: white;">💳 <?= t('transport_buy_ticket') ?></button>
</form>
            </div>
        <?php else: ?>
            <div class="ticket-demo">
                <p style="color: #dc3545; font-weight: bold; margin-bottom: 15px;"><?= t('transport_login_required') ?></p>
                <button onclick="openPopup('loginPopup')" class="btn-full">🔒 Login pentru achiziție</button>
            </div>
        <?php endif; ?>
    </div>

</div>

<?php include 'footer.php'; ?>