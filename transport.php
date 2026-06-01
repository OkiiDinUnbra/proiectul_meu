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

<style>
    /* Fundalul general al paginii se adapteaza la tema */
    body {
        background-color: var(--bg-main) !important;
        color: var(--text-main);
        margin: 0;
        padding: 0;
        min-height: 100vh;
        display: flex;
        flex-direction: column;
    }

    .hero-transport {
        text-align: center;
        padding: 140px 20px 40px;
        background: var(--bg-main) !important; 
        background-image: none !important;
        color: var(--text-main);
    }
    .hero-transport h1 {
        font-size: 42px;
        color: var(--text-main);
        margin-bottom: 10px;
    }

    .hero-transport p {
        color: var(--text-light);
    }

    /* Containerul pentru carduri */
    .transport-container {
        display: flex;
        justify-content: center;
        gap: 30px;
        max-width: 1000px;
        margin: 0 auto 60px;
        padding: 0 20px;
        flex-wrap: wrap;
        flex-grow: 1;
    }

    /* Design curat, premium - adaptat la tema */
    .card-modul {
        background: var(--card-bg);
        border: 1px solid var(--border-color);
        border-radius: 20px;
        padding: 40px;
        flex: 1;
        min-width: 320px;
        color: var(--text-main);
        box-shadow: 0 10px 30px var(--shadow-light);
    }

    .card-modul h2 {
        color: #ffd700;
        margin-bottom: 25px;
        border-bottom: 1px solid var(--border-light);
        padding-bottom: 15px;
        font-size: 22px;
    }
    
    .card-modul label { 
        color: var(--text-light);
        font-weight: 500;
        display: block;
        margin-bottom: 8px;
    }
    
    .bilet-info { color: var(--text-light); margin-bottom: 20px; font-size: 14px; line-height: 1.5; }
    
    /* Input-uri adaptate temei */
    .form-group { margin-bottom: 25px; }
    .form-group select {
        width: 100%;
        padding: 14px 15px;
        border-radius: 10px;
        border: 1px solid var(--border-color);
        background: var(--input-bg);
        color: var(--text-main);
        outline: none;
        font-family: 'Poppins', sans-serif;
        font-size: 15px;
        transition: 0.3s;
    }
    .form-group select:focus { border-color: #ffd700; }
    .form-group select option { background: var(--card-bg); color: var(--text-main); }
    
    /* Butoane */
    .btn-full {
        width: 100%;
        padding: 15px;
        border: none;
        border-radius: 10px;
        font-weight: bold;
        font-size: 16px;
        cursor: pointer;
        background: #ffd700;
        color: #111;
        transition: 0.3s;
    }
    .btn-full:hover { background: #e6c200; transform: translateY(-2px); }
    
    .ticket-demo {
        background: rgba(0,0,0,0.3);
        padding: 30px;
        border-radius: 15px;
        text-align: center;
        border: 1px dashed rgba(255, 215, 0, 0.3);
    }
</style>

<section class="hero-transport">
    <h1><?= t('transport_title') ?></h1>
    <p style="font-size: 18px;"><?= t('transport_subtitle') ?></p>
</section>

<div class="transport-container">

    <div class="card-modul">
        <h2>📍 <?= t('transport_plan') ?></h2>
        <form method="GET" action="rutare.php">
            <div class="form-group">
                <label><?= t('transport_from') ?></label>
                <select name="plecare" required>
                    <option value="" disabled selected><?= t('transport_from_placeholder') ?></option>
                    <?php foreach ($toate_statiile as $statie): ?>
                        <option value="<?= $statie['id'] ?>"><?= htmlspecialchars($statie['nume_statie']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label><?= t('transport_to') ?></label>
                <select name="destinatie" required>
                    <option value="" disabled selected><?= t('transport_to_placeholder') ?></option>
                    <?php foreach ($toate_statiile as $statie): ?>
                        <option value="<?= $statie['id'] ?>"><?= htmlspecialchars($statie['nume_statie']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <button type="submit" class="btn-full">🔍 <?= t('transport_search') ?></button>
        </form>
    </div>

    <div class="card-modul">
        <h2>🎫 <?= t('transport_digital_ticket') ?></h2>
        <?php if (isset($_SESSION['user_id'])): ?>
            <p class="bilet-info"><?= t('transport_ticket_info') ?></p>

            <div class="ticket-demo">
                <h3 style="margin-bottom: 20px; color: white; font-size: 24px;"><?= t('transport_price') ?></h3>
                <?php $_SESSION['payment_token'] = bin2hex(random_bytes(16)); ?>
                <form method="POST" action="genereaza_bilet.php">
                    <input type="hidden" name="payment_token" value="<?= $_SESSION['payment_token'] ?>">
                    <button type="submit" class="btn-full" style="background: #28a745; color: white; border: 1px solid #1e7e34;">💳 <?= t('transport_buy_ticket') ?></button>
                </form>
            </div>
        <?php else: ?>
            <div class="ticket-demo">
                <p style="color: #ff6b6b; font-weight: 500; margin-bottom: 20px; font-size: 15px;"><?= t('transport_login_required') ?></p>
                <button onclick="openPopup('loginPopup')" class="btn-full" style="background: rgba(255,255,255,0.1); color: #ffd700; border: 1px solid #ffd700;">🔒 Login pentru achiziție</button>
            </div>
        <?php endif; ?>
    </div>

</div>

<?php include 'footer.php'; ?>