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
        font-size: 38px;
        color: var(--text-main);
        margin-bottom: 10px;
    }
    .hero-transport p {
        color: var(--text-light);
        font-size: 16px;
    }

    .transport-container {
        display: flex;
        justify-content: center;
        gap: 30px;
        max-width: 900px; /* Lățime redusă pentru un aspect mai compact */
        margin: 0 auto 60px;
        padding: 0 20px;
        flex-wrap: wrap;
        flex-grow: 1;
    }

    .card-modul {
        background: var(--card-bg);
        backdrop-filter: blur(12px);
        -webkit-backdrop-filter: blur(12px);
        border: 1px solid var(--border-color);
        border-radius: 16px;
        padding: 25px; /* Spațiere interioară redusă */
        flex: 1 1 300px;
        max-width: 420px; /* Limităm alungirea cardurilor */
        color: var(--text-main);
        box-shadow: var(--shadow-medium);
    }

    .card-modul h2 {
        color: var(--accent-primary);
        margin-bottom: 20px;
        border-bottom: 1px solid var(--border-light);
        padding-bottom: 12px;
        font-size: 20px;
    }
    
    .card-modul label { 
        color: var(--text-light);
        font-weight: 500;
        display: block;
        margin-bottom: 8px;
        font-size: 14px;
    }
    
    .bilet-info { 
        color: var(--text-light); 
        margin-bottom: 15px;
        font-size: 13px; 
        line-height: 1.5; 
    }
    
    .form-group { margin-bottom: 15px; }
    
    .form-group select {
        width: 100%;
        padding: 12px 15px;
        border-radius: 10px;
        border: 1px solid var(--border-color);
        background: var(--input-bg);
        color: var(--text-main);
        outline: none;
        font-family: 'Poppins', sans-serif;
        font-size: 14px;
        transition: 0.3s;
    }
    .form-group select:focus { border-color: var(--link-color); box-shadow: 0 0 0 3px rgba(91, 176, 255, 0.15); }
    .form-group select option { background: var(--bg-section); color: var(--text-main); }
    
    .btn-full {
        width: 100%;
        padding: 12px;
        border: none;
        border-radius: 10px;
        font-weight: bold;
        font-size: 15px;
        cursor: pointer;
        background: var(--accent-primary);
        color: #111;
        transition: 0.3s;
    }
    .btn-full:hover { background: var(--accent-primary-dark); transform: translateY(-2px); }
    .btn-success { background: var(--accent-success); color: #000; }
    .btn-success:hover { background: var(--accent-success-dark); }
    
    .ticket-demo {
        background: rgba(0,0,0,0.3);
        padding: 20px;
        border-radius: 12px;
        text-align: center;
        border: 1px dashed rgba(255, 215, 0, 0.3);
    }
    
    .text-error { color: var(--accent-delete); font-size: 14px; margin-bottom: 10px; font-weight: 600; }
    .btn-accent-border { background: transparent; border: 1px solid var(--accent-primary); color: var(--accent-primary); }
    .btn-accent-border:hover { background: var(--accent-primary); color: #000; }
</style>

<section class="hero-transport">
    <h1><?= t('transport_title') ?></h1>
    <p><?= t('transport_subtitle') ?></p>
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
                <h3 style="margin-bottom: 15px; color: var(--text-main); font-size: 22px;"><?= t('transport_price') ?></h3>
                <?php $_SESSION['payment_token'] = bin2hex(random_bytes(16)); ?>
                <form method="POST" action="genereaza_bilet.php">
                    <input type="hidden" name="payment_token" value="<?= $_SESSION['payment_token'] ?>">
                    <button type="submit" class="btn-full btn-success">💳 <?= t('transport_buy_ticket') ?></button>
                </form>
            </div>
        <?php else: ?>
            <div class="ticket-demo">
                <p class="text-error"><?= t('transport_login_required') ?></p>
                <button onclick="openPopup('loginPopup')" class="btn-full btn-accent-border">🔒 Login pentru achiziție</button>
            </div>
        <?php endif; ?>
    </div>

</div>

<?php include 'footer.php'; ?>