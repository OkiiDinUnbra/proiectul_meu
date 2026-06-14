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
    /* FUNDAL FĂRĂ FILTRU ÎNTUNECAT */
    body {
        background: url('img/transport-bg.jpg') no-repeat center center fixed !important;
        background-size: cover !important;
        color: var(--text-main);
        margin: 0;
        padding: 0;
        min-height: 100vh;
        display: flex;
        flex-direction: column;
    }

    .hero-transport {
        text-align: center;
        padding: 180px 20px 60px;
        background: transparent !important; 
        color: #ffffff;
    }
    .hero-transport h1 {
        font-size: 54px; 
        color: #ffffff;
        margin-bottom: 10px;
        text-shadow: 0 4px 10px rgba(0,0,0,0.8); 
    }
    .hero-transport p {
        color: #ffffff;
        font-size: 22px; 
        text-shadow: 0 2px 5px rgba(0,0,0,0.8);
    }

    .transport-container {
        display: flex;
        justify-content: center;
        align-items: flex-start;
        gap: 40px;
        max-width: 1000px; 
        margin: 0 auto 60px;
        padding: 0 20px;
        flex-wrap: wrap;
        flex-grow: 1;
        position: relative;
        z-index: 2;
    }

    .card-modul {
        background: var(--card-bg);
        backdrop-filter: blur(12px);
        -webkit-backdrop-filter: blur(12px);
        border: 1px solid var(--border-color);
        border-radius: 16px;
        padding: 35px; 
        flex: 1 1 350px;
        max-width: 460px; 
        color: var(--text-main);
        box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        transition: transform 0.3s;
        height: auto; 
    }
    
    .card-modul:hover { transform: translateY(-5px); }

    .card-modul h2 {
        color: var(--text-main);
        margin-bottom: 25px;
        border-bottom: 1px solid var(--border-color);
        padding-bottom: 15px;
        font-size: 28px; 
        font-weight: 800;
        text-align: center;
    }
    
    .card-modul label { 
        color: var(--text-main);
        font-weight: 700;
        display: block;
        margin-bottom: 10px;
        font-size: 18px; 
    }
    
    .bilet-info { 
        color: #ffffff; /* Făcut alb conform cerinței */
        margin-bottom: 25px;
        font-size: 18px; 
        line-height: 1.6; 
        text-align: center;
        font-weight: 500;
        text-shadow: 0 1px 3px rgba(0,0,0,0.5); /* O mică umbră pentru claritate extra */
    }
    
    .form-group { margin-bottom: 25px; }
    
    .form-group select {
        width: 100%;
        padding: 16px 15px; 
        border-radius: 10px;
        border: 2px solid var(--border-color);
        background: var(--bg-main);
        color: var(--text-main);
        outline: none;
        font-family: inherit;
        font-size: 18px; 
        font-weight: 500;
        transition: 0.3s;
    }
    .form-group select:focus { border-color: var(--link-color); }
    .form-group select option { background: var(--card-bg); color: var(--text-main); font-size: 18px; }
    
    .btn-full {
        width: 100%;
        padding: 18px;
        border: none;
        border-radius: 10px;
        font-weight: bold;
        font-size: 20px; 
        cursor: pointer;
        background: #007bff;
        color: #fff;
        transition: 0.3s;
        margin-top: 15px;
    }
    .btn-full:hover { background: #0056b3; transform: translateY(-2px); }
    
    .btn-success { background: #28a745; color: #fff; }
    .btn-success:hover { background: #1e7e34; }
    
    .ticket-demo {
        background: rgba(40, 167, 69, 0.05);
        padding: 30px 20px;
        border-radius: 12px;
        text-align: center;
        border: 2px dashed #28a745;
    }
    
    .text-error { color: #dc3545; font-size: 18px; margin-bottom: 10px; font-weight: 600; }
    .btn-accent-border { background: transparent; border: 2px solid #007bff; color: #007bff; }
    .btn-accent-border:hover { background: #007bff; color: #fff; }
</style>

<section class="hero-transport">
    <h1>Transport Public</h1>
    <p>Alege o destinație sau cumpără un tichet rapid.</p>
</section>

<div class="transport-container fade-up-element">

    <div class="card-modul">
        <h2>📍 Află Traseul</h2>
        <form method="GET" action="rutare.php">
            <div class="form-group">
                <label>De unde pleci?</label>
                <select name="plecare" required>
                    <option value="" disabled selected>Alege stația de pornire...</option>
                    <?php foreach ($toate_statiile as $statie): ?>
                        <option value="<?= $statie['id'] ?>"><?= htmlspecialchars($statie['nume_statie']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Unde vrei să ajungi?</label>
                <select name="destinatie" required>
                    <option value="" disabled selected>Alege stația destinație...</option>
                    <?php foreach ($toate_statiile as $statie): ?>
                        <option value="<?= $statie['id'] ?>"><?= htmlspecialchars($statie['nume_statie']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <button type="submit" class="btn-full">🔍 Caută Traseul</button>
        </form>
    </div>

    <div class="card-modul">
        <h2>🎫 Bilet Digital</h2>
        <?php if (isset($_SESSION['user_id'])): ?>
            <p class="bilet-info">Biletul tău va fi valabil 90 de minute de la achiziție pe orice linie din oraș.</p>

            <div class="ticket-demo">
                <h3 style="margin-bottom: 20px; color: var(--text-main); font-size: 36px; font-weight: 800;">3.00 RON</h3>
                <?php $_SESSION['payment_token'] = bin2hex(random_bytes(16)); ?>
                <form method="POST" action="genereaza_bilet.php">
                    <input type="hidden" name="payment_token" value="<?= $_SESSION['payment_token'] ?>">
                    <button type="submit" class="btn-full btn-success">💳 Cumpără Biletul</button>
                </form>
            </div>
        <?php else: ?>
            <div class="ticket-demo" style="border-color: #007bff; background: rgba(0, 123, 255, 0.05);">
                <p class="text-error" style="color: #007bff;">Trebuie să fii conectat.</p>
                <button onclick="openPopup('loginPopup')" class="btn-full btn-accent-border">🔒 Loghează-te</button>
            </div>
        <?php endif; ?>
    </div>

</div>

<?php include 'footer.php'; ?>